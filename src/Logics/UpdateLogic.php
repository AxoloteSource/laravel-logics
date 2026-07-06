<?php

namespace AxoloteSource\Logics\Logics;

use AxoloteSource\Logics\CoreLogic;
use AxoloteSource\Logics\Enums\Http;
use AxoloteSource\Logics\Traits\ValidateNotFound;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Data;

abstract class UpdateLogic extends Logic
{
    use CoreLogic, ValidateNotFound;

    public function __construct(?Model $model = null)
    {
        if (is_null($model)) {
            return;
        }
        $this->model = $model;
    }

    abstract public function run(Data $input): JsonResponse;

    protected function before(): bool
    {
        return true;
    }

    protected function action(): Logic
    {
        $this->model->fill($this->input->toArray());
        $this->model->save();
        $this->response = collect($this->model);

        return $this;
    }

    protected function after(): bool
    {
        return true;
    }

    protected function makeQuery(): Builder
    {
        return $this->model->newQuery()
            ->where('id', $this->input->id);
    }
}
