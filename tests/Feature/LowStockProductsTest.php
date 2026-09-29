<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_products_strictly_below_the_requested_threshold(): void
    {
        Product::factory()->create([
            'name' => 'Low Stock Mouse',
            'code' => 'MOUSE-LOW',
            'price' => '29.99',
            'tax_percentage' => '18.00',
            'stock' => 2,
        ]);
        Product::factory()->create([
            'name' => 'Low Stock Keyboard',
            'code' => 'KEYBOARD-LOW',
            'price' => '79.99',
            'tax_percentage' => '18.00',
            'stock' => 4,
        ]);
        Product::factory()->create(['stock' => 5]);
        Product::factory()->create(['stock' => 10]);

        $this->get('/api/products/low-stock?threshold=5')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Low Stock Mouse')
            ->assertJsonPath('data.0.code', 'MOUSE-LOW')
            ->assertJsonPath('data.0.price', '29.99')
            ->assertJsonPath('data.0.tax_percentage', '18.00')
            ->assertJsonPath('data.0.stock', 2)
            ->assertJsonPath('data.1.stock', 4);
    }

    public function test_it_uses_the_requested_threshold_instead_of_a_fixed_value(): void
    {
        Product::factory()->create(['name' => 'Below Three', 'stock' => 2]);
        Product::factory()->create(['name' => 'At Three', 'stock' => 3]);
        Product::factory()->create(['name' => 'Above Three', 'stock' => 4]);

        $this->getJson('/api/products/low-stock?threshold=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Below Three')
            ->assertJsonPath('data.0.stock', 2);
    }

    public function test_it_returns_json_validation_errors_for_missing_or_invalid_threshold(): void
    {
        $this->get('/api/products/low-stock')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('threshold');

        $this->get('/api/products/low-stock?threshold=abc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('threshold');

        $this->get('/api/products/low-stock?threshold=-1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('threshold');
    }
}
