<?php

namespace AxoloteSource\Logics\Logics\Flow\Traits;

use AxoloteSource\Logics\Enums\Http;

trait FlowLogic
{
    private string $modelRoute;

    abstract public function allowedModels(): array;

    abstract public function resources(): array;

    abstract public function publicModels(): array;

    abstract public function isAllow(): array;

    protected function withResource(): mixed
    {
        if (! array_key_exists($this->modelRoute, $this->resources())) {
            return $this->response;
        }

        $resource = $this->resources()[$this->modelRoute];
        $array = $this->response->toArray();

        if (! array_is_list($array)) {
            return new $resource($this->model);
        }

        return $resource::collection($this->response);
    }

    private function validIsAllowModel(): bool
    {
        if (array_key_exists($this->modelRoute, $this->allowedModels())) {
            return true;
        }

        return $this->error(
            __('Model Not Found'),
            ['model' => $this->modelRoute],
            Http::NotFound
        );
    }

    private function validateAction(): bool
    {
        if (in_array($this->modelRoute, $this->publicModels(), true)) {
            return true;
        }

        $allowed = $this->isAllow();

        if (! array_key_exists($this->modelRoute, $allowed)) {
            return $this->error(
                __('You do not have permission to access this resource'),
                ['model' => $this->modelRoute],
                Http::Forbidden
            );
        }

        $action = $allowed[$this->modelRoute];

        if ($this->user()?->belongsToAction($action)) {
            return true;
        }

        return $this->error(
            __('You do not have permission to access this resource'),
            ['action' => $action],
            Http::Forbidden
        );
    }
}
