<?php

namespace App\Persistence;

use App\Domain\Enums\FieldType;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestValue;
use App\Models\FormField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RequestValueWriter
{
    public function write(ApprovalRequest $request, FormField $field, mixed $raw): ApprovalRequestValue
    {
        $payload = ['approval_request_id' => $request->id, 'form_field_id' => $field->id];

        switch ($field->type) {
            case FieldType::Text:
            case FieldType::Textarea:
            case FieldType::Select:
                $payload['value_string'] = $raw === null ? null : (string) $raw;
                break;

            case FieldType::Number:
                $payload['value_number'] = $raw === null ? null : (float) $raw;
                break;

            case FieldType::Date:
                $payload['value_date'] = $raw ?: null;
                break;

            case FieldType::Checkbox:
                $payload['value_boolean'] = $raw === null ? null : (bool) $raw;
                break;

            case FieldType::File:
                $payload['file_path'] = $raw instanceof UploadedFile
                    ? $raw->store("forms/{$request->form_id}/requests/{$request->id}", 'public')
                    : (is_string($raw) ? $raw : null);
                break;
        }

        return ApprovalRequestValue::create($payload);
    }
}
