<?php

namespace App\Services;

use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\DTOs\CreateFormDTO;
use App\Models\Form;
use Illuminate\Support\Collection;

class FormBuilderService
{
    public function __construct(
        private readonly FormRepositoryInterface $forms,
    ) {}

    public function listForAdmin(): Collection
    {
        return $this->forms->allForAdmin();
    }

    public function listActive(): Collection
    {
        return $this->forms->listActive();
    }

    public function find(int $id): ?Form
    {
        return $this->forms->find($id);
    }

    public function create(CreateFormDTO $dto, int $creatorId): Form
    {
        return $this->forms->create($dto, $creatorId);
    }

    public function update(Form $form, CreateFormDTO $dto): Form
    {
        return $this->forms->update($form, $dto);
    }

    public function delete(Form $form): void
    {
        $this->forms->delete($form);
    }
}
