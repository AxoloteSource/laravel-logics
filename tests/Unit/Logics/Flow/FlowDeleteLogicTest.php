<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics\Flow;

use AxoloteSource\Logics\Data\Flow\FlowByIdData;
use AxoloteSource\Logics\Logics\Flow\FlowDeleteLogicBase;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class FlowDeleteLogicTest extends TestCase
{
    public function test_flow_delete_logic_runs_successfully()
    {
        $input = new FlowByIdData('test-model', 1);

        // Definir una clase anónima de modelo para evitar el error de Mockery::__construct()
        $modelClassName = 'TestModel'.uniqid();
        eval("class $modelClassName extends \Illuminate\Database\Eloquent\Model {
            public static \$queryBuilder;
            public function newQuery() { return self::\$queryBuilder; }
            public function delete() { return true; }
            public function toArray() { return ['id' => 1]; }
        }");

        $queryBuilder = Mockery::mock(Builder::class);
        $modelClassName::$queryBuilder = $queryBuilder;

        $modelInstance = new $modelClassName;
        $queryBuilder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($modelInstance);

        // Mock de la clase concreta que extiende FlowDeleteLogicBase
        $logic = new class($modelClassName) extends FlowDeleteLogicBase
        {
            public $modelClass;

            public function __construct($modelClass)
            {
                $this->modelClass = $modelClass;
            }

            public function allowedModels(): array
            {
                return ['test-model' => $this->modelClass];
            }

            public function resources(): array
            {
                return [];
            }

            public function publicModels(): array
            {
                return [];
            }

            public function isAllow(): array
            {
                return [];
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(204, $response->getStatusCode());
    }
}
