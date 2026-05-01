<?php

namespace App\Domain\DTOs;

use App\Domain\Enums\ApprovalAction;

class ApprovalActionDTO
{
    public function __construct(
        public readonly ApprovalAction $action,
        public readonly ?string $comment,
    ) {}

    public static function approve(?string $comment = null): self
    {
        return new self(ApprovalAction::Approve, $comment);
    }

    public static function reject(?string $comment = null): self
    {
        return new self(ApprovalAction::Reject, $comment);
    }
}
