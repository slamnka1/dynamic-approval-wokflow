<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestValueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'form_field_id' => $this->form_field_id,
            'field_key' => $this->whenLoaded('field', fn () => $this->field->key),
            'field_label' => $this->whenLoaded('field', fn () => $this->field->label),
            'field_type' => $this->whenLoaded('field', fn () => $this->field->type->value),
            'value' => $this->presentValue(),
        ];
    }
}
