<?php

namespace AxoloteSource\Logics;

use AxoloteSource\Logics\Responses\ResponseMacros;
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
    }
}
