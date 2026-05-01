<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestValue extends Model
{
    protected $fillable = [
        'approval_request_id',
        'form_field_id',
        'value_string',
        'value_number',
        'value_date',
        'value_boolean',
        'file_path',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'value_date' => 'date',
        'value_boolean' => 'boolean',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    public function presentValue(): mixed
    {
        return $this->value_string
            ?? $this->value_number
            ?? ($this->value_date?->toDateString())
            ?? $this->value_boolean
            ?? $this->file_path;
    }
}
