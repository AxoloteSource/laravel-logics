<?php

namespace AxoloteSource\Logics\Logics\Flow;

use AxoloteSource\Logics\Classes\Filter;
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

    abstract public function filtersModel(): array;

    public function run(Data|FlowIndexData $input): JsonResponse
    {
        return parent::logic($input);
    }

    public function runQueryWithSearch(string $search): Builder
    {
        if (in_array('search', array_keys($this->customFilters()))) {
            $this->applyCustomFilter(new Filter('search', $search, 'like'));

            return $this->queryBuilder;
        }

        $colum = array_key_exists($this->modelRoute, $this->searchColum())
            ? $this->searchColum()[$this->modelRoute]
            : $this->getColumnSearch();

        return $this->queryBuilder->where($colum, 'like', "%{$search}%");
    }

    protected function customFilters(): array
    {
        $filters = $this->filtersModel()[$this->modelRoute] ?? null;

        return is_callable($filters) ? $filters() : $filters ?? [];
    }
}
