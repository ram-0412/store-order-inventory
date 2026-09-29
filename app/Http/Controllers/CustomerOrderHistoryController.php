<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerOrderHistoryRequest;
use App\Services\CustomerOrderHistoryService;
use Illuminate\Http\JsonResponse;

class CustomerOrderHistoryController extends Controller
{
    public function index(
        CustomerOrderHistoryRequest $request,
        CustomerOrderHistoryService $historyService,
    ): JsonResponse {
        $history = $historyService->findByEmail($request->validated('email'));

        if ($history === null) {
            return response()->json([
                'message' => 'Customer not found.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $history,
        ]);
    }
}
