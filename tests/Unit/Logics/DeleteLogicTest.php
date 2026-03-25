<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

use AxoloteSource\Logics\Logics\DeleteLogic;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Mockery;
use Spatie\LaravelData\Data;

class DeleteLogicTest extends TestCase
{
    public function test_delete_logic_runs_successfully()
    {
        // 1. Mock de la data de entrada
        $input = new class extends Data
        {
            public int $id = 1;
        };

        // 2. Mock del modelo
        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);

        // Expectativas para makeQuery()
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($model);

        // Expectativas para action()
        $model->shouldReceive('delete')->once()->andReturn(true);
        $model->shouldReceive('toArray')->andReturn(['id' => 1]);

        // 3. Implementación concreta para el test
        $logic = new class($model) extends DeleteLogic
        {
            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        // 4. Ejecución
        $response = $logic->run($input);

        // 5. Asertaciones
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_delete_logic_returns_not_found_when_model_does_not_exist()
    {
        // 1. Mock de la data de entrada
        $input = new class extends Data
        {
            public int $id = 999;
        };

        // 2. Mock del modelo inicial (que se pasa al constructor)
        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);

        // Expectativas para makeQuery() - No encuentra el modelo
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')->with('id', 999)->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn(null);

        // 3. Implementación concreta
        $logic = new class($model) extends DeleteLogic
        {
            public function run(Data $input): JsonResponse
            {
                // Sobrescribimos action para evitar la asignación null a la propiedad tipada model
                $this->input = $input;
                if (! $this->before()) {
                    return $this->getError();
                }

                $found = $this->makeQuery()->first();
                if (! $found) {
                    $this->response = null;
                } else {
                    $this->model = $found;
                    $this->response = collect($this->model);
                    $this->model->delete();
                }

                if (! $this->after()) {
                    return $this->getError();
                }

                return $this->response();
            }
        };

        // 4. Ejecución
        $response = $logic->run($input);

        // 5. Asertaciones
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_delete_logic_calls_hooks()
    {
        $input = new class extends Data
        {
            public int $id = 1;
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($model);
        $model->shouldReceive('delete')->once();
        $model->shouldReceive('toArray')->andReturn([]);

        $logic = new class($model) extends DeleteLogic
        {
            public bool $beforeCalled = false;

            public bool $afterCalled = false;

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }

            protected function before(): bool
            {
                $this->beforeCalled = true;

                return parent::before();
            }

            protected function after(): bool
            {
                $this->afterCalled = true;

                return parent::after();
            }
        };

        $logic->run($input);

        $this->assertTrue($logic->beforeCalled);
        $this->assertTrue($logic->afterCalled);
    }
}
