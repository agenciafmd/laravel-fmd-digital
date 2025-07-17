<?php

namespace Agenciafmd\FmdDigital\Providers;

use Illuminate\Support\ServiceProvider;

class FmdDigitalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->registerConfigs();
    }

    private function registerConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-fmd-digital.php', 'laravel-fmd-digital');
    }
}
