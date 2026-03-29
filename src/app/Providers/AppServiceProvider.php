<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрируем Swagger напрямую по полному имени класса
        if (class_exists(\L5Swagger\L5SwaggerServiceProvider::class)) {
            $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
        }
    }

    public function boot(): void
    {

    }
}
