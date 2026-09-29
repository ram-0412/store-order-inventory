@extends('layouts.app')

@section('title', 'Low stock products')

@section('content')
<div class="page-heading mb-4">
    <p class="eyebrow mb-2">INVENTORY</p>
    <h1 class="h2 mb-1">Low stock products</h1>
</div>
<section class="card content-card mb-4">
    <div class="card-body">
        <form id="low-stock-form" class="row g-2 align-items-end needs-validation" novalidate>
            <div class="col-8 col-md-4 col-lg-3">
                <label for="stock-threshold" class="form-label">Low Stock Threshold</label>
                <input id="stock-threshold" name="threshold" type="number" class="form-control" min="0" step="1" value="5" required aria-describedby="threshold-feedback">
                <div id="threshold-feedback" class="invalid-feedback">Enter a whole number of zero or greater.</div>
            </div>
            <div class="col-4 col-md-auto d-grid">
                <button id="search-low-stock" type="submit" class="btn btn-success">Search</button>
            </div>
        </form>
    </div>
</section>
<div id="low-stock-status" aria-live="polite"></div>
<section class="card content-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Product</th><th>Code</th><th class="text-end">Price</th><th class="text-end">Tax</th><th class="text-end">Stock</th></tr></thead>
            <tbody id="low-stock-results"><tr><td colspan="5" class="empty-state">Loading products...</td></tr></tbody>
        </table>
    </div>
</section>
@endsection
