@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="page-heading d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div><p class="eyebrow mb-2">CATALOG</p><h1 class="h2 mb-1">Products</h1><p class="text-secondary mb-0">{{ number_format($products->total()) }} products</p></div>
    <a class="btn btn-outline-success" href="{{ route('products.low-stock') }}">View low stock</a>
</div>
<section class="card content-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Product</th><th>Code</th><th class="text-end">Price</th><th class="text-end">Tax</th><th class="text-end">Stock</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td class="fw-semibold">{{ $product->name }}</td>
                    <td><code>{{ $product->code }}</code></td>
                    <td class="text-end">${{ number_format((float) $product->price, 2) }}</td>
                    <td class="text-end">{{ number_format((float) $product->tax_percentage, 2) }}%</td>
                    <td class="text-end">{{ number_format($product->stock) }}</td>
                    <td>
                        @if ($product->stock === 0)
                            <span class="badge text-bg-danger">Out of Stock</span>
                        @elseif ($product->stock < 5)
                            <span class="badge text-bg-warning">Low Stock</span>
                        @else
                            <span class="badge text-bg-success">In Stock</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@if ($products->hasPages())
    <div class="d-flex justify-content-end mt-3">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
