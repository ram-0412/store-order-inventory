<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        try {
            $order = $orderService->create($request->validated());
        } catch (InsufficientStockException $exception) {
            return response()->json([
                'message' => 'Insufficient stock for one or more products.',
                'errors' => [
                    'items' => [[
                        'product_id' => $exception->productId,
                        'available' => $exception->available,
                        'requested' => $exception->requested,
                    ]],
                ],
            ], JsonResponse::HTTP_CONFLICT);
        }

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order,
        ], JsonResponse::HTTP_CREATED);
    }
}
