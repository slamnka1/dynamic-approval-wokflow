<?php

namespace App\Models;

use App\Domain\Enums\FieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormField extends Model
{
    protected $fillable = [
        'form_id',
        'key',
        'label',
        'type',
        'is_required',
        'sort_order',
        'min_value',
        'max_value',
        'placeholder',
    ];

    protected $casts = [
        'type' => FieldType::class,
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'min_value' => 'integer',
        'max_value' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order');
    }
}
