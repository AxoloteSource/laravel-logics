<?php

namespace AxoloteSource\Logics\Tests\Unit\Logics;

use AxoloteSource\Logics\Enums\Http;
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
}
