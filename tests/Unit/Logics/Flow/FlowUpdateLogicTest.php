<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics\Flow;

use AxoloteSource\Logics\Logics\Flow\FlowUpdateLogicBase;
use AxoloteSource\Logics\Tests\TestCase;

class FlowUpdateLogicTest extends TestCase
{
    public function test_flow_update_logic_runs_successfully()
    {
        $inputData = ['model' => 'test-model', 'id' => 1, 'name' => 'Updated Name'];

        $modelClassName = 'TestModel'.uniqid();
        eval("class $modelClassName extends \Illuminate\Database\Eloquent\Model {
            protected \$fillable = ['id', 'name'];
            public function save(array \$options = []) { return true; }
            public function find(\$id) { return \$this; }
            public function toArray() { return ['id' => \$this->id, 'name' => \$this->name]; }
        }");

        $dataClassName = 'TestUpdateData'.uniqid();
        eval("class $dataClassName extends \Spatie\LaravelData\Data {
            public int \$id;
            public string \$name;
            public static function validateAndCreate(\Illuminate\Contracts\Support\Arrayable|array \$payload): static {
                \$instance = new self();
                \$data = is_array(\$payload) ? \$payload : \$payload->toArray();
                \$instance->id = \$data['id'];
                \$instance->name = \$data['name'];
                return \$instance;
            }
        }");

        $logic = new class($modelClassName, $dataClassName) extends FlowUpdateLogicBase
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

        $this->assertEquals(200, $response->getStatusCode());
    }
}
