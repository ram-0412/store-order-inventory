<?php

namespace App\Http\Controllers;

use App\Http\Requests\LowStockProductsRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class LowStockProductsController extends Controller
{
    public function index(LowStockProductsRequest $request): JsonResponse
    {
        $products = Product::query()
            ->where('stock', '<', (int) $request->validated('threshold'))
            ->orderBy('stock')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'price', 'tax_percentage', 'stock']);

        return response()->json([
            'data' => $products,
        ]);
    }
}
