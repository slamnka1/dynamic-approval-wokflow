<?php

namespace App\Domain\DTOs;

use App\Domain\Enums\WorkflowType;

class ConfigureWorkflowDTO
{
    /**
     * @param  array<int, array{approver_id:int}>  $steps
     */
    public function __construct(
        public readonly string $name,
        public readonly WorkflowType $type,
        public readonly int $requiredApprovals,
        public readonly array $steps,
    ) {}

    public static function fromArray(array $data): self
    {
        $type = WorkflowType::from($data['type']);
        $steps = $data['steps'] ?? [];

        return new self(
            name: $data['name'],
            type: $type,
            requiredApprovals: (int) ($data['required_approvals'] ?? count($steps)),
            steps: $steps,
        );
    }
}
