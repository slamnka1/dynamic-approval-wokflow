<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FormFieldResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type->value,
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'min_value' => $this->min_value,
            'max_value' => $this->max_value,
            'placeholder' => $this->placeholder,
            'options' => FormFieldOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
