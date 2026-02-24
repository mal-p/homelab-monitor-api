<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\{JsonResponse, Response};
use Illuminate\Support\Facades\Log;

trait GeneratesDatabaseErrorResponses
{
    /**
     * Log database exception and retrun an opaque JSON response.
     */
    protected function databaseErrorResponse(QueryException $e, string $method, array $context = []): JsonResponse
    {
        Log::error('Database operation failed', array_merge([
            'route' => class_basename(static::class) . "::{$method}",
            'exception' => $e->getMessage(),
        ], $context));

        return response()->json(
            ['errors' => ['server' => ['Database error occurred']]],
            Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
