<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Laptop', 'code' => 'ELEC-LAP-001', 'price' => 1299.99, 'tax_percentage' => 18, 'stock' => 12],
            ['name' => 'Keyboard', 'code' => 'ELEC-KEY-001', 'price' => 79.99, 'tax_percentage' => 18, 'stock' => 3],
            ['name' => 'Mouse', 'code' => 'ELEC-MOU-001', 'price' => 29.99, 'tax_percentage' => 18, 'stock' => 25],
            ['name' => 'Monitor', 'code' => 'ELEC-MON-001', 'price' => 349.99, 'tax_percentage' => 18, 'stock' => 2],
            ['name' => 'Headset', 'code' => 'ELEC-HED-001', 'price' => 89.99, 'tax_percentage' => 18, 'stock' => 4],
            ['name' => 'Webcam', 'code' => 'ELEC-WEB-001', 'price' => 59.99, 'tax_percentage' => 18, 'stock' => 8],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['code' => $product['code']], $product);
        }

        Product::factory()->count(4)->create();
    }
}
