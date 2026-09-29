<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Store Desk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/store.css') }}" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container-xxl">
        <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">Store<span>Desk</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#storeNavigation" aria-controls="storeNavigation" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="storeNavigation">
            <div class="navbar-nav ms-lg-4 me-auto">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}" href="{{ route('products.index') }}">Products</a>
                <a class="nav-link {{ request()->routeIs('customers.index') ? 'active' : '' }}" href="{{ route('customers.index') }}">Customers</a>
                <a class="nav-link {{ request()->routeIs('products.low-stock') ? 'active' : '' }}" href="{{ route('products.low-stock') }}">Low stock</a>
            </div>
            <div class="navbar-nav align-items-lg-center gap-lg-2">
                <a class="nav-link {{ request()->routeIs('orders.history') ? 'active' : '' }}" href="{{ route('orders.history') }}">Order history</a>
                <a class="btn btn-success btn-sm px-3" href="{{ route('orders.create') }}">Create order</a>
            </div>
        </div>
    </div>
</nav>

<main class="container-xxl py-4 py-lg-5">
    @yield('content')
</main>

<footer class="border-top bg-white py-3">
    <div class="container-xxl d-flex justify-content-between small text-secondary">
        <span>Store Desk</span>
        <span>{{ now()->format('Y') }}</span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/store.js') }}" defer></script>
</body>
</html>
