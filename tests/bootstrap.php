<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../vendor/illuminate/support/helpers.php';

use Illuminate\Container\Container;

if (! function_exists('app')) {
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return Container::getInstance()->make('config');
        }

        return Container::getInstance()->make('config')->get($key, $default);
    }
}
