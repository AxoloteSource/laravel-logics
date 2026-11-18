<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

use AxoloteSource\Logics\Enums\Http;
use AxoloteSource\Logics\ErrorContainer;
use AxoloteSource\Logics\Logics\Logic;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Data;

class LogicTest extends TestCase
{
    public function test_logic_lazy_run_returns_self()
    {
        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            public function run(Data $input): self
            {
                return $this->lazyRun($input);
            }
        };

        $result = $logic->run($input);

        $this->assertInstanceOf(Logic::class, $result);
    }

    public function test_logic_returns_error_when_before_fails()
    {
        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            protected function before(): bool
            {
                return $this->error('Validation failed', ['field' => 'required'], Http::UnprocessableEntity);
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('Validation failed', $data['message']);
    }

    public function test_logic_returns_error_when_after_fails()
    {
        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return $this->error('Post-processing failed', null, Http::ServerError);
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function test_logic_returns_error_when_action_sets_an_error()
    {
        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                $this->error('Action failed');

                return $this;
            }

            protected function after(): bool
            {
                throw new \RuntimeException('After should not run when action has errors.');
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame('Action failed', $response->getData(true)['message']);
    }

    public function test_all_validations_pass_continues_flow()
    {
        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            protected function validations(): array
            {
                return [
                    function () {
                        return true;
                    },
                    function () {
                        return true;
                    },
                ];
            }

            public function run(Data $input): self
            {
                return $this->lazyRun($input);
            }
        };

        $result = $logic->run($input);

        $this->assertInstanceOf(Logic::class, $result);
    }

    public function test_validation_failure_returns_error()
    {
        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            protected function validations(): array
            {
                return [
                    function () {
                        return true;
                    },
                    function () {
                        return $this->error('Custom validation failed', null, Http::UnprocessableEntity);
                    },
                ];
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(422, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('Custom validation failed', $data['message']);
    }

    public function test_validation_throws_exception_when_not_callable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation must be a callable');

        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            protected function validations(): array
            {
                return [
                    'not_a_callable',
                ];
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $logic->run($input);
    }

    private function innerLogic(bool $shouldFail, string $message = 'Inner failed'): Logic
    {
        return new class($shouldFail, $message) extends Logic
        {
            private bool $shouldFail;

            private string $message;

            public function __construct(bool $shouldFail, string $message)
            {
                $this->shouldFail = $shouldFail;
                $this->message = $message;
            }

            protected function before(): bool
            {
                return $this->shouldFail
                    ? $this->error($this->message, null, Http::Forbidden)
                    : true;
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            public function run(Data $input): self
            {
                return $this->lazyRun($input);
            }
        };
    }

    public function test_outer_error_without_nesting_returns_error_and_skips_after()
    {
        $input = new class extends Data {};

        $logic = new class extends Logic
        {
            public bool $afterRan = false;

            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                $this->error('Outer denied', null, Http::Forbidden);

                return $this;
            }

            protected function after(): bool
            {
                $this->afterRan = true;

                return true;
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame('Outer denied', $response->getData(true)['message']);
        $this->assertFalse($logic->afterRan);
    }

    public function test_nested_success_does_not_clear_outer_error()
    {
        $input = new class extends Data {};
        $inner = $this->innerLogic(false);

        $logic = new class($inner) extends Logic
        {
            private Logic $inner;

            public bool $afterRan = false;

            public function __construct(Logic $inner)
            {
                $this->inner = $inner;
            }

            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                $this->error('Outer denied', null, Http::Forbidden);
                $this->inner->lazyRun(new class extends Data {});

                return $this;
            }

            protected function after(): bool
            {
                $this->afterRan = true;

                return true;
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame('Outer denied', $response->getData(true)['message']);
        $this->assertFalse($logic->afterRan);
    }

    public function test_nested_failure_propagates_to_outer()
    {
        $input = new class extends Data {};
        $inner = $this->innerLogic(true, 'Inner denied');

        $logic = new class($inner) extends Logic
        {
            private Logic $inner;

            public function __construct(Logic $inner)
            {
                $this->inner = $inner;
            }

            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                $this->inner->lazyRun(new class extends Data {});

                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame('Inner denied', $response->getData(true)['message']);
    }

    public function test_first_nested_failure_is_preserved_after_a_successful_nested_run()
    {
        $input = new class extends Data {};
        $failing = $this->innerLogic(true, 'First inner failure');
        $success = $this->innerLogic(false);

        $logic = new class($failing, $success) extends Logic
        {
            private Logic $failing;

            private Logic $success;

            public function __construct(Logic $failing, Logic $success)
            {
                $this->failing = $failing;
                $this->success = $success;
            }

            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                $this->failing->lazyRun(new class extends Data {});
                $this->success->lazyRun(new class extends Data {});

                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $response = $logic->run($input);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertSame('First inner failure', $response->getData(true)['message']);
    }

    public function test_sequential_runs_do_not_inherit_previous_errors()
    {
        $input = new class extends Data {};

        $failing = new class extends Logic
        {
            protected function before(): bool
            {
                return $this->error('First run failed', null, Http::Forbidden);
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $success = new class extends Logic
        {
            protected function before(): bool
            {
                return true;
            }

            protected function action(): Logic
            {
                return $this;
            }

            protected function after(): bool
            {
                return true;
            }

            public function run(Data $input): JsonResponse
            {
                return $this->logic($input);
            }
        };

        $first = $failing->run($input);
        $second = $success->run($input);

        $this->assertEquals(403, $first->getStatusCode());
        $this->assertEquals(200, $second->getStatusCode());
        $this->assertSame([], ErrorContainer::$error);
    }
}
