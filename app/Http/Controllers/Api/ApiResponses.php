<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Shared JSON envelope helpers for the mobile API controllers.
 */
trait ApiResponses
{
    /** Wrap a paginated result: { success, data, meta }. */
    protected function paginated(LengthAwarePaginator $paginator, $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /** Wrap a single item: { success, data }. */
    protected function item($data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
        ], $status);
    }

    /** Simple message response. */
    protected function message(string $message, int $status = 200, bool $success = true): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
        ], $status);
    }
}
