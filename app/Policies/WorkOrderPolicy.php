<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Support\WorkOrderService;

final class WorkOrderPolicy
{
    public function __construct(
        private readonly WorkOrderService $workOrderService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->workOrderService->canSubmitTickets($user)
            || $this->workOrderService->canManageQueue($user);
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $this->workOrderService->canManageQueue($user)
            || $workOrder->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->workOrderService->canSubmitTickets($user);
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        if ($this->workOrderService->canManageQueue($user)) {
            return true;
        }

        return $workOrder->user_id === $user->id && $workOrder->isOpen();
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        if ($this->workOrderService->canManageQueue($user)) {
            return true;
        }

        return $workOrder->user_id === $user->id
            && $workOrder->status === WorkOrderStatus::Unprocessed;
    }

    public function manageQueue(User $user): bool
    {
        return $this->workOrderService->canManageQueue($user);
    }
}
