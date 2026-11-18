<?php

namespace AxoloteSource\Logics\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TransactionMiddleware
{
    /**
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypassTransaction($request)) {
            return $next($request);
        }

        DB::beginTransaction();

        try {
            $response = $next($request);

            if ($response->getStatusCode() >= 400) {
                DB::rollBack();

                return $response;
            }

            DB::commit();
        } catch (\Exception|Throwable $e) {
            DB::rollBack();
            logger('rollback error');
            throw $e;
        }

        return $response;
    }

    /**
     * GET requests and the test environment intentionally skip the transaction.
     * Override this method in tests to exercise the middleware.
     */
    protected function shouldBypassTransaction(Request $request): bool
    {
        return $request->isMethod('get') || app()->runningUnitTests();
    }
}
