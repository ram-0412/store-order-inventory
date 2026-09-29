<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;

Route::get('/', [StorefrontController::class, 'dashboard'])->name('dashboard');
Route::get('/products', [StorefrontController::class, 'products'])->name('products.index');
Route::get('/customers', [StorefrontController::class, 'customers'])->name('customers.index');
Route::get('/orders/create', [StorefrontController::class, 'createOrder'])->name('orders.create');
Route::get('/orders/history', [StorefrontController::class, 'orderHistory'])->name('orders.history');
Route::get('/products/low-stock', [StorefrontController::class, 'lowStockProducts'])->name('products.low-stock');
