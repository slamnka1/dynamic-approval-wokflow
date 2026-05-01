<?php

namespace App\Domain\Contracts;

use App\Domain\DTOs\SubmitRequestDTO;
use App\Models\ApprovalRequest;
use App\Models\Form;
use App\Models\User;
use Illuminate\Support\Collection;

interface ApprovalRequestRepositoryInterface
{
    public function find(int $id): ?ApprovalRequest;

    public function listForRequester(User $user): Collection;

    public function listPendingForApprover(User $user): Collection;

    public function listPastForApprover(User $user): Collection;

    public function create(Form $form, User $requester, SubmitRequestDTO $dto): ApprovalRequest;
}
