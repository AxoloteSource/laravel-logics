<?php

namespace AxoloteSource\Logics\Tests;

use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupContainer();
        $this->setupResponseFacade();
    }

    protected function setupContainer(): void
    {
        $container = new Container;
        Container::setInstance($container);

        $container->bind('config', function () {
            return new class
            {
                public function get($key, $default = null)
                {
                    if ($key === 'data.throw_when_max_transformation_depth_reached') {
                        return false;
                    }

                    return $default;
                }
            };
        });
    }

    protected function setupResponseFacade(): void
    {
        // Mock simple de Config si es necesario (para spatie/laravel-data)
        $configMock = Mockery::mock('alias:Illuminate\Support\Facades\Config');
        $configMock->shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
            return $default;
        })->byDefault();

        // Registrar en el contenedor si es posible, aunque alias: suele ser suficiente para facades
        // Pero spatie/laravel-data a veces usa el helper config()

        // Mock simple de Response facade si no está disponible o para asegurar consistencia en tests del paquete
        $responseMock = Mockery::mock('alias:Illuminate\Support\Facades\Response');

        $responseMock->shouldReceive('success')->andReturnUsing(function ($data = null, $status = 200) {
            $statusCode = $status instanceof \UnitEnum ? $status->value : $status;

            return $this->createMockJsonResponse(['data' => $data], $statusCode);
        })->byDefault();

        $responseMock->shouldReceive('error')->andReturnUsing(function ($message = 'Error', $data = null, $status = 400) {
            $statusCode = $status instanceof \UnitEnum ? $status->value : $status;

            return $this->createMockJsonResponse(['message' => $message, 'data' => $data], $statusCode);
        })->byDefault();

        // Soporte para successDataTable que usa IndexLogic
        $responseMock->shouldReceive('successDataTable')->andReturnUsing(function ($data = null, $headers = [], $status = 200) {
            $statusCode = $status instanceof \UnitEnum ? $status->value : $status;

            return $this->createMockJsonResponse([
                'data' => $data,
                'headers' => $headers,
            ], $statusCode);
        })->byDefault();
    }

    protected function createMockJsonResponse(array $data, int $status): JsonResponse
    {
        $response = Mockery::mock(JsonResponse::class);
        $response->shouldReceive('getStatusCode')->andReturn($status);
        $response->shouldReceive('getData')->andReturnUsing(function ($assoc = false) use ($data) {
            return $assoc ? $data : (object) $data;
        });

        return $response;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
