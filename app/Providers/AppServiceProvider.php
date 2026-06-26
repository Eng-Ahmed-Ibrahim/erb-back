<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Ramsey\Uuid\Uuid;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Pivot::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Uuid::uuid4();
            }

        });
    }
}
