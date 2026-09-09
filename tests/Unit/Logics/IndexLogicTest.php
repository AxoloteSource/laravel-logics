<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

use AxoloteSource\Logics\Data\IndexData;
use AxoloteSource\Logics\Logics\IndexLogic;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Spatie\LaravelData\Data;

class IndexLogicTest extends TestCase
{
    public function test_index_logic_runs_with_pagination_successfully()
    {
        // 1. Mock de la data de entrada
        $input = new class extends Data
        {
            public int $limit = 10;
            public int $page = 1;
        };

        // 2. Mock del modelo y query builder
        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);
        $collection = collect([['id' => 1, 'name' => 'Test']]);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('with')->with([])->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->with(10, ['*'], 'page', 1)->andReturn($pagination);
        
        $pagination->shouldReceive('getCollection')->andReturn($collection);
        $pagination->shouldReceive('total')->andReturn(1);
        $pagination->shouldReceive('perPage')->andReturn(10);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        // 3. Implementación concreta
        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        // 4. Ejecución
        $response = $logic->run($input);

        // 5. Asertaciones
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('headers', $data);
    }

    public function test_index_logic_with_filters()
    {
        $input = new class extends Data
        {
            public array $filters = [
                ['property' => 'status', 'value' => 'active', 'operator' => '=']
            ];
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        // Filtro aplicado via Filter class (applyToQuery)
        $queryBuilder->shouldReceive('where')->with('status', '=', 'active')->andReturnSelf();
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);
        
        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_with_search()
    {
        $input = new class extends Data
        {
            public string $search = 'query';
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        // Búsqueda por defecto usa 'name'
        $queryBuilder->shouldReceive('where')->with('name', 'like', '%query%')->andReturnSelf();
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);
        
        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_with_custom_filters()
    {
        $input = new class extends Data
        {
            public array $filters = [
                ['property' => 'custom', 'value' => 'value']
            ];
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        
        // Custom filter expectation
        $queryBuilder->shouldReceive('whereIn')->with('type', ['A', 'B'])->andReturnSelf();
        
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);
        
        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }

            protected function customFilters(): array
            {
                return [
                    'custom' => function ($filter) {
                        $this->queryBuilder->whereIn('type', ['A', 'B']);
                    }
                ];
            }
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_order_by_applies_order_with_default_direction()
    {
        $input = new class extends Data
        {
            public string $order_by = 'name';
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);

        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_order_by_applies_desc_direction_from_order_param()
    {
        $input = new class extends Data
        {
            public string $order_by = 'price';
            public string $order = 'desc';
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('orderBy')->with('price', 'desc')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);

        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_order_by_translates_alias_via_aliasOrderBy()
    {
        $input = new class extends Data
        {
            public string $order_by = 'category_name';
        };

        $relation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        $relatedModel = Mockery::mock(Model::class);
        $relatedModel->shouldReceive('getTable')->andReturn('categories');
        $relation->shouldReceive('getRelated')->andReturn($relatedModel);
        $relation->shouldReceive('getOwnerKeyName')->andReturn('id');
        $relation->shouldReceive('getForeignKeyName')->andReturn('category_id');

        $productClass = 'ProductOrderAlias'.uniqid();
        $GLOBALS['__relMocks'][$productClass] = $relation;
        eval("
            class $productClass extends \Illuminate\Database\Eloquent\Model {
                protected \$table = 'products';
                public \$timestamps = false;
                public function category() {
                    return \$GLOBALS['__relMocks']['$productClass'];
                }
            }
        ");

        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        // El alias 'category_name' se traduce a la columna real 'category.name'
        $queryBuilder->shouldReceive('select')->with('products.*')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('leftJoin')->with('categories as category_0', 'category_0.id', '=', 'products.category_id')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->with('category_0.name', 'asc')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);

        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class(new $productClass(), $queryBuilder) extends IndexLogic
        {
            public $qb;

            public function __construct($model, $qb)
            {
                parent::__construct($model);
                $this->qb = $qb;
            }

            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }

            protected array $aliasOrderBy = [
                'category_name' => 'category.name',
            ];

            public function makeQuery(): Builder
            {
                return $this->qb;
            }
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());

        unset($GLOBALS['__relMocks'][$productClass]);
    }

    public function test_index_logic_order_by_ignored_when_not_in_allowOrderByFields()
    {
        $input = new class extends Data
        {
            public string $order_by = 'forbidden_column';
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);

        // orderBy no debería ser llamado porque 'forbidden_column' no está en la lista
        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);

        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }

            protected array $allowOrderByFields = ['id', 'name'];
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_order_by_allows_columns_in_allowOrderByFields()
    {
        $input = new class extends Data
        {
            public string $order_by = 'name';
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('orderBy')->with('name', 'asc')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);

        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }

            protected array $allowOrderByFields = ['id', 'name', 'price'];
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_order_by_without_explicit_order_uses_default_id()
    {
        // El IndexData por defecto trae order_by = 'id' y order = 'asc'
        $input = new IndexData();

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $pagination = Mockery::mock(LengthAwarePaginator::class);

        $model->shouldReceive('newQuery')->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('orderBy')->with('id', 'asc')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('with')->andReturnSelf();
        $queryBuilder->shouldReceive('paginate')->andReturn($pagination);

        $pagination->shouldReceive('getCollection')->andReturn(collect());
        $pagination->shouldReceive('total')->andReturn(0);
        $pagination->shouldReceive('perPage')->andReturn(15);
        $pagination->shouldReceive('currentPage')->andReturn(1);

        $logic = new class($model) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_index_logic_order_by_with_belongs_to_relation()
    {
        $queryBuilder = Mockery::mock(Builder::class);

        $relation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        $relatedModel = Mockery::mock(Model::class);
        $relatedModel->shouldReceive('getTable')->andReturn('users');
        $relation->shouldReceive('getRelated')->andReturn($relatedModel);
        $relation->shouldReceive('getOwnerKeyName')->andReturn('id');
        $relation->shouldReceive('getForeignKeyName')->andReturn('user_id');

        $postClass = 'PostOrderRel'.uniqid();
        $GLOBALS['__relMocks'][$postClass] = $relation;
        eval("
            class $postClass extends \Illuminate\Database\Eloquent\Model {
                protected \$table = 'posts';
                public \$timestamps = false;
                public function user() {
                    return \$GLOBALS['__relMocks']['$postClass'];
                }
            }
        ");

        $queryBuilder->shouldReceive('select')->with('posts.*')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('leftJoin')->with('users as user_0', 'user_0.id', '=', 'posts.user_id')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->with('user_0.name', 'desc')->once()->andReturnSelf();

        $logic = new class(new $postClass()) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        // @phpstan-ignore-next-line
        $result = $logic->runQueryWithOrder($queryBuilder, 'user.name', 'desc');
        $this->assertSame($queryBuilder, $result);

        unset($GLOBALS['__relMocks'][$postClass]);
    }

    public function test_index_logic_order_by_with_has_one_relation()
    {
        $queryBuilder = Mockery::mock(Builder::class);

        $relation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasOne::class);
        $relatedModel = Mockery::mock(Model::class);
        $relatedModel->shouldReceive('getTable')->andReturn('profiles');
        $relation->shouldReceive('getRelated')->andReturn($relatedModel);
        $relation->shouldReceive('getLocalKeyName')->andReturn('id');
        $relation->shouldReceive('getForeignKeyName')->andReturn('user_id');

        $userClass = 'UserOrderRel2'.uniqid();
        $GLOBALS['__relMocks'][$userClass] = $relation;
        eval("
            class $userClass extends \Illuminate\Database\Eloquent\Model {
                protected \$table = 'users2';
                public \$timestamps = false;
                public function profile() {
                    return \$GLOBALS['__relMocks']['$userClass'];
                }
            }
        ");

        $queryBuilder->shouldReceive('select')->with('users2.*')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('leftJoin')->with('profiles as profile_0', 'profile_0.user_id', '=', 'users2.id')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->with('profile_0.phone', 'asc')->once()->andReturnSelf();

        $logic = new class(new $userClass()) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        // @phpstan-ignore-next-line
        $result = $logic->runQueryWithOrder($queryBuilder, 'profile.phone', 'asc');
        $this->assertSame($queryBuilder, $result);

        unset($GLOBALS['__relMocks'][$userClass]);
    }

    public function test_index_logic_order_by_relation_falls_back_when_relation_method_missing()
    {
        $queryBuilder = Mockery::mock(Builder::class);

        // Modelo real sin el método de relación 'nonexistent' → fallback a orderBy directo
        $postClass = 'PostNoRel'.uniqid();
        eval("
            class $postClass extends \Illuminate\Database\Eloquent\Model {
                protected \$table = 'posts_norel';
                public \$timestamps = false;
            }
        ");

        $queryBuilder->shouldReceive('select')->with('posts_norel.*')->once()->andReturnSelf();
        $queryBuilder->shouldReceive('orderBy')->with('nonexistent.column', 'asc')->once()->andReturnSelf();

        $logic = new class(new $postClass()) extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }
        };

        // @phpstan-ignore-next-line
        $result = $logic->runQueryWithOrder($queryBuilder, 'nonexistent.column', 'asc');
        $this->assertSame($queryBuilder, $result);
    }

    public function test_index_logic_sortable_columns_returns_all_when_allow_empty()
    {
        $logic = new class extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }

            public function exposedSortableColumns(): array
            {
                return $this->sortableColumns();
            }
        };

        $this->assertSame([], $logic->exposedSortableColumns());
    }

    public function test_index_logic_sortable_columns_merges_allowed_fields_and_alias_keys()
    {
        $logic = new class extends IndexLogic
        {
            public function run(Data $input): JsonResponse
            {
                // @phpstan-ignore-next-line
                return $this->logic($input);
            }

            protected array $allowOrderByFields = ['id', 'name', 'price'];

            protected array $aliasOrderBy = ['category_name' => 'category.name'];

            public function exposedSortableColumns(): array
            {
                return $this->sortableColumns();
            }
        };

        $this->assertSame(['id', 'name', 'price', 'category_name'], $logic->exposedSortableColumns());
    }
}
