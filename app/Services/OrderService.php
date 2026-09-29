<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @param array{customer_name: string, customer_email: string, items: array<int, array{product_id: int, quantity: int}>} $data
     */
    public function create(array $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $items = collect($data['items']);
            $productIds = $items->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products are no longer available.',
                ]);
            }

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);

                if ($product->stock < $item['quantity']) {
                    throw new InsufficientStockException(
                        (int) $product->id,
                        (int) $product->stock,
                        (int) $item['quantity'],
                    );
                }
            }

            $customer = Customer::firstOrCreate(
                ['email' => $data['customer_email']],
                ['name' => $data['customer_name']],
            );

            $orderLines = [];
            $subtotalCents = 0;
            $taxCents = 0;

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPriceCents = (int) round((float) $product->price * 100, 0, PHP_ROUND_HALF_UP);
                $lineSubtotalCents = $unitPriceCents * $quantity;
                $lineTaxCents = (int) round(
                    $lineSubtotalCents * (float) $product->tax_percentage / 100,
                    0,
                    PHP_ROUND_HALF_UP,
                );

                $orderLines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $this->formatMoney($unitPriceCents),
                    'tax_percentage' => $product->tax_percentage,
                    'subtotal' => $this->formatMoney($lineSubtotalCents),
                    'tax' => $this->formatMoney($lineTaxCents),
                    'total' => $this->formatMoney($lineSubtotalCents + $lineTaxCents),
                ];

                $subtotalCents += $lineSubtotalCents;
                $taxCents += $lineTaxCents;
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $this->formatMoney($subtotalCents),
                'tax' => $this->formatMoney($taxCents),
                'grand_total' => $this->formatMoney($subtotalCents + $taxCents),
            ]);

            foreach ($orderLines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_percentage' => $line['tax_percentage'],
                    'subtotal' => $line['subtotal'],
                    'tax' => $line['tax'],
                    'total' => $line['total'],
                ]);

                $line['product']->decrement('stock', $line['quantity']);
            }

            return $order->load(['customer', 'items.product']);
        });

        SendOrderConfirmation::dispatch($order);

        return $order;
    }

    private function formatMoney(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
