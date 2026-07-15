<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\SupportAgentPresence;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;

final class SupportQueueService
{
    /**
     * @return Builder<WorkOrder>
     */
    public function queueQuery(): Builder
    {
        return WorkOrder::query()
            ->with(['user', 'machine', 'assignee'])
            ->whereIn('status', [
                WorkOrderStatus::Unprocessed,
                WorkOrderStatus::Processing,
            ])
            ->orderByRaw($this->priorityOrderSql())
            ->orderBy('submitted_at');
    }

    public function openCount(): int
    {
        return WorkOrder::query()
            ->whereIn('status', [WorkOrderStatus::Unprocessed, WorkOrderStatus::Processing])
            ->count();
    }

    public function urgentCount(): int
    {
        return WorkOrder::query()
            ->whereIn('status', [WorkOrderStatus::Unprocessed, WorkOrderStatus::Processing])
            ->where('priority', WorkOrderPriority::Urgent)
            ->count();
    }

    public function liveChatRequestedCount(): int
    {
        return WorkOrder::query()
            ->where('live_chat_active', true)
            ->whereIn('status', [WorkOrderStatus::Unprocessed, WorkOrderStatus::Processing])
            ->count();
    }

    public function isLiveChatAvailable(): bool
    {
        return SupportAgentPresence::query()
            ->where('is_available_for_live_chat', true)
            ->whereHas('user', fn (Builder $query): Builder => $query->where('is_enabled', true))
            ->exists();
    }

    /**
     * @return list<User>
     */
    public function availableAgents(): array
    {
        return User::query()
            ->whereHas('supportAgentPresence', fn (Builder $query): Builder => $query->where('is_available_for_live_chat', true))
            ->where('is_enabled', true)
            ->orderBy('name')
            ->get()
            ->all();
    }

    private function priorityOrderSql(): string
    {
        $driver = WorkOrder::query()->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return "CASE priority WHEN '".WorkOrderPriority::Urgent->value."' THEN 1 WHEN '".WorkOrderPriority::High->value."' THEN 2 WHEN '".WorkOrderPriority::Normal->value."' THEN 3 ELSE 4 END";
        }

        return 'FIELD(priority, '
            ."'".WorkOrderPriority::Urgent->value."', "
            ."'".WorkOrderPriority::High->value."', "
            ."'".WorkOrderPriority::Normal->value."', "
            ."'".WorkOrderPriority::Low->value."')";
    }
}
