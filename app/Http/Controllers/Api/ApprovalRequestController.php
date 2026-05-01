<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitApprovalRequestRequest;
use App\Http\Resources\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\Form;
use App\Services\ApprovalRequestService;
use App\Support\RequestAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalRequestController extends Controller
{
    public function __construct(
        private readonly ApprovalRequestService $service,
    ) {}

    public function store(SubmitApprovalRequestRequest $request, Form $form): JsonResponse
    {
        $created = $this->service->submit(
            $form,
            $request->user(),
            $request->input('values', []),
            $request->allFiles()['values'] ?? [],
        );

        return response()->json(['data' => new ApprovalRequestResource($created)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ApprovalRequestResource::collection($this->service->listMine($request->user())),
        ]);
    }

    public function show(Request $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        if (! RequestAccess::canView($request->user(), $approvalRequest)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $loaded = $this->service->find($approvalRequest->id);

        return response()->json(['data' => new ApprovalRequestResource($loaded)]);
    }
}
