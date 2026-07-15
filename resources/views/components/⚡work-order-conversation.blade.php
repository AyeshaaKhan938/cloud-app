<?php

use App\Models\WorkOrder;
use App\Services\Support\SupportQueueService;
use App\Services\Support\WorkOrderService;
use Livewire\Component;

new class extends Component
{
    public WorkOrder $ticket;

    public string $reply = '';

    public bool $canReply = true;

    public bool $canManage = false;

    public bool $liveChatAvailable = false;

    public function mount(WorkOrder $ticket): void
    {
        $this->authorizeAccess($ticket);
        $this->ticket = $ticket->load(['messages.user', 'machine', 'assignee', 'user']);
        $this->canManage = app(WorkOrderService::class)->canManageQueue();
        $this->liveChatAvailable = app(SupportQueueService::class)->isLiveChatAvailable();
        $this->canReply = $this->ticket->isOpen();
    }

    public function sendReply(): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        $service = app(WorkOrderService::class);
        $service->addMessage(
            $this->ticket,
            $user,
            $this->reply,
            $service->canManageQueue($user),
        );

        $this->reply = '';
        $this->ticket->refresh()->load(['messages.user', 'machine', 'assignee', 'user']);
    }

    public function requestLiveChat(): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        try {
            $this->ticket = app(WorkOrderService::class)->requestLiveChat($this->ticket, $user)
                ->load(['messages.user', 'machine', 'assignee', 'user']);
            $this->dispatch('notify', type: 'success', message: 'Live chat connected. A support agent will join this thread.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? 'Live chat is unavailable.';
            $this->dispatch('notify', type: 'warning', message: $message);
        }
    }

    private function authorizeAccess(WorkOrder $ticket): void
    {
        abort_unless(auth()->user()?->can('view', $ticket) ?? false, 403);
    }
};
?>

<div
    @if ($ticket->live_chat_active && $ticket->isOpen()) wire:poll.5s @endif
    class="space-y-4"
>
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Support ticket</p>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $ticket->work_order_number }}</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $ticket->device_name }} ({{ $ticket->device_number }})
                </p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <span class="rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                    {{ $ticket->priority->getLabel() }} priority
                </span>
                <span class="rounded-full bg-sky-100 px-3 py-1 font-medium text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">
                    {{ $ticket->status->getLabel() }}
                </span>
                @if ($ticket->live_chat_active)
                    <span class="rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                        Live chat active
                    </span>
                @endif
            </div>
        </div>

        @if (filled($ticket->issue_description))
            <div class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                <p class="mb-1 font-medium text-gray-900 dark:text-white">Issue description</p>
                <p class="whitespace-pre-wrap">{{ $ticket->issue_description }}</p>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="font-medium text-gray-950 dark:text-white">Conversation</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ticket updates and replies appear here.</p>
        </div>

        <div class="max-h-[28rem] space-y-3 overflow-y-auto p-4">
            @forelse ($ticket->messages as $message)
                <div @class([
                    'rounded-lg px-3 py-2 text-sm',
                    'ml-8 bg-sky-50 text-sky-950 dark:bg-sky-500/10 dark:text-sky-100' => $message->is_staff_reply,
                    'mr-8 bg-gray-50 text-gray-900 dark:bg-gray-800 dark:text-gray-100' => ! $message->is_staff_reply,
                ])>
                    <div class="mb-1 flex items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-medium">{{ $message->author_name }} @if ($message->is_staff_reply) (Support) @endif</span>
                        <span>{{ $message->created_at?->diffForHumans() }}</span>
                    </div>
                    <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No replies yet. Support will respond in this thread.</p>
            @endforelse
        </div>

        @if ($canReply)
            <div class="border-t border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-950/40">
                <label class="mb-3 block text-sm font-semibold text-gray-800 dark:text-gray-100">Add a reply</label>
                <textarea
                    wire:model="reply"
                    rows="4"
                    class="block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base leading-relaxed text-gray-900 shadow-sm outline-none ring-0 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-amber-400 dark:focus:ring-amber-500/30"
                    placeholder="Type your message..."
                    style="padding: 12px 16px;"
                ></textarea>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-filament::button
                        type="button"
                        color="primary"
                        wire:click="sendReply"
                    >
                        Send message
                    </x-filament::button>

                    @if (! $canManage && ! $ticket->live_chat_active)
                        <x-filament::button
                            type="button"
                            color="gray"
                            outlined
                            wire:click="requestLiveChat"
                        >
                            Request live chat
                        </x-filament::button>
                        @unless ($liveChatAvailable)
                            <span class="text-xs text-gray-500 dark:text-gray-400">No agents online — ticket stays in queue</span>
                        @endunless
                    @endif
                </div>
            </div>
        @else
            <div class="border-t border-gray-200 p-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                This ticket is {{ strtolower($ticket->status->getLabel()) }}. Replies are closed.
            </div>
        @endif
    </div>
</div>
