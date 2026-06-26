<?php

namespace Modules\MembershipCards\App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\MembershipCards\Domain\Repositories\OfficerRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\BeneficiaryRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\SubscriptionRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\FeePlanRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\MembershipCardRepositoryInterface;
use Modules\MembershipCards\Domain\Repositories\AttachmentRepositoryInterface;
use Modules\MembershipCards\Infrastructure\Persistence\EloquentOfficerRepository;
use Modules\MembershipCards\Infrastructure\Persistence\EloquentBeneficiaryRepository;
use Modules\MembershipCards\Infrastructure\Persistence\EloquentSubscriptionRepository;
use Modules\MembershipCards\Infrastructure\Persistence\EloquentFeePlanRepository;
use Modules\MembershipCards\Infrastructure\Persistence\EloquentMembershipCardRepository;
use Modules\MembershipCards\Infrastructure\Persistence\EloquentAttachmentRepository;
use Modules\MembershipCards\Domain\Services\FeeCalculationService;
use Modules\MembershipCards\Domain\Services\CardValidationService;
use Modules\MembershipCards\Domain\Services\AttachmentService;

class MembershipCardsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'MembershipCards';

    protected string $moduleNameLower = 'membershipcards';

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
        $this->app->bind(OfficerRepositoryInterface::class, EloquentOfficerRepository::class);
        $this->app->bind(BeneficiaryRepositoryInterface::class, EloquentBeneficiaryRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
        $this->app->bind(FeePlanRepositoryInterface::class, EloquentFeePlanRepository::class);
        $this->app->bind(MembershipCardRepositoryInterface::class, EloquentMembershipCardRepository::class);
        $this->app->bind(AttachmentRepositoryInterface::class, EloquentAttachmentRepository::class);
    }

    /**
     * Register service bindings.
     */
    protected function registerServices(): void
    {
        $this->app->singleton(FeeCalculationService::class, function ($app) {
            return new FeeCalculationService(
                $app->make(FeePlanRepositoryInterface::class)
            );
        });
        
        $this->app->singleton(CardValidationService::class, function ($app) {
            return new CardValidationService(
                $app->make(SubscriptionRepositoryInterface::class),
                $app->make(MembershipCardRepositoryInterface::class)
            );
        });
        
        $this->app->singleton(AttachmentService::class, function ($app) {
            return new AttachmentService(
                $app->make(AttachmentRepositoryInterface::class),
                $app->make(OfficerRepositoryInterface::class),
                $app->make(BeneficiaryRepositoryInterface::class)
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

