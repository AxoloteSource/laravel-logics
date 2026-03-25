<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

use AxoloteSource\Logics\Logics\ShowLogic;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Mockery;
use Spatie\LaravelData\Data;

class ShowLogicTest extends TestCase
{
    public function test_show_logic_runs_successfully()
    {
        $input = new class extends Data
        {
            public int $id = 1;
        };

        $model = Mockery::mock(Model::class);
        $queryBuilder = Mockery::mock(Builder::class);

        // Mock find in before()
        $model->shouldReceive('find')->with(1)->andReturn($model);

        // Mock makeQuery in action()
        $model->shouldReceive('newQuery')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($model);

        $model->shouldReceive('toArray')->andReturn(['id' => 1, 'name' => 'Test']);

        $logic = new class($model) extends ShowLogic
        {
            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals(1, $data['data']['id']);
    }

    public function test_show_logic_returns_404_when_not_found_in_before()
    {
        $input = new class extends Data
        {
            public int $id = 999;
        };

        $model = Mockery::mock(Model::class);

        // Simular que no se encuentra en find() dentro de before()
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $logic = new class($model) extends ShowLogic
        {
            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
