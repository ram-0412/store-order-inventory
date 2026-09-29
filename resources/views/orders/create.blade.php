@extends('layouts.app')

@section('title', 'Create order')

@section('content')
<div class="page-heading mb-4">
    <p class="eyebrow mb-2">SALES</p>
    <h1 class="h2 mb-1">Create order</h1>
</div>
<div id="order-status" aria-live="polite"></div>
<form id="create-order-form" class="card content-card needs-validation" novalidate>
    <div class="card-body p-3 p-lg-4">
        <h2 class="h6 mb-3">Customer</h2>
        <div class="row g-3 mb-4" data-customer-autofill>
            <div class="col-md-6">
                <label for="customer-name" class="form-label">Customer Name</label>
                <input id="customer-name" name="customer_name" list="customer-name-options" data-customer-name class="form-control" maxlength="255" autocomplete="name" required>
                <datalist id="customer-name-options">
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->name }}" data-customer-email="{{ $customer->email }}"></option>
                    @endforeach
                </datalist>
                <div class="invalid-feedback">Enter the customer's name.</div>
            </div>
            <div class="col-md-6">
                <label for="customer-email" class="form-label">Customer Email</label>
                <input id="customer-email" name="customer_email" list="customer-email-options" data-customer-email class="form-control" type="email" maxlength="255" autocomplete="email" required>
                <datalist id="customer-email-options">
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->email }}" data-customer-name="{{ $customer->name }}"></option>
                    @endforeach
                </datalist>
                <div class="invalid-feedback">Enter a valid email address.</div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Items</h2>
            <button id="add-order-item" type="button" class="btn btn-sm btn-outline-success">Add product</button>
        </div>
        <div id="order-items"></div>

        <div class="row justify-content-end mb-4">
            <div class="col-md-6 col-lg-5">
                <dl class="order-totals mb-0">
                    <div><dt>Subtotal</dt><dd id="order-subtotal">$0.00</dd></div>
                    <div><dt>Tax</dt><dd id="order-tax">$0.00</dd></div>
                    <div class="grand-total"><dt>Grand Total</dt><dd id="order-grand-total">$0.00</dd></div>
                </dl>
                <p class="small text-secondary text-end mb-0">Totals are estimates; the server calculates the final amounts.</p>
            </div>
        </div>

        @if ($products->where('stock', '>', 0)->isEmpty())
            <div class="alert alert-warning mb-3">There are no products currently in stock.</div>
        @endif

        <div class="d-flex justify-content-end border-top pt-3">
            <button id="submit-order" type="submit" class="btn btn-success px-4">Submit order</button>
        </div>
    </div>
</form>

<template id="order-item-template">
    <div class="order-line row g-2 align-items-end border-bottom pb-3 mb-3">
        <div class="col-12 col-md-4">
            <label class="form-label">Product</label>
            <select class="form-select product-select" required>
                <option value="">Select a product</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-tax="{{ $product->tax_percentage }}" @disabled($product->stock < 1)>
                        {{ $product->name }} · {{ $product->code }} · ${{ number_format((float) $product->price, 2) }} · {{ $product->stock }} in stock
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback">Choose a product.</div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label">Quantity</label>
            <input type="number" class="form-control quantity-input" min="1" step="1" value="1" required>
            <div class="invalid-feedback">Enter a quantity of at least one.</div>
        </div>
        <div class="col-6 col-md-1">
            <span class="form-label d-block">Tax %</span>
            <span class="line-tax-rate text-secondary">—</span>
        </div>
        <div class="col-6 col-md-2">
            <span class="form-label d-block">Unit Price</span>
            <span class="line-unit-price">—</span>
        </div>
        <div class="col-6 col-md-2">
            <span class="form-label d-block">Subtotal</span>
            <span class="line-subtotal">$0.00</span>
        </div>
        <div class="col-12 col-md-1 d-grid">
            <button type="button" class="btn btn-outline-secondary remove-order-item" aria-label="Remove product line">Remove</button>
        </div>
    </div>
</template>
@endsection
