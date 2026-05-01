<?php

namespace Tests\Unit;

use App\Domain\Enums\RequestStatus;
use App\Domain\Enums\UserRole;
use App\Domain\Enums\WorkflowType;
use App\Models\ApprovalRequest;
use App\Models\Form;
use App\Models\Workflow;
use App\Services\Approval\ApprovalProcessorFactory;
use App\Services\Approval\SequentialApprovalProcessor;
use App\Services\Approval\ThresholdApprovalProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalProcessorFactoryTest extends TestCase
{
    use RefreshDatabase;

    private function buildRequest(WorkflowType $type): ApprovalRequest
    {
        $admin = $this->makeUser(UserRole::Admin);
        $req = $this->makeUser(UserRole::Requester);

        $form = Form::create(['name' => 'F', 'created_by' => $admin->id, 'is_active' => true]);
        $wf = Workflow::create([
            'form_id' => $form->id,
            'name' => 'W',
            'type' => $type,
            'required_approvals' => 1,
        ]);

        return ApprovalRequest::create([
            'form_id' => $form->id,
            'workflow_id' => $wf->id,
            'requester_id' => $req->id,
            'status' => RequestStatus::Pending,
            'current_step_order' => $type === WorkflowType::Sequential ? 1 : null,
        ]);
    }

    public function test_picks_sequential_processor_for_sequential_workflow(): void
    {
        $factory = app(ApprovalProcessorFactory::class);
        $processor = $factory->for($this->buildRequest(WorkflowType::Sequential));

        $this->assertInstanceOf(SequentialApprovalProcessor::class, $processor);
    }

    public function test_picks_threshold_processor_for_threshold_workflow(): void
    {
        $factory = app(ApprovalProcessorFactory::class);
        $processor = $factory->for($this->buildRequest(WorkflowType::Threshold));

        $this->assertInstanceOf(ThresholdApprovalProcessor::class, $processor);
    }
}
