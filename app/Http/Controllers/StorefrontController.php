<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class StorefrontController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function dashboard(): View
    {
        $productSummary = Product::query()
            ->selectRaw(
                'COUNT(*) as total_products, COALESCE(SUM(stock), 0) as total_stock_items, COALESCE(SUM(CASE WHEN stock < ? THEN 1 ELSE 0 END), 0) as low_stock_count',
                [self::LOW_STOCK_THRESHOLD],
            )
            ->first();

        return view('dashboard', [
            'productCount' => $productSummary->total_products,
            'customerCount' => Customer::count(),
            'orderCount' => Order::count(),
            'unitsOnHand' => $productSummary->total_stock_items,
            'lowStockCount' => $productSummary->low_stock_count,
            'lowStockProducts' => Product::query()
                ->where('stock', '<', self::LOW_STOCK_THRESHOLD)
                ->orderBy('stock')
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'code', 'price', 'tax_percentage', 'stock']),
            'recentOrders' => Order::with('customer')->latest()->limit(8)->get(),
        ]);
    }

    public function products(): View
    {
        return view('products.index', [
            'products' => Product::orderBy('name')->paginate(15),
        ]);
    }

    public function customers(): View
    {
        return view('customers.index', [
            'customers' => Customer::withCount('orders')->orderBy('name')->paginate(15),
        ]);
    }

    public function createOrder(): View
    {
        return view('orders.create', [
            'products' => Product::orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(['name', 'email']),
        ]);
    }

    public function orderHistory(): View
    {
        return view('orders.history', [
            'customers' => Customer::orderBy('name')->get(['name', 'email']),
        ]);
    }

    public function lowStockProducts(): View
    {
        return view('products.low-stock');
    }
}
