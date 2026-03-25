<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

use AxoloteSource\Logics\Logics\UpdateLogic;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Mockery;
use Spatie\LaravelData\Data;

class UpdateLogicTest extends TestCase
{
    public function test_update_logic_runs_successfully()
    {
        $inputData = ['id' => 1, 'name' => 'Updated Name'];

        $input = new class($inputData) extends Data
        {
            public int $id;

            public string $name;

            public function __construct(array $data)
            {
                $this->id = $data['id'];
                $this->name = $data['name'];
            }
        };

        $model = Mockery::mock(Model::class);
        $model->exists = true;

        // Mock find in before()
        $model->shouldReceive('find')->with(1)->andReturn($model);

        // Mock fill and save in action()
        $model->shouldReceive('fill')->with($inputData)->andReturnSelf();
        $model->shouldReceive('save')->once()->andReturn(true);
        $model->shouldReceive('toArray')->andReturn($inputData);

        $logic = new class($model) extends UpdateLogic
        {
            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('Updated Name', $data['data']['name']);
    }

    public function test_update_logic_returns_404_when_not_found()
    {
        $inputData = ['id' => 999, 'name' => 'Ghost'];
        $input = new class($inputData) extends Data
        {
            public int $id;

            public string $name;

            public function __construct(array $data)
            {
                $this->id = $data['id'];
                $this->name = $data['name'];
            }
        };

        $model = Mockery::mock(Model::class);
        $model->shouldReceive('find')->with(999)->andReturn(null);

        $logic = new class($model) extends UpdateLogic
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
