<?php

namespace App\Http\Controllers\Api;

use App\Domain\DTOs\CreateFormDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormRequest;
use App\Http\Resources\FormResource;
use App\Models\Form;
use App\Services\FormBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function __construct(
        private readonly FormBuilderService $forms,
    ) {}

    public function adminIndex(): JsonResponse
    {
        return response()->json([
            'data' => FormResource::collection($this->forms->listForAdmin()),
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => FormResource::collection($this->forms->listActive()),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $form = $this->forms->find($id);

        if (! $form) {
            return response()->json(['message' => 'Form not found.'], 404);
        }

        return response()->json(['data' => new FormResource($form)]);
    }

    public function store(StoreFormRequest $request): JsonResponse
    {
        $dto = CreateFormDTO::fromArray($request->validated());
        $form = $this->forms->create($dto, $request->user()->id);

        return response()->json(['data' => new FormResource($form)], 201);
    }

    public function update(StoreFormRequest $request, Form $form): JsonResponse
    {
        $dto = CreateFormDTO::fromArray($request->validated());
        $updated = $this->forms->update($form, $dto);

        return response()->json(['data' => new FormResource($updated)]);
    }

    public function destroy(Form $form): JsonResponse
    {
        $this->forms->delete($form);

        return response()->json(['message' => 'Deleted.']);
    }
}
