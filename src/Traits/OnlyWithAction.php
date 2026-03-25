<?php

namespace AxoloteSource\Logics\Traits;

trait OnlyWithAction
{
    protected function before(): bool
    {
        return true;
    }

    protected function after(): bool
    {
        return true;
    }
}
