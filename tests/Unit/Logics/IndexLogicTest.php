<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

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
}
