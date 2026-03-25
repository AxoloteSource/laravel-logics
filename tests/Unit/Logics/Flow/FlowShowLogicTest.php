<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics\Flow;

use AxoloteSource\Logics\Data\Flow\FlowByIdData;
use AxoloteSource\Logics\Logics\Flow\FlowShowLogicBase;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class FlowShowLogicTest extends TestCase
{
    public function test_flow_show_logic_runs_successfully()
    {
        $input = new FlowByIdData('test-model', 1);

        $modelClassName = 'TestModel'.uniqid();
        eval("class $modelClassName extends \Illuminate\Database\Eloquent\Model {
            public static \$queryBuilder;
            public function newQuery() { return self::\$queryBuilder; }
            public function find(\$id) { return \$this; }
        }");

        $queryBuilder = Mockery::mock(Builder::class);
        $modelClassName::$queryBuilder = $queryBuilder;

        $modelInstance = new $modelClassName;
        $modelInstance->exists = true;
        $modelInstance->id = 1;

        $queryBuilder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($modelInstance);

        $logic = new class($modelClassName) extends FlowShowLogicBase
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

            public function searchColum(): array
            {
                return [];
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
