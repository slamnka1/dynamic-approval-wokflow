<?php

namespace App\Http\Requests;

use App\Domain\Enums\FieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],

            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string', 'regex:/^[a-z0-9_]+$/', 'max:64'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::enum(FieldType::class)],
            'fields.*.is_required' => ['sometimes', 'boolean'],
            'fields.*.sort_order' => ['sometimes', 'integer'],
            'fields.*.min_value' => ['nullable', 'integer'],
            'fields.*.max_value' => ['nullable', 'integer'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],

            'fields.*.options' => ['sometimes', 'array'],
            'fields.*.options.*.value' => ['required_with:fields.*.options', 'string', 'max:255'],
            'fields.*.options.*.label' => ['required_with:fields.*.options', 'string', 'max:255'],
            'fields.*.options.*.sort_order' => ['sometimes', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $keys = collect($this->input('fields', []))->pluck('key')->toArray();
            if (count($keys) !== count(array_unique($keys))) {
                $v->errors()->add('fields', 'Field keys must be unique within a form.');
            }

            foreach ($this->input('fields', []) as $i => $f) {
                if (($f['type'] ?? null) === FieldType::Select->value) {
                    $opts = $f['options'] ?? [];
                    if (count($opts) === 0) {
                        $v->errors()->add("fields.$i.options", 'Select fields must define at least one option.');
                    }
                }
            }
        });
    }
}
