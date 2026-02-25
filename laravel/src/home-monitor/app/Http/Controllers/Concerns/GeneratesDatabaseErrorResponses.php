<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\{JsonResponse, Response};
use Illuminate\Support\Facades\Log;

trait GeneratesDatabaseErrorResponses
{
    /**
     * Log database exception and returns an opaque JSON response.
     */
    protected function databaseErrorResponse(QueryException $e, string $method, array $context = []): JsonResponse
    {
        $baseContext = array_merge([
            'route' => class_basename(static::class) . "::{$method}",
            'exception_class' => $e::class,

            'sql' => $e->getSql(),
            'binding_count' => is_array($e->getBindings()) ? count($e->getBindings()) : null,

            'thrown' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
        ], $context);

        Log::error('Database operation failed', $baseContext);

        // debug logs may include sensitive info
        Log::debug('Database operation failed (stacktrace)', ['exception' => $e]);

        return response()->json(
            ['errors' => ['server' => ['Database error occurred']]],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
