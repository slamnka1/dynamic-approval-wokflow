<?php

namespace App\Providers;

use App\Domain\Contracts\ApprovalRequestRepositoryInterface;
use App\Domain\Contracts\DynamicFieldValidatorInterface;
use App\Domain\Contracts\FormRepositoryInterface;
use App\Domain\Contracts\WorkflowRepositoryInterface;
use App\Repositories\Decorators\CachedFormRepository;
use App\Repositories\EloquentApprovalRequestRepository;
use App\Repositories\EloquentFormRepository;
use App\Repositories\EloquentWorkflowRepository;
use App\Validation\DynamicFieldValidator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FormRepositoryInterface::class, function ($app) {
            return new CachedFormRepository(
                new EloquentFormRepository,
                $app->make(CacheRepository::class),
            );
        });

        $this->app->bind(WorkflowRepositoryInterface::class, EloquentWorkflowRepository::class);
        $this->app->bind(ApprovalRequestRepositoryInterface::class, EloquentApprovalRequestRepository::class);
        $this->app->bind(DynamicFieldValidatorInterface::class, DynamicFieldValidator::class);
    }

    public function boot(): void
    {
        //
    }
}
