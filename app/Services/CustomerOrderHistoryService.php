<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;

class CustomerOrderHistoryService
{
    /**
     * @return array{customer: array{id: int, name: string, email: string}, orders: array<int, array<string, mixed>>}|null
     */
    public function findByEmail(string $email): ?array
    {
        $customer = Customer::query()
            ->where('email', $email)
            ->with([
                'orders' => fn ($query) => $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->with(['items.product']),
            ])
            ->first();

        if ($customer === null) {
            return null;
        }

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'orders' => $customer->orders->map(static fn (Order $order): array => [
                'id' => $order->id,
                'order_date' => $order->created_at?->toISOString(),
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'grand_total' => $order->grand_total,
                'items' => $order->items->map(static fn (OrderItem $item): array => [
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'tax' => $item->tax,
                    'total' => $item->total,
                ])->all(),
            ])->all(),
        ];
    }
}
