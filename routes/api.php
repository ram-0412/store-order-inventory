<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerOrderHistoryController;
use App\Http\Controllers\LowStockProductsController;
use Illuminate\Support\Facades\Route;

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/customers/orders', [CustomerOrderHistoryController::class, 'index']);
Route::get('/products/low-stock', [LowStockProductsController::class, 'index']);
