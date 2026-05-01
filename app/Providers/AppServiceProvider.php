<?php

namespace App\Providers;

use App\Domain\Contracts\ApprovalRequestRepositoryInterface;
use App\Domain\Contracts\DynamicFieldValidatorInterface;
use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\Contracts\WorkflowRepositoryInterface;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Observers\FormCacheObserver;
use App\Repositories\Decorators\CachedFormRepository;
use App\Repositories\EloquentApprovalRequestRepository;
use App\Repositories\EloquentFormRepository;
use App\Repositories\EloquentWorkflowRepository;
use App\Support\FormCache;
use App\Validation\DynamicFieldValidator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormCache::class);

        $this->app->bind(FormRepositoryInterface::class, function ($app) {
            return new CachedFormRepository(
                new EloquentFormRepository,
                $app->make(FormCache::class),
            );
        });

        $this->app->bind(WorkflowRepositoryInterface::class, EloquentWorkflowRepository::class);
        $this->app->bind(ApprovalRequestRepositoryInterface::class, EloquentApprovalRequestRepository::class);
        $this->app->bind(DynamicFieldValidatorInterface::class, DynamicFieldValidator::class);
    }

    public function boot(): void
    {
        Form::observe(FormCacheObserver::class);
        FormField::observe(FormCacheObserver::class);
        FormFieldOption::observe(FormCacheObserver::class);
        Workflow::observe(FormCacheObserver::class);
        WorkflowStep::observe(FormCacheObserver::class);
    }
}
