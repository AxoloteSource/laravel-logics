<?php

namespace AxoloteSource\Logics\Logics\Flow\Traits;

trait WithoutValidate
{
    protected function initializer(): bool
    {
        $this->modelRoute = $this->input->model;

        $allowedModels = $this->allowedModels();
        $this->model = new $allowedModels[$this->modelRoute];

        return parent::initializer();
    }

    protected function before(): bool
    {
        if (! $this->validIsAllowModel()) {
            return false;
        }

        if (! $this->validateAction()) {
            return false;
        }

        return true;
    }
}
