<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Enums\UserFeature;
use App\Enums\UserRole;
use App\Enums\WorkOrderReportingStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Machine;
use App\Models\NotificationSetting;
use App\Models\SupportAgentPresence;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderMessage;
use App\Notifications\LiveChatRequestedNotification;
use App\Notifications\NewSupportTicketNotification;
use App\Notifications\SupportTicketMessageNotification;
use App\Notifications\SupportTicketResolvedNotification;
use App\Services\Users\FeatureAccess;
use App\Services\Users\UserCloudScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class WorkOrderService
{
    public function __construct(
        private readonly UserCloudScope $cloudScope,
        private readonly FeatureAccess $featureAccess,
    ) {}

    public function canSubmitTickets(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($this->cloudScope->hasFullCloudAccess($user)) {
            return true;
        }

        return $this->featureAccess->can($user, UserFeature::WorkOrders);
    }

    public function canManageQueue(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user instanceof User && $this->cloudScope->hasFullCloudAccess($user);
    }

    public function generateTicketNumber(): string
    {
        return 'ST-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTicket(array $data, User $submitter): WorkOrder
    {
        if (! $this->canSubmitTickets($submitter)) {
            abort(403);
        }

        $machine = $this->resolveMachine((int) ($data['machine_id'] ?? 0), $submitter);

        $ticket = WorkOrder::query()->create([
            'user_id' => $submitter->id,
            'machine_id' => $machine?->id,
            'work_order_number' => $this->generateTicketNumber(),
            'device_number' => $machine?->machine_number ?? (string) ($data['device_number'] ?? ''),
            'device_name' => $machine?->machine_name ?? (string) ($data['device_name'] ?? ''),
            'associated_account' => $submitter->account,
            'device_type' => 'Vending kiosk',
            'submitted_by' => $submitter->name,
            'issue_description' => (string) ($data['issue_description'] ?? ''),
            'issue_type' => $data['issue_type'],
            'attachments' => $data['attachments'] ?? null,
            'submitted_at' => now(),
            'priority' => $data['priority'],
            'status' => WorkOrderStatus::Unprocessed,
            'reporting_status' => $data['reporting_status'] ?? WorkOrderReportingStatus::None,
        ]);

        $this->notifyAdminsOfNewTicket($ticket);

        return $ticket->fresh(['machine', 'user', 'messages']);
    }

    public function addMessage(
        WorkOrder $ticket,
        User $author,
        string $body,
        bool $isStaffReply,
        bool $notify = true,
    ): WorkOrderMessage {
        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Message cannot be empty.',
            ]);
        }

        $message = WorkOrderMessage::query()->create([
            'work_order_id' => $ticket->id,
            'user_id' => $author->id,
            'author_name' => $author->name,
            'body' => $body,
            'is_staff_reply' => $isStaffReply,
        ]);

        $ticket->update(['last_message_at' => now()]);

        if ($notify) {
            $this->notifyMessageRecipients($ticket, $message, $author);
        }

        return $message;
    }

    public function updateStatus(WorkOrder $ticket, WorkOrderStatus $status, User $actor): WorkOrder
    {
        if (! $this->canManageQueue($actor)) {
            abort(403);
        }

        $wasResolved = $ticket->isResolved();

        $ticket->update([
            'status' => $status,
            'resolved_at' => $status->isResolved() ? now() : null,
        ]);

        if ($status->isResolved()) {
            $ticket->update(['live_chat_active' => false]);
        }

        if (! $wasResolved && $ticket->isResolved() && $ticket->user instanceof User) {
            $ticket->user->notify(new SupportTicketResolvedNotification($ticket));
        }

        return $ticket->fresh();
    }

    public function assign(WorkOrder $ticket, ?User $assignee, User $actor): WorkOrder
    {
        if (! $this->canManageQueue($actor)) {
            abort(403);
        }

        if ($assignee !== null && ! $this->canManageQueue($assignee)) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => 'Assignee must be a support agent account.',
            ]);
        }

        $ticket->update(['assigned_to_user_id' => $assignee?->id]);

        if ($ticket->status === WorkOrderStatus::Unprocessed && $assignee !== null) {
            $ticket->update(['status' => WorkOrderStatus::Processing]);
        }

        return $ticket->fresh(['assignee']);
    }

    public function requestLiveChat(WorkOrder $ticket, User $requester): WorkOrder
    {
        if ($ticket->user_id !== $requester->id && ! $this->canManageQueue($requester)) {
            abort(403);
        }

        if ($ticket->isResolved()) {
            throw ValidationException::withMessages([
                'live_chat' => 'Live chat is not available on resolved tickets.',
            ]);
        }

        $ticket->update(['live_chat_requested_at' => now()]);

        $availableAgents = User::query()
            ->whereHas('supportAgentPresence', fn ($query) => $query->where('is_available_for_live_chat', true))
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
            ->where('is_enabled', true)
            ->get();

        if ($availableAgents->isEmpty()) {
            throw ValidationException::withMessages([
                'live_chat' => 'No support agents are available for live chat right now. Your ticket remains in the queue and support will reply here.',
            ]);
        }

        Notification::send($availableAgents, new LiveChatRequestedNotification($ticket));

        $ticket->update(['live_chat_active' => true]);

        return $ticket->fresh();
    }

    public function setAgentAvailability(User $agent, bool $available): SupportAgentPresence
    {
        if (! $this->canManageQueue($agent)) {
            abort(403);
        }

        return SupportAgentPresence::query()->updateOrCreate(
            ['user_id' => $agent->id],
            [
                'is_available_for_live_chat' => $available,
                'last_seen_at' => now(),
            ],
        );
    }

    private function resolveMachine(int $machineId, User $submitter): ?Machine
    {
        if ($machineId <= 0) {
            return null;
        }

        $machine = Machine::query()->find($machineId);

        if ($machine === null) {
            throw ValidationException::withMessages([
                'machine_id' => 'Machine not found.',
            ]);
        }

        if (! $this->cloudScope->ownsMachine($machine, $submitter) && ! $this->cloudScope->hasFullCloudAccess($submitter)) {
            throw ValidationException::withMessages([
                'machine_id' => 'You can only open support tickets for your own machines.',
            ]);
        }

        return $machine;
    }

    private function notifyAdminsOfNewTicket(WorkOrder $ticket): void
    {
        $admins = User::query()
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
            ->where('is_enabled', true)
            ->get();

        Notification::send($admins, new NewSupportTicketNotification($ticket));

        $this->sendNotificationEmail(new NewSupportTicketNotification($ticket));
    }

    private function notifyMessageRecipients(WorkOrder $ticket, WorkOrderMessage $message, User $author): void
    {
        if ($message->is_staff_reply) {
            if ($ticket->user instanceof User && $ticket->user->id !== $author->id) {
                $ticket->user->notify(new SupportTicketMessageNotification($ticket, $message));
            }

            return;
        }

        $admins = User::query()
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
            ->where('is_enabled', true)
            ->get();

        Notification::send($admins, new SupportTicketMessageNotification($ticket, $message));

        $this->sendNotificationEmail(new SupportTicketMessageNotification($ticket, $message));
    }

    private function sendNotificationEmail(object $notification): void
    {
        $email = NotificationSetting::current()->notification_email;

        if (! filled($email)) {
            return;
        }

        try {
            Notification::route('mail', $email)->notify($notification);
        } catch (\Throwable $exception) {
            Log::warning('support_notification_email_failed', [
                'email' => $email,
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
