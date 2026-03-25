<?php

namespace AxoloteSource\Logics\Logics\Flow;

use AxoloteSource\Logics\Data\Flow\FlowIndexData;
use AxoloteSource\Logics\Logics\Flow\Traits\FlowLogic;
use AxoloteSource\Logics\Logics\Flow\Traits\WhitSearch;
use AxoloteSource\Logics\Logics\Flow\Traits\WithoutValidate;
use AxoloteSource\Logics\Logics\IndexLogic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Data;

abstract class FlowIndexLogicBase extends IndexLogic
{
    use FlowLogic, WhitSearch, WithoutValidate;

    protected Data|FlowIndexData $input;

    public function run(Data|FlowIndexData $input): JsonResponse
    {
        return parent::logic($input);
    }

    public function runQueryWithSearch(string $search): Builder
    {
        $colum = array_key_exists($this->modelRoute, $this->searchColum())
            ? $this->searchColum()[$this->modelRoute]
            : $this->getColumnSearch();

        return $this->queryBuilder->where($colum, 'like', "%{$search}%");
    }
}
