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
use App\Services\Approval\ThresholdApprovalProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThresholdApprovalProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function buildScenario(int $required = 2): array
    {
        $admin = $this->makeUser(UserRole::Admin);
        $requester = $this->makeUser(UserRole::Requester);
        $a = $this->makeUser(UserRole::Approver);
        $b = $this->makeUser(UserRole::Approver);
        $c = $this->makeUser(UserRole::Approver);

        $form = Form::create(['name' => 'PTO', 'created_by' => $admin->id, 'is_active' => true]);
        $workflow = Workflow::create([
            'form_id' => $form->id,
            'name' => 'N-of-M',
            'type' => WorkflowType::Threshold,
            'required_approvals' => $required,
        ]);
        foreach ([$a, $b, $c] as $i => $u) {
            WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => $i + 1, 'approver_id' => $u->id]);
        }

        $req = ApprovalRequest::create([
            'form_id' => $form->id,
            'workflow_id' => $workflow->id,
            'requester_id' => $requester->id,
            'status' => RequestStatus::Pending,
            'current_step_order' => null,
        ]);

        return [$a, $b, $c, $req];
    }

    public function test_anyone_in_pool_can_act_first(): void
    {
        [$a, $b, $c, $req] = $this->buildScenario();
        $p = app(ThresholdApprovalProcessor::class);

        $this->assertTrue($p->canApproverAct($req, $a));
        $this->assertTrue($p->canApproverAct($req, $b));
        $this->assertTrue($p->canApproverAct($req, $c));
    }

    public function test_user_outside_pool_cannot_act(): void
    {
        [, , , $req] = $this->buildScenario();
        $outsider = $this->makeUser(UserRole::Approver);

        $this->assertFalse(app(ThresholdApprovalProcessor::class)->canApproverAct($req, $outsider));
    }

    public function test_threshold_approves_after_n_approvals(): void
    {
        [$a, $b, , $req] = $this->buildScenario(2);
        $p = app(ThresholdApprovalProcessor::class);

        $p->process($req, $a, ApprovalActionDTO::approve());
        $req->refresh();
        $this->assertSame(RequestStatus::Pending, $req->status);

        $p->process($req, $b, ApprovalActionDTO::approve());
        $req->refresh();
        $this->assertSame(RequestStatus::Approved, $req->status);
        $this->assertSame(2, $req->actions()->count());
    }

    public function test_a_single_reject_terminates(): void
    {
        [$a, , , $req] = $this->buildScenario(2);
        $p = app(ThresholdApprovalProcessor::class);

        $p->process($req, $a, ApprovalActionDTO::reject('blocked'));
        $req->refresh();
        $this->assertSame(RequestStatus::Rejected, $req->status);
    }

    public function test_an_approver_cannot_act_twice_on_the_same_request(): void
    {
        [$a, , , $req] = $this->buildScenario(3);
        $p = app(ThresholdApprovalProcessor::class);

        $p->process($req, $a, ApprovalActionDTO::approve());
        $this->assertFalse($p->canApproverAct($req->fresh(), $a));
    }

    public function test_completed_request_blocks_further_actions(): void
    {
        [$a, $b, $c, $req] = $this->buildScenario(2);
        $p = app(ThresholdApprovalProcessor::class);

        $p->process($req, $a, ApprovalActionDTO::approve());
        $p->process($req->fresh(), $b, ApprovalActionDTO::approve());

        $this->assertFalse($p->canApproverAct($req->fresh(), $c));
    }
}
