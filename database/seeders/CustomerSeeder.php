<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            ['name' => 'Olivia Martinez', 'email' => 'olivia.martinez@example.com'],
            ['name' => 'James Chen', 'email' => 'james.chen@example.com'],
            ['name' => 'Aisha Patel', 'email' => 'aisha.patel@example.com'],
            ['name' => 'Noah Williams', 'email' => 'noah.williams@example.com'],
            ['name' => 'Sofia Thompson', 'email' => 'sofia.thompson@example.com'],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['email' => $customer['email']], $customer);
        }

        Customer::factory()->count(5)->create();
    }
}
