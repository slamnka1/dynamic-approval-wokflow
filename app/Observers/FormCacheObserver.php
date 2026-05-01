<?php

namespace App\Observers;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Support\FormCache;
use Illuminate\Database\Eloquent\Model;

class FormCacheObserver
{
    public function __construct(
        private readonly FormCache $cache,
    ) {}

    public function saved(Model $model): void
    {
        $this->bust($model);
    }

    public function deleted(Model $model): void
    {
        $this->bust($model);
    }

    private function bust(Model $model): void
    {
        $formId = $this->formIdFor($model);

        if ($formId !== null) {
            $this->cache->forget($formId);
        }
    }

    private function formIdFor(Model $model): ?int
    {
        return match (true) {
            $model instanceof Form            => $model->id,
            $model instanceof FormField       => $model->form_id,
            $model instanceof FormFieldOption => $model->field?->form_id,
            $model instanceof Workflow        => $model->form_id,
            $model instanceof WorkflowStep    => $model->workflow?->form_id,
            default                           => null,
        };
    }
}
