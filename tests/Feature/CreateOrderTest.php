<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Jobs\SendOrderConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_and_calculates_all_totals_on_the_server(): void
    {
        Queue::fake();

        $firstProduct = Product::factory()->create([
            'price' => '10.25',
            'tax_percentage' => '10.00',
            'stock' => 5,
        ]);
        $secondProduct = Product::factory()->create([
            'price' => '5.00',
            'tax_percentage' => '10.00',
            'stock' => 5,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Ramkumar',
            'customer_email' => 'ram@example.com',
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2],
                ['product_id' => $secondProduct->id, 'quantity' => 1],
            ],
            'grand_total' => 0,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.subtotal', '25.50')
            ->assertJsonPath('data.tax', '2.55')
            ->assertJsonPath('data.grand_total', '28.05')
            ->assertJsonPath('data.items.0.subtotal', '20.50')
            ->assertJsonPath('data.items.0.tax', '2.05')
            ->assertJsonPath('data.items.0.total', '22.55');

        $this->assertDatabaseHas('products', ['id' => $firstProduct->id, 'stock' => 3]);
        $this->assertDatabaseHas('products', ['id' => $secondProduct->id, 'stock' => 4]);
        $this->assertDatabaseHas('customers', [
            'name' => 'Ramkumar',
            'email' => 'ram@example.com',
        ]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        Queue::assertPushed(SendOrderConfirmation::class, fn (SendOrderConfirmation $job): bool =>
            $job->order->getKey() === $response->json('data.id')
        );
    }

    public function test_it_reuses_an_existing_customer_with_the_same_email(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create([
            'name' => 'Existing Customer',
            'email' => 'ram@example.com',
        ]);
        $product = Product::factory()->create(['stock' => 3]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'New Request Name',
            'customer_email' => 'ram@example.com',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.customer.name', 'Existing Customer');

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
        ]);
        Queue::assertPushed(SendOrderConfirmation::class);
    }

    public function test_it_returns_conflict_and_rolls_back_when_stock_is_insufficient(): void
    {
        Queue::fake();

        $availableProduct = Product::factory()->create(['stock' => 5]);
        $limitedProduct = Product::factory()->create(['stock' => 1]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Ramkumar',
            'customer_email' => 'ram@example.com',
            'items' => [
                ['product_id' => $availableProduct->id, 'quantity' => 2],
                ['product_id' => $limitedProduct->id, 'quantity' => 2],
            ],
        ])
            ->assertConflict()
            ->assertJsonPath('errors.items.0.product_id', $limitedProduct->id)
            ->assertJsonPath('errors.items.0.available', 1)
            ->assertJsonPath('errors.items.0.requested', 2);

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('products', ['id' => $availableProduct->id, 'stock' => 5]);
        $this->assertDatabaseHas('products', ['id' => $limitedProduct->id, 'stock' => 1]);
        Queue::assertNotPushed(SendOrderConfirmation::class);
    }

    public function test_it_validates_customer_and_order_items(): void
    {
        $this->postJson('/api/orders', [
            'customer_name' => '',
            'customer_email' => 'not-an-email',
            'items' => [
                ['product_id' => 99999, 'quantity' => 0],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_name',
                'customer_email',
                'items.0.product_id',
                'items.0.quantity',
            ]);

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_confirmation_job_logs_order_and_customer_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'ram@example.com']);
        $order = Order::create([
            'customer_id' => $customer->id,
            'subtotal' => '10.00',
            'tax' => '1.00',
            'grand_total' => '11.00',
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with("Order confirmation simulated for order #{$order->id} to ram@example.com");

        (new SendOrderConfirmation($order))->handle();
    }
}
