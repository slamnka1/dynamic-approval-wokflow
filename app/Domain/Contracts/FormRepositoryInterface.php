<?php

namespace App\Domain\Contracts;

use App\Domain\DTOs\CreateFormDTO;
use App\Models\Form;
use Illuminate\Support\Collection;

interface FormRepositoryInterface
{
    public function allForAdmin(): Collection;

    public function listActive(): Collection;

    public function find(int $id): ?Form;

    public function create(CreateFormDTO $dto, int $creatorId): Form;

    public function update(Form $form, CreateFormDTO $dto): Form;

    public function delete(Form $form): void;
}
