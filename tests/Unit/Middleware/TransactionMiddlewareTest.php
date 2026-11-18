<?php

namespace AxoloteSource\Logics\Tests\Unit\Middleware;

use AxoloteSource\Logics\Middleware\TransactionMiddleware;
use AxoloteSource\Logics\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;

class TransactionMiddlewareTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        Facade::setFacadeApplication(['db' => $this->capsule->getDatabaseManager()]);

        $this->capsule->schema()->create('events', function ($table) {
            $table->increments('id');
            $table->string('note')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    private function middleware(): TransactionMiddleware
    {
        return new class extends TransactionMiddleware
        {
            protected function shouldBypassTransaction(Request $request): bool
            {
                return $request->isMethod('get');
            }
        };
    }

    private function request(string $method = 'POST'): Request
    {
        return Request::create('/events', $method);
    }

    public function test_it_commits_on_successful_json_response()
    {
        $response = $this->middleware()->handle(
            $this->request(),
            function () {
                DB::table('events')->insert(['note' => 'created']);

                return new JsonResponse(['status' => 'OK'], 200);
            }
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('events')->count());
    }

    public function test_it_rolls_back_on_validation_422_without_error_status()
    {
        $response = $this->middleware()->handle(
            $this->request(),
            function () {
                DB::table('events')->insert(['note' => 'created']);

                return new JsonResponse([
                    'message' => 'The given data was invalid.',
                    'errors' => [],
                ], 422);
            }
        );

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('events')->count());
    }

    public function test_it_rolls_back_on_403_with_ok_status_body()
    {
        $response = $this->middleware()->handle(
            $this->request(),
            function () {
                DB::table('events')->insert(['note' => 'created']);

                return new JsonResponse(['status' => 'OK'], 403);
            }
        );

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(0, DB::table('events')->count());
    }

    public function test_it_rolls_back_on_503_without_status()
    {
        $response = $this->middleware()->handle(
            $this->request(),
            function () {
                DB::table('events')->insert(['note' => 'created']);

                return new JsonResponse(['message' => 'Service unavailable'], 503);
            }
        );

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(0, DB::table('events')->count());
    }

    public function test_it_rolls_back_on_non_json_error_response()
    {
        $response = $this->middleware()->handle(
            $this->request(),
            function () {
                DB::table('events')->insert(['note' => 'created']);

                return new Response('Conflict', 409);
            }
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(0, DB::table('events')->count());
    }

    public function test_it_commits_on_non_json_success_response()
    {
        $response = $this->middleware()->handle(
            $this->request(),
            function () {
                DB::table('events')->insert(['note' => 'created']);

                return new Response('Created', 201);
            }
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, DB::table('events')->count());
    }

    public function test_it_does_not_open_a_transaction_for_get_requests()
    {
        $response = $this->middleware()->handle(
            $this->request('GET'),
            function () {
                $this->assertSame(0, DB::transactionLevel());
                DB::table('events')->insert(['note' => 'read']);

                return new JsonResponse(['status' => 'OK'], 200);
            }
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('events')->count());
    }

    public function test_it_rolls_back_and_rethrows_when_the_closure_throws()
    {
        try {
            $this->middleware()->handle(
                $this->request(),
                function () {
                    DB::table('events')->insert(['note' => 'created']);

                    throw new \RuntimeException('boom');
                }
            );

            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }

        $this->assertSame(0, DB::table('events')->count());
    }
}
