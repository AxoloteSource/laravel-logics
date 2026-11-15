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

    /**
     * Filter properties that must never be applied.
     *
     * @var array<int, string>
     */
    protected array $disallowedFilterProperties = [
        'deleted_at',
        'password',
    ];

    /**
     * Optional filter property allowlist. When non-empty, it takes precedence
     * over the denylist.
     *
     * @var array<int, string>
     */
    protected array $allowedFilterProperties = [];

    /**
     * Filter operators that must never be applied.
     *
     * @var array<int, string>
     */
    protected array $disallowedFilterOperators = [
        'regexp',
        'rlike',
        'not regexp',
        'not rlike',
    ];

    /**
     * Maximum number of filters to apply. Null means no limit.
     */
    protected ?int $maxFilters = null;

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
        if (isset($this->input->filters) && ! $this->validateFilters($this->input->filters)) {
            return $this;
        }

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
                if (! $this->isFilterPropertyAllowed($orderBy)) {
                    $this->error(__('The filter property ":property" is not allowed.', [
                        'property' => $orderBy,
                    ]));

                    return $this;
                }

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

    protected function validateFilters(array $filters): bool
    {
        if (! is_null($this->maxFilters) && count($filters) > $this->maxFilters) {
            return $this->error(__('Too many filters: the maximum is :max.', [
                'max' => $this->maxFilters,
            ]));
        }

        $disallowedOperators = array_map('strtolower', $this->disallowedFilterOperators);

        foreach ($filters as $filterData) {
            $property = (string) ($filterData['property'] ?? '');
            $operator = strtolower((string) ($filterData['operator'] ?? '='));

            foreach (explode('|', $property) as $item) {
                if (! $this->isFilterPropertyAllowed($item)) {
                    return $this->error(__('The filter property ":property" is not allowed.', [
                        'property' => $item,
                    ]));
                }
            }

            if (in_array($operator, $disallowedOperators, true)) {
                return $this->error(__('The filter operator ":operator" is not allowed.', [
                    'operator' => $operator,
                ]));
            }
        }

        return true;
    }

    protected function canonicalFilterProperty(string $property): string
    {
        $segments = explode('.', $property);

        return strtolower(trim((string) end($segments)));
    }

    protected function isFilterPropertyAllowed(string $property): bool
    {
        $column = $this->canonicalFilterProperty($property);

        if ($this->allowedFilterProperties !== []) {
            return in_array($column, array_map('strtolower', $this->allowedFilterProperties), true);
        }

        return ! in_array($column, array_map('strtolower', $this->disallowedFilterProperties), true);
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

    /**
     * List of column accessors that are allowed to be sorted by the frontend.
     * When empty, all columns are considered sortable.
     *
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        if (empty($this->allowOrderByFields)) {
            return [];
        }

        return array_merge($this->allowOrderByFields, array_keys($this->aliasOrderBy));
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
                $this->tableHeaders(),
                $this->sortableColumns()
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
