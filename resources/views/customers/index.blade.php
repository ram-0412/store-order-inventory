@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="page-heading mb-4">
    <p class="eyebrow mb-2">PEOPLE</p>
    <h1 class="h2 mb-1">Customers</h1>
    <p class="text-secondary mb-0">{{ number_format($customers->total()) }} customers</p>
</div>
<section class="card content-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th class="text-end">Orders</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td class="fw-semibold">{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td class="text-end">{{ number_format($customer->orders_count) }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-success" href="{{ route('orders.history', ['email' => $customer->email]) }}">View history</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-state">No customers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@if ($customers->hasPages())
    <div class="d-flex justify-content-end mt-3">
        {{ $customers->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
