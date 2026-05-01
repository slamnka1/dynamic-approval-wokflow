<?php

namespace App\Services;

use App\Domain\Contracts\ApprovalRequestRepositoryInterface;
use App\Domain\Contracts\DynamicFieldValidatorInterface;
use App\Domain\DTOs\SubmitRequestDTO;
use App\Domain\Enums\RequestStatus;
use App\Models\ApprovalRequest;
use App\Models\Form;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApprovalRequestService
{
    public function __construct(
        private readonly ApprovalRequestRepositoryInterface $requests,
        private readonly DynamicFieldValidatorInterface $validator,
    ) {}

    public function submit(Form $form, User $requester, array $data, array $files = []): ApprovalRequest
    {
        if (! $form->is_active) {
            throw ValidationException::withMessages(['form' => ['This form is not accepting submissions.']]);
        }

        $form->loadMissing('workflow.steps');
        if (! $form->workflow || $form->workflow->steps->isEmpty()) {
            throw ValidationException::withMessages(['form' => ['This form has no approval workflow configured.']]);
        }

        $hasPending = ApprovalRequest::where('form_id', $form->id)
            ->where('requester_id', $requester->id)
            ->where('status', RequestStatus::Pending)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'form' => ['You already have a pending request for this form. Wait for it to be decided before submitting again.'],
            ]);
        }

        $validated = $this->validator->validate($form, $data, $files);

        return $this->requests->create($form, $requester, new SubmitRequestDTO($validated));
    }

    public function listMine(User $user): Collection
    {
        return $this->requests->listForRequester($user);
    }

    public function find(int $id): ?ApprovalRequest
    {
        return $this->requests->find($id);
    }
}
