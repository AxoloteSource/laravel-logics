<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics\Flow;

use AxoloteSource\Logics\Data\Flow\FlowIndexData;
use AxoloteSource\Logics\Logics\Flow\FlowIndexLogicBase;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

class FlowIndexLogicTest extends TestCase
{
    public function test_flow_index_logic_runs_successfully()
    {
        $input = new FlowIndexData('test-model');
        $input->search = 'term';

        $modelClassName = 'TestModel'.uniqid();
        eval("class $modelClassName extends \Illuminate\Database\Eloquent\Model {
            public static \$queryBuilder;
            public function newQuery() { return self::\$queryBuilder; }
        }");

        $queryBuilder = Mockery::mock(Builder::class);
        $modelClassName::$queryBuilder = $queryBuilder;

        $queryBuilder->shouldReceive('where')->with('custom_col', 'like', '%term%')->andReturnSelf();
        $queryBuilder->shouldReceive('with')->with([])->andReturnSelf();

        $paginator = Mockery::mock(\Illuminate\Pagination\LengthAwarePaginator::class);
        $paginator->shouldReceive('getCollection')->andReturn(new Collection);
        $paginator->shouldReceive('total')->andReturn(0);
        $paginator->shouldReceive('perPage')->andReturn(15);
        $paginator->shouldReceive('currentPage')->andReturn(1);

        $queryBuilder->shouldReceive('paginate')->andReturn($paginator);

        $logic = new class($modelClassName) extends FlowIndexLogicBase
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
                return ['test-model' => 'custom_col'];
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
