<?php

namespace App\Models;

use App\Domain\Enums\ApprovalAction as ApprovalActionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    protected $fillable = [
        'approval_request_id',
        'approver_id',
        'action',
        'comment',
        'step_order',
        'acted_at',
    ];

    protected $casts = [
        'action' => ApprovalActionEnum::class,
        'acted_at' => 'datetime',
        'step_order' => 'integer',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
