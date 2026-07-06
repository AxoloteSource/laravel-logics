<?php

namespace AxoloteSource\Logics\Traits;

use AxoloteSource\Logics\Enums\Http;

trait ValidateNotFound
{
    protected bool $validateNotFound = true;

    protected function initializer(): bool
    {
        if (! $this->validateNotFound) {
            return true;
        }

        $foundModel = $this->makeQuery()->first();

        if (is_null($foundModel)) {
            return $this->error(message: 'Not Found', status: Http::NotFound);
        }

        $this->model = $foundModel;

        return true;
    }
}
