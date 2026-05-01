<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'current_step_order' => $this->current_step_order,
            'requester_id' => $this->requester_id,
            'requester' => $this->whenLoaded('requester', fn () => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
                'email' => $this->requester->email,
            ]),
            'form' => $this->whenLoaded('form', fn () => [
                'id' => $this->form->id,
                'name' => $this->form->name,
            ]),
            'workflow' => $this->whenLoaded('workflow', fn () => [
                'id' => $this->workflow->id,
                'name' => $this->workflow->name,
                'type' => $this->workflow->type->value,
            ]),
            'values' => ApprovalRequestValueResource::collection($this->whenLoaded('values')),
            'actions' => ApprovalActionResource::collection($this->whenLoaded('actions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
