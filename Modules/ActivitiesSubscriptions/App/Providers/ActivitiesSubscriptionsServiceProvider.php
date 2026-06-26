<?php

namespace Modules\ActivitiesSubscriptions\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AcademyRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\OfferRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriberRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\AttendanceRepositoryInterface;
use Modules\ActivitiesSubscriptions\Domain\Repositories\CoachRepositoryInterface;
use Modules\ActivitiesSubscriptions\Infrastructure\Persistence\EloquentAcademyRepository;
use Modules\ActivitiesSubscriptions\Infrastructure\Persistence\EloquentOfferRepository;
use Modules\ActivitiesSubscriptions\Infrastructure\Persistence\EloquentSubscriberRepository;
use Modules\ActivitiesSubscriptions\Infrastructure\Persistence\EloquentSubscriptionRepository;
use Modules\ActivitiesSubscriptions\Infrastructure\Persistence\EloquentAttendanceRepository;
use Modules\ActivitiesSubscriptions\Infrastructure\Persistence\EloquentCoachRepository;
use Modules\ActivitiesSubscriptions\Domain\Services\QRCodeService;
use Modules\ActivitiesSubscriptions\Domain\Services\QRCodeImageService;
use Modules\ActivitiesSubscriptions\Domain\Services\SubscriptionValidationService;

class ActivitiesSubscriptionsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'ActivitiesSubscriptions';

    protected string $moduleNameLower = 'activitiessubscriptions';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Infrastructure/Migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerRepositories();
        $this->registerServices();
    }

    /**
     * Register repository bindings.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(AcademyRepositoryInterface::class, EloquentAcademyRepository::class);
        $this->app->bind(OfferRepositoryInterface::class, EloquentOfferRepository::class);
        $this->app->bind(SubscriberRepositoryInterface::class, EloquentSubscriberRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, EloquentAttendanceRepository::class);
        $this->app->bind(CoachRepositoryInterface::class, EloquentCoachRepository::class);
    }

    /**
     * Register service bindings.
     */
    protected function registerServices(): void
    {
        $this->app->singleton(QRCodeService::class);
        $this->app->singleton(QRCodeImageService::class);
        $this->app->singleton(SubscriptionValidationService::class, function ($app) {
            return new SubscriptionValidationService(
                $app->make(OfferRepositoryInterface::class),
                $app->make(AcademyRepositoryInterface::class),
                $app->make(SubscriptionRepositoryInterface::class)
            );
        });
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);

        $componentNamespace = str_replace('/', '\\', config('modules.namespace').'\\'.$this->moduleName.'\\'.config('modules.paths.generator.component-class.path'));
        Blade::componentNamespace($componentNamespace, $this->moduleNameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}