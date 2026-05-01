<?php

namespace App\Http\Requests;

use App\Domain\Enums\WorkflowType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigureWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(WorkflowType::class)],
            'required_approvals' => ['nullable', 'integer', 'min:1'],

            'steps' => ['required', 'array', 'min:1'],
            'steps.*.approver_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
