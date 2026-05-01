<?php

namespace App\Services\Approval;

use App\Domain\Contracts\ApprovalProcessorInterface;
use App\Domain\Enums\WorkflowType;
use App\Models\ApprovalRequest;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class ApprovalProcessorFactory
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function for(ApprovalRequest $request): ApprovalProcessorInterface
    {
        $request->loadMissing('workflow');

        return match ($request->workflow->type) {
            WorkflowType::Sequential => $this->container->make(SequentialApprovalProcessor::class),
            WorkflowType::Threshold => $this->container->make(ThresholdApprovalProcessor::class),
            default => throw new InvalidArgumentException('Unknown workflow type.'),
        };
    }
}
