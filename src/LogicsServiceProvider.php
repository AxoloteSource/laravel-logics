<?php

namespace AxoloteSource\Logics;

use AxoloteSource\Logics\Responses\ResponseMacros;
use AxoloteSource\Logics\Commands;
use Illuminate\Support\ServiceProvider;

class LogicsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        ResponseMacros::register();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\PublishSkillsCommand::class,
            ]);
        }
    }
}
