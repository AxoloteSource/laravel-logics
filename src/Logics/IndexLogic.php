<?php

namespace AxoloteSource\Logics\Logics;

use AxoloteSource\Logics\Classes\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Response;
use Spatie\LaravelData\Data;

abstract class IndexLogic extends Logic
{
    protected Builder $queryBuilder;

    protected LengthAwarePaginator $pagination;

    protected bool $withPagination = true;

    protected array $allowOrderByFields = [];

    protected array $aliasOrderBy = [];

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

    public function action(): self
    {
        $limit = $this->input->limit ?? 15;
        $page = $this->input->page ?? 1;
        $this->queryBuilder = $this->makeQuery();

        if (isset($this->input->filters)) {
            $this->queryBuilder = $this->runQueryFilters($this->input->filters);
        }

        if (isset($this->input->search)) {
            $this->queryBuilder = $this->runQueryWithSearch($this->input->search);
        }

        if (isset($this->input->order_by)) {
            $orderBy = $this->input->order_by;

            if (isset($this->aliasOrderBy[$orderBy])) {
                $orderBy = $this->aliasOrderBy[$orderBy];
            }

            if (empty($this->allowOrderByFields) || in_array($orderBy, $this->allowOrderByFields)) {
                $this->queryBuilder = $this->runQueryWithOrder(
                    $this->queryBuilder,
                    $orderBy,
                    $this->input->order ?? 'asc'
                );
            }
        }

        $this->queryBuilder->with($this->withRelations());

        if ($this->withPagination) {
            $this->pagination = $this->queryBuilder->paginate($limit, ['*'], 'page', $page);
            $this->response = $this->pagination->getCollection();
        } else {
            $this->response = $this->queryBuilder->get();
        }

        return $this;
    }

    protected function after(): bool
    {
        return true;
    }

    public function makeQuery(): Builder
    {
        if (method_exists($this->model, 'scopeIndex') || method_exists($this->model, 'index')) {
            return $this->model->index();
        }

        return $this->model->newQuery();
    }

    public function runQueryFilters(array $filters): Builder
    {
        $customFilters = array_keys($this->customFilters());

        foreach ($filters as $filterData) {
            $property = $filterData['property'];
            $value = $filterData['value'];
            $operator = $filterData['operator'] ?? '=';

            $filter = new Filter($property, $value, $operator);

            if (in_array($property, $customFilters)) {
                $this->applyCustomFilter($filter);

                continue;
            }

            $this->queryBuilder = $filter->applyToQuery($this->queryBuilder);
        }

        return $this->queryBuilder;
    }

    public function runQueryWithSearch(string $search): Builder
    {
        if (in_array('search', array_keys($this->customFilters()))) {
            $this->applyCustomFilter(new Filter('search', $search, 'like'));

            return $this->queryBuilder;
        }

        return $this->queryBuilder->where($this->getColumnSearch(), 'like', "%{$search}%");
    }

    public function runQueryWithOrder(Builder $queryBuilder, string $orderBy, string $direction = 'asc'): Builder
    {
        if (str_contains($orderBy, '.')) {
            return $this->applyOrderByRelation($queryBuilder, $orderBy, $direction);
        }

        return $queryBuilder->orderBy($orderBy, $direction);
    }

    protected function applyOrderByRelation(Builder $queryBuilder, string $orderBy, string $direction): Builder
    {
        $parts = explode('.', $orderBy);
        $column = array_pop($parts);
        $relations = $parts;

        $currentModel = $this->model;
        $currentTable = $currentModel->getTable();
        $baseTable = $currentTable;

        $queryBuilder->select("{$baseTable}.*");

        foreach ($relations as $index => $relationName) {
            if (! method_exists($currentModel, $relationName)) {
                return $queryBuilder->orderBy($orderBy, $direction);
            }

            $relation = $currentModel->$relationName();
            $relatedModel = $relation->getRelated();
            $relatedTable = $relatedModel->getTable();
            $relatedAlias = "{$relationName}_{$index}";

            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $ownerKey = $relation->getOwnerKeyName();
                $foreignKey = $relation->getForeignKeyName();

                $queryBuilder->leftJoin(
                    "{$relatedTable} as {$relatedAlias}",
                    "{$relatedAlias}.{$ownerKey}",
                    '=',
                    "{$currentTable}.{$foreignKey}"
                );
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                $localKey = $relation->getLocalKeyName();
                $foreignKey = $relation->getForeignKeyName();

                $queryBuilder->leftJoin(
                    "{$relatedTable} as {$relatedAlias}",
                    "{$relatedAlias}.{$foreignKey}",
                    '=',
                    "{$currentTable}.{$localKey}"
                );
            } else {
                return $queryBuilder->orderBy($orderBy, $direction);
            }

            $currentModel = $relatedModel;
            $currentTable = $relatedAlias;
        }

        return $queryBuilder->orderBy("{$currentTable}.{$column}", $direction);
    }

    protected function getColumnSearch(): string
    {
        return 'name';
    }

    protected function tableHeaders(): array
    {
        if (method_exists($this->model, 'getTableHeaders')) {
            return $this->model->getTableHeaders();
        }

        return [];
    }

    protected function withRelations(): array
    {
        return [];
    }

    protected function response(): JsonResponse
    {
        if ($this->withPagination) {
            return Response::successDataTable(
                new LengthAwarePaginator(
                    $this->withResource(),
                    $this->pagination->total(),
                    $this->pagination->perPage(),
                    $this->pagination->currentPage()
                ),
                $this->tableHeaders()
            );
        }

        return Response::success(
            $this->withResource(),
        );
    }

    protected function customFilters(): array
    {
        return [];
    }

    protected function applyCustomFilter(Filter $filter): void
    {
        $customFilters = $this->customFilters();
        $property = $filter->property;

        if (isset($customFilters[$property])) {
            $filterCallback = $customFilters[$property];
            if (! is_callable($filterCallback)) {
                throw new \InvalidArgumentException("Filter for property {$property} is not callable.");
            }

            $filterCallback($filter);
        }
    }
}
