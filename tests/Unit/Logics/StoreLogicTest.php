<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

use AxoloteSource\Logics\Logics\StoreLogic;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Mockery;
use Spatie\LaravelData\Data;

class StoreLogicTest extends TestCase
{
    public function test_store_logic_runs_successfully()
    {
        $inputData = ['name' => 'New Resource', 'description' => 'Test Description'];

        $input = new class($inputData) extends Data
        {
            public string $name;

            public string $description;

            public function __construct(array $data)
            {
                $this->name = $data['name'];
                $this->description = $data['description'];
            }
        };

        $model = Mockery::mock(Model::class);

        // Mock fill and save in action()
        $model->shouldReceive('fill')->with($inputData)->andReturnSelf();
        $model->shouldReceive('save')->once()->andReturn(true);
        $model->shouldReceive('toArray')->andReturn($inputData);

        $logic = new class($model) extends StoreLogic
        {
            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(201, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('New Resource', $data['data']['name']);
    }
}
