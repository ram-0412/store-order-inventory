@extends('layouts.app')

@section('title', 'Order history')

@section('content')
<div class="page-heading mb-4">
    <p class="eyebrow mb-2">SALES</p>
    <h1 class="h2 mb-1">Order history</h1>
</div>
<section class="card content-card mb-4">
    <div class="card-body">
        <form id="order-history-form" class="row g-2 align-items-end needs-validation" data-customer-autofill novalidate>
            <div class="col-md-5">
                <label for="history-name" class="form-label">Customer name</label>
                <input id="history-name" name="customer_name" list="history-customer-name-options" data-customer-name class="form-control" placeholder="Select a customer or enter email" autocomplete="name">
                <datalist id="history-customer-name-options">
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->name }}" data-customer-email="{{ $customer->email }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="col-md-5">
                <label for="history-email" class="form-label">Customer email</label>
                <input id="history-email" name="email" list="history-customer-email-options" data-customer-email type="email" class="form-control" placeholder="customer@example.com" maxlength="255" autocomplete="email" required>
                <datalist id="history-customer-email-options">
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->email }}" data-customer-name="{{ $customer->name }}"></option>
                    @endforeach
                </datalist>
                <div class="invalid-feedback">Enter a valid customer email.</div>
            </div>
            <div class="col-md-auto d-grid">
                <button id="find-orders-button" type="submit" class="btn btn-success">Find orders</button>
            </div>
        </form>
    </div>
</section>
<div id="history-status" aria-live="polite"></div>
<div id="order-history-results" aria-live="polite"></div>
@endsection
