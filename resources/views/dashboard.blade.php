@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-heading d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
        <p class="eyebrow mb-2">STORE OPERATIONS</p>
        <h1 class="h2 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">{{ now()->format('l, F j') }}</p>
    </div>
    <a class="btn btn-success" href="{{ route('orders.create') }}">Create order</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Total Products</div><div class="metric-value">{{ number_format($productCount) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Total Customers</div><div class="metric-value">{{ number_format($customerCount) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Total Orders</div><div class="metric-value">{{ number_format($orderCount) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-xl">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Total Stock Items</div><div class="metric-value">{{ number_format($unitsOnHand) }}</div>
        </div></div>
    </div>
    <div class="col-12 col-xl">
        <a class="card metric-card metric-warning h-100 text-decoration-none" href="{{ route('products.low-stock') }}"><div class="card-body">
            <div class="metric-label">Low Stock Product Count</div><div class="metric-value">{{ number_format($lowStockCount) }}</div>
        </div></a>
    </div>
</div>

<section class="card content-card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h2 class="h6 mb-0">Low stock products</h2>
        <a class="small link-success" href="{{ route('products.low-stock') }}">View low stock</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Product</th><th>Code</th><th class="text-end">Price</th><th class="text-end">Tax</th><th class="text-end">Stock</th></tr></thead>
            <tbody>
            @forelse ($lowStockProducts as $product)
                <tr>
                    <td class="fw-semibold">{{ $product->name }}</td>
                    <td><code>{{ $product->code }}</code></td>
                    <td class="text-end">${{ number_format((float) $product->price, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $product->tax_percentage, 2) }}%</td>
                    <td class="text-end"><span class="badge text-bg-warning">{{ number_format($product->stock) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">No products below 5 units.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="card content-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h2 class="h6 mb-0">Recent orders</h2>
        <a class="small link-success" href="{{ route('orders.history') }}">Look up customer history</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            @forelse ($recentOrders as $order)
                <tr>
                    <td class="fw-semibold">#{{ $order->id }}</td>
                    <td>{{ $order->customer->name }}<div class="small text-secondary">{{ $order->customer->email }}</div></td>
                    <td>{{ $order->created_at->format('M j, Y g:i A') }}</td>
                    <td class="text-end">${{ number_format((float) $order->grand_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-state">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
