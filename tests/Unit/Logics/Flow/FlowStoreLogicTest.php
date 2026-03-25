<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics\Flow;

use AxoloteSource\Logics\Logics\Flow\FlowStoreLogicBase;
use AxoloteSource\Logics\Tests\TestCase;

class FlowStoreLogicTest extends TestCase
{
    public function test_flow_store_logic_runs_successfully()
    {
        $inputData = ['model' => 'test-model', 'name' => 'Test Name'];

        $modelClassName = 'TestModel'.uniqid();
        eval("class $modelClassName extends \Illuminate\Database\Eloquent\Model {
            protected \$fillable = ['name'];
            public function save(array \$options = []) { return true; }
            public function toArray() { return ['id' => 1, 'name' => \$this->name]; }
        }");

        $dataClassName = 'TestData'.uniqid();
        eval("class $dataClassName extends \Spatie\LaravelData\Data {
            public string \$name;
            public static function validateAndCreate(\Illuminate\Contracts\Support\Arrayable|array \$payload): static {
                \$instance = new self();
                \$instance->name = is_array(\$payload) ? \$payload['name'] : \$payload->toArray()['name'];
                return \$instance;
            }
        }");

        $logic = new class($modelClassName, $dataClassName) extends FlowStoreLogicBase
        {
            public $modelClass;

            public $dataClass;

            public function __construct($modelClass, $dataClass)
            {
                $this->modelClass = $modelClass;
                $this->dataClass = $dataClass;
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

            public function validates(): array
            {
                return ['test-model' => $this->dataClass];
            }
        };

        $response = $logic->run($inputData);

        $this->assertEquals(201, $response->getStatusCode());
    }
}
