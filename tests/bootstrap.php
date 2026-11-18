<?php

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../vendor/illuminate/support/helpers.php';

require_once __DIR__ . '/Stubs/App/Models/User.php';

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

if (! function_exists('__')) {
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        foreach ($replace as $search => $value) {
            $key = str_replace(':'.$search, $value, $key);
        }

        return $key;
    }
}

if (! function_exists('logger')) {
    function logger(mixed $message = null, array $context = []): void
    {
        // No-op logger for package tests.
    }
}
