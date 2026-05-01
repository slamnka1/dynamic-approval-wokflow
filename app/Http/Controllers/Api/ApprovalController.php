<?php

namespace App\Http\Controllers\Api;

use App\Domain\DTOs\ApprovalActionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalActionRequest;
use App\Http\Resources\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        private readonly ApprovalService $service,
    ) {}

    public function pending(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ApprovalRequestResource::collection($this->service->pending($request->user())),
        ]);
    }

    public function past(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ApprovalRequestResource::collection($this->service->past($request->user())),
        ]);
    }

    public function approve(ApprovalActionRequest $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $updated = $this->service->act(
            $approvalRequest,
            $request->user(),
            ApprovalActionDTO::approve($request->input('comment')),
        );

        return response()->json(['data' => new ApprovalRequestResource($updated->load(['actions.approver']))]);
    }

    public function reject(ApprovalActionRequest $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $updated = $this->service->act(
            $approvalRequest,
            $request->user(),
            ApprovalActionDTO::reject($request->input('comment')),
        );

        return response()->json(['data' => new ApprovalRequestResource($updated->load(['actions.approver']))]);
    }
}
