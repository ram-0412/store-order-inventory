<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_customers_orders_and_item_price_snapshots(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Ramkumar',
            'email' => 'ram@example.com',
        ]);
        $product = Product::factory()->create([
            'name' => 'Laptop',
            'price' => '999.99',
        ]);
        $order = $customer->orders()->create([
            'subtotal' => '20.50',
            'tax' => '2.05',
            'grand_total' => '22.55',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => '10.25',
            'tax_percentage' => '10.00',
            'subtotal' => '20.50',
            'tax' => '2.05',
            'total' => '22.55',
        ]);

        $this->getJson('/api/customers/orders?email=ram@example.com')
            ->assertOk()
            ->assertJsonPath('data.customer.name', 'Ramkumar')
            ->assertJsonPath('data.customer.email', 'ram@example.com')
            ->assertJsonPath('data.orders.0.id', $order->id)
            ->assertJsonPath('data.orders.0.subtotal', '20.50')
            ->assertJsonPath('data.orders.0.tax', '2.05')
            ->assertJsonPath('data.orders.0.grand_total', '22.55')
            ->assertJsonPath('data.orders.0.items.0.product_name', 'Laptop')
            ->assertJsonPath('data.orders.0.items.0.quantity', 2)
            ->assertJsonPath('data.orders.0.items.0.unit_price', '10.25')
            ->assertJsonPath('data.orders.0.items.0.subtotal', '20.50')
            ->assertJsonPath('data.orders.0.items.0.tax', '2.05')
            ->assertJsonPath('data.orders.0.items.0.total', '22.55');
    }

    public function test_it_returns_json_validation_errors_for_missing_or_invalid_email(): void
    {
        $this->get('/api/customers/orders')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->get('/api/customers/orders?email=not-an-email')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_it_returns_not_found_for_an_unknown_customer_email(): void
    {
        $this->getJson('/api/customers/orders?email=missing@example.com')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Customer not found.']);
    }
}
