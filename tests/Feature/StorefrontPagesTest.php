<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_storefront_pages_render(): void
    {
        foreach ([
            '/',
            '/products',
            '/customers',
            '/orders/create',
            '/orders/history',
            '/products/low-stock',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_dashboard_shows_metrics_and_only_products_below_five(): void
    {
        $customer = Customer::factory()->create();
        Product::factory()->create(['name' => 'Low Stock Keyboard', 'stock' => 2]);
        Product::factory()->create(['name' => 'Threshold Monitor', 'stock' => 5]);
        Order::create([
            'customer_id' => $customer->id,
            'subtotal' => '10.00',
            'tax' => '1.00',
            'grand_total' => '11.00',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Total Products')
            ->assertSee('Total Customers')
            ->assertSee('Total Orders')
            ->assertSee('Total Stock Items')
            ->assertSee('Low Stock Product Count')
            ->assertSee('Low Stock Keyboard')
            ->assertDontSee('Threshold Monitor');
    }

    public function test_products_page_shows_each_stock_status(): void
    {
        Product::factory()->create(['name' => 'Available Product', 'stock' => 10]);
        Product::factory()->create(['name' => 'Low Product', 'stock' => 4]);
        Product::factory()->create(['name' => 'Sold Out Product', 'stock' => 0]);

        $this->get('/products')
            ->assertOk()
            ->assertSee('In Stock')
            ->assertSee('Low Stock')
            ->assertSee('Out of Stock');
    }

    public function test_customers_page_shows_order_count_and_history_action(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'History Customer',
            'email' => 'history@example.com',
        ]);
        Order::create([
            'customer_id' => $customer->id,
            'subtotal' => '10.00',
            'tax' => '1.00',
            'grand_total' => '11.00',
        ]);

        $this->get('/customers')
            ->assertOk()
            ->assertSee('History Customer')
            ->assertSee('history@example.com')
            ->assertSee('View history')
            ->assertSee(route('orders.history', ['email' => 'history@example.com']));
    }

    public function test_product_and_customer_lists_are_paginated(): void
    {
        Product::factory()->count(16)->create();
        Customer::factory()->count(16)->create();

        $this->get('/products')->assertOk()->assertSee('Next');
        $this->get('/customers')->assertOk()->assertSee('Next');
    }

    public function test_create_order_page_has_line_pricing_and_totals(): void
    {
        Customer::factory()->create([
            'name' => 'Autocomplete Customer',
            'email' => 'autocomplete@example.com',
        ]);
        Product::factory()->create([
            'name' => 'Laptop',
            'price' => '999.99',
            'tax_percentage' => '18.00',
            'stock' => 5,
        ]);

        $this->get('/orders/create')
            ->assertOk()
            ->assertSee('Customer Name')
            ->assertSee('Customer Email')
            ->assertSee('Autocomplete Customer')
            ->assertSee('autocomplete@example.com')
            ->assertSee('Unit Price')
            ->assertSee('Tax %')
            ->assertSee('Subtotal')
            ->assertSee('Grand Total')
            ->assertSee('data-price="999.99"', false)
            ->assertSee('data-tax="18.00"', false);
    }

    public function test_low_stock_page_exposes_an_editable_search_threshold(): void
    {
        $this->get('/products/low-stock')
            ->assertOk()
            ->assertSee('Low Stock Threshold')
            ->assertSee('value="5"', false)
            ->assertSee('Search')
            ->assertSee('threshold-feedback');
    }

    public function test_order_history_page_includes_customer_autofill_options(): void
    {
        Customer::factory()->create([
            'name' => 'History Autocomplete',
            'email' => 'history-autocomplete@example.com',
        ]);

        $this->get('/orders/history')
            ->assertOk()
            ->assertSee('Customer name')
            ->assertSee('History Autocomplete')
            ->assertSee('history-autocomplete@example.com');
    }
}
