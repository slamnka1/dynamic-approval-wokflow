<?php

namespace App\Http\Controllers\Api;

use App\Domain\DTOs\ConfigureWorkflowDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigureWorkflowRequest;
use App\Http\Resources\WorkflowResource;
use App\Models\Form;
use App\Services\WorkflowConfigurationService;
use Illuminate\Http\JsonResponse;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowConfigurationService $service,
    ) {}

    public function configure(ConfigureWorkflowRequest $request, Form $form): JsonResponse
    {
        $dto = ConfigureWorkflowDTO::fromArray($request->validated());
        $workflow = $this->service->configure($form, $dto);

        return response()->json(['data' => new WorkflowResource($workflow)], 201);
    }

    public function show(Form $form): JsonResponse
    {
        $workflow = $form->workflow()->with('steps.approver')->first();

        if (! $workflow) {
            return response()->json(['message' => 'No workflow configured.'], 404);
        }

        return response()->json(['data' => new WorkflowResource($workflow)]);
    }
}
