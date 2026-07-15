<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkOrderIssueType;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderReportingStatus;
use App\Enums\WorkOrderStatus;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'machine_id',
    'assigned_to_user_id',
    'work_order_number',
    'device_number',
    'device_name',
    'associated_account',
    'device_type',
    'submitted_by',
    'issue_description',
    'issue_type',
    'attachments',
    'submitted_at',
    'resolved_at',
    'last_message_at',
    'live_chat_requested_at',
    'live_chat_active',
    'user_rating',
    'priority',
    'reporting_status',
    'status',
])]
final class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_message_at' => 'datetime',
            'live_chat_requested_at' => 'datetime',
            'live_chat_active' => 'boolean',
            'user_rating' => 'integer',
            'attachments' => 'array',
            'status' => WorkOrderStatus::class,
            'reporting_status' => WorkOrderReportingStatus::class,
            'priority' => WorkOrderPriority::class,
            'issue_type' => WorkOrderIssueType::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Machine, $this> */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /** @return HasMany<WorkOrderMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(WorkOrderMessage::class)->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isResolved(): bool
    {
        return $this->status->isResolved();
    }
}
