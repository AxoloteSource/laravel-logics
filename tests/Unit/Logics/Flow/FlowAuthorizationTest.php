<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics\Flow;

use App\Models\User;
use AxoloteSource\Logics\Data\Flow\FlowByIdData;
use AxoloteSource\Logics\Logics\Flow\FlowDeleteLogicBase;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

class FlowAuthorizationTest extends TestCase
{
    /**
     * @return array{0: string, 1: Builder&\Mockery\MockInterface}
     */
    private function makeModelFixture(): array
    {
        $modelClassName = 'FlowProbeWidget'.uniqid();

        eval("class $modelClassName extends \\Illuminate\\Database\\Eloquent\\Model {
            public static \$queryBuilder;
            public static int \$deleteCalls = 0;
            public function newQuery() { return self::\$queryBuilder; }
            public function delete() { static::\$deleteCalls++; return true; }
            public function toArray() { return ['id' => 1]; }
        }");

        $queryBuilder = Mockery::mock(Builder::class);
        $modelClassName::$queryBuilder = $queryBuilder;

        return [$modelClassName, $queryBuilder];
    }

    private function makeLogic(
        string $modelClassName,
        array $publicModels,
        array $isAllow,
        ?User $user = null
    ): FlowDeleteLogicBase {
        return new class($modelClassName, $publicModels, $isAllow, $user) extends FlowDeleteLogicBase
        {
            private string $modelClass;

            private array $publicModelsList;

            private array $isAllowList;

            private ?User $fakeUser;

            public function __construct(string $modelClass, array $publicModels, array $isAllow, ?User $user)
            {
                $this->modelClass = $modelClass;
                $this->publicModelsList = $publicModels;
                $this->isAllowList = $isAllow;
                $this->fakeUser = $user;
            }

            public function allowedModels(): array
            {
                return ['widgets' => $this->modelClass];
            }

            public function resources(): array
            {
                return [];
            }

            public function publicModels(): array
            {
                return $this->publicModelsList;
            }

            public function isAllow(): array
            {
                return $this->isAllowList;
            }

            protected function user(): ?User
            {
                return $this->fakeUser;
            }
        };
    }

    public function test_unmapped_model_is_denied()
    {
        [$modelClass, $queryBuilder] = $this->makeModelFixture();

        $queryBuilder->shouldNotReceive('where');
        $queryBuilder->shouldNotReceive('first');

        $logic = $this->makeLogic($modelClass, [], []);

        $response = $logic->run(new FlowByIdData('widgets', 1));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame(0, $modelClass::$deleteCalls);
    }

    public function test_public_model_is_allowed()
    {
        [$modelClass, $queryBuilder] = $this->makeModelFixture();

        $model = new $modelClass;
        $model->exists = true;

        $queryBuilder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($model);

        $logic = $this->makeLogic($modelClass, ['widgets'], []);

        $response = $logic->run(new FlowByIdData('widgets', 1));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame(1, $modelClass::$deleteCalls);
    }

    public function test_mapped_model_denied_by_action()
    {
        [$modelClass, $queryBuilder] = $this->makeModelFixture();

        $queryBuilder->shouldNotReceive('where');
        $queryBuilder->shouldNotReceive('first');

        $user = Mockery::mock(User::class);
        $user->shouldReceive('belongsToAction')->with('widgets.destroy')->andReturn(false);

        $logic = $this->makeLogic($modelClass, [], ['widgets' => 'widgets.destroy'], $user);

        $response = $logic->run(new FlowByIdData('widgets', 1));

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame(0, $modelClass::$deleteCalls);
    }

    public function test_unknown_model_returns_not_found()
    {
        [$modelClass, $queryBuilder] = $this->makeModelFixture();

        $queryBuilder->shouldNotReceive('where');
        $queryBuilder->shouldNotReceive('first');

        $logic = $this->makeLogic($modelClass, [], []);

        $response = $logic->run(new FlowByIdData('ghost', 1));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame(0, $modelClass::$deleteCalls);
    }

    public function test_mapped_model_allowed_by_action()
    {
        [$modelClass, $queryBuilder] = $this->makeModelFixture();

        $model = new $modelClass;
        $model->exists = true;

        $queryBuilder->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $queryBuilder->shouldReceive('first')->andReturn($model);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('belongsToAction')->with('widgets.destroy')->andReturn(true);

        $logic = $this->makeLogic($modelClass, [], ['widgets' => 'widgets.destroy'], $user);

        $response = $logic->run(new FlowByIdData('widgets', 1));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame(1, $modelClass::$deleteCalls);
    }
}
