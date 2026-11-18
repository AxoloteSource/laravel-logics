<?php

namespace AxoloteSource\Logics\Logics\Flow\Traits;

trait WithoutValidate
{
    protected function initializer(): bool
    {
        $this->modelRoute = $this->input->model;

        if (! $this->validIsAllowModel()) {
            return false;
        }

        if (! $this->validateAction()) {
            return false;
        }

        $allowedModels = $this->allowedModels();
        $this->model = new $allowedModels[$this->modelRoute];

        return parent::initializer();
    }

    protected function before(): bool
    {
        return true;
    }
}
