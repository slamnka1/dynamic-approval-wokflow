<?php

namespace Tests\Unit;

use App\Domain\DTOs\ApprovalActionDTO;
use App\Domain\Enums\RequestStatus;
use App\Domain\Enums\UserRole;
use App\Domain\Enums\WorkflowType;
use App\Models\ApprovalRequest;
use App\Models\Form;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\Approval\SequentialApprovalProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequentialApprovalProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function buildScenario(): array
    {
        $admin = $this->makeUser(UserRole::Admin);
        $requester = $this->makeUser(UserRole::Requester);
        $first = $this->makeUser(UserRole::Approver);
        $second = $this->makeUser(UserRole::Approver);

        $form = Form::create(['name' => 'Test', 'created_by' => $admin->id, 'is_active' => true]);
        $workflow = Workflow::create([
            'form_id' => $form->id,
            'name' => 'Sequential',
            'type' => WorkflowType::Sequential,
            'required_approvals' => 2,
        ]);
        WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => 1, 'approver_id' => $first->id]);
        WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => 2, 'approver_id' => $second->id]);

        $req = ApprovalRequest::create([
            'form_id' => $form->id,
            'workflow_id' => $workflow->id,
            'requester_id' => $requester->id,
            'status' => RequestStatus::Pending,
            'current_step_order' => 1,
        ]);

        return [$first, $second, $requester, $req];
    }

    public function test_only_current_step_approver_can_act(): void
    {
        [$first, $second, , $req] = $this->buildScenario();
        $p = app(SequentialApprovalProcessor::class);

        $this->assertTrue($p->canApproverAct($req, $first));
        $this->assertFalse($p->canApproverAct($req, $second));
    }

    public function test_approve_advances_step_and_completes_at_last(): void
    {
        [$first, $second, , $req] = $this->buildScenario();
        $p = app(SequentialApprovalProcessor::class);

        $p->process($req, $first, ApprovalActionDTO::approve('lgtm'));
        $req->refresh();
        $this->assertSame(RequestStatus::Pending, $req->status);
        $this->assertSame(2, $req->current_step_order);
        $this->assertSame(1, $req->actions()->count());

        $p->process($req, $second, ApprovalActionDTO::approve());
        $req->refresh();
        $this->assertSame(RequestStatus::Approved, $req->status);
        $this->assertNull($req->current_step_order);
        $this->assertSame(2, $req->actions()->count());
    }

    public function test_reject_at_any_step_terminates(): void
    {
        [$first, , , $req] = $this->buildScenario();
        $p = app(SequentialApprovalProcessor::class);

        $p->process($req, $first, ApprovalActionDTO::reject('nope'));
        $req->refresh();
        $this->assertSame(RequestStatus::Rejected, $req->status);
        $this->assertNull($req->current_step_order);
    }

    public function test_completed_request_blocks_further_actions(): void
    {
        [$first, $second, , $req] = $this->buildScenario();
        $p = app(SequentialApprovalProcessor::class);

        $p->process($req, $first, ApprovalActionDTO::approve());
        $p->process($req->fresh(), $second, ApprovalActionDTO::approve());

        $this->assertFalse($p->canApproverAct($req->fresh(), $first));
        $this->assertFalse($p->canApproverAct($req->fresh(), $second));
    }
}
