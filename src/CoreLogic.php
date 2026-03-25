<?php

namespace AxoloteSource\Logics;

use AxoloteSource\Logics\Enums\Http;

trait CoreLogic
{
    protected function error(
        ?string $message = null,
        ?array $data = null,
        Http $status = Http::UnprocessableEntity
    ): false {
        ErrorContainer::error($message, $data, $status);

        return false;
    }
}
