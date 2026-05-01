<?php

namespace App\Repositories;

use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\DTOs\CreateFormDTO;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentFormRepository implements FormRepositoryInterface
{
    public function allForAdmin(): Collection
    {
        return Form::with(['fields.options', 'workflow.steps.approver'])
            ->orderByDesc('id')
            ->get();
    }

    public function listActive(): Collection
    {
        return Form::with(['fields.options'])
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): ?Form
    {
        return Form::with(['fields.options', 'workflow.steps.approver'])->find($id);
    }

    public function create(CreateFormDTO $dto, int $creatorId): Form
    {
        return DB::transaction(function () use ($dto, $creatorId) {
            $form = Form::create([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
                'created_by' => $creatorId,
            ]);

            $this->syncFields($form, $dto->fields);

            return $form->load(['fields.options']);
        });
    }

    public function update(Form $form, CreateFormDTO $dto): Form
    {
        return DB::transaction(function () use ($form, $dto) {
            $form->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);

            $form->fields()->delete();
            $this->syncFields($form, $dto->fields);

            return $form->load(['fields.options']);
        });
    }

    public function delete(Form $form): void
    {
        $form->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncFields(Form $form, array $fields): void
    {
        foreach ($fields as $i => $f) {
            $field = FormField::create([
                'form_id' => $form->id,
                'key' => $f['key'],
                'label' => $f['label'],
                'type' => $f['type'],
                'is_required' => (bool) ($f['is_required'] ?? false),
                'sort_order' => (int) ($f['sort_order'] ?? $i),
                'min_value' => $f['min_value'] ?? null,
                'max_value' => $f['max_value'] ?? null,
                'placeholder' => $f['placeholder'] ?? null,
            ]);

            foreach ($f['options'] ?? [] as $j => $opt) {
                FormFieldOption::create([
                    'form_field_id' => $field->id,
                    'value' => $opt['value'],
                    'label' => $opt['label'],
                    'sort_order' => (int) ($opt['sort_order'] ?? $j),
                ]);
            }
        }
    }
}
