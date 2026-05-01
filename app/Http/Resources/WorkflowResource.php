<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'form_id' => $this->form_id,
            'name' => $this->name,
            'type' => $this->type->value,
            'required_approvals' => $this->required_approvals,
            'steps' => WorkflowStepResource::collection($this->whenLoaded('steps')),
        ];
    }
}
