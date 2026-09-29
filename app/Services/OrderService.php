<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadStage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private LeadService $leads)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Order
    {
        $rows = $data['items'] ?? [];
        if (! is_array($rows) || $rows === []) {
            throw ValidationException::withMessages([
                'items' => 'Cần ít nhất một khóa học.',
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $rows) {
            $productIds = collect($rows)->pluck('product_id')->filter()->unique()->all();
            $products = Product::query()->whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');

            $prepared = [];
            $subtotal = 0;
            $total = 0;

            foreach ($rows as $index => $row) {
                $product = $products->get((int) ($row['product_id'] ?? 0));
                if (! $product) {
                    throw ValidationException::withMessages([
                        "items.$index.product_id" => 'Khóa học không hợp lệ.',
                    ]);
                }

                $quantity = max(1, (int) ($row['quantity'] ?? 1));
                $discount = max(0, min(100, (float) ($row['discount_percent'] ?? 0)));
                $lineSubtotal = $product->price * $quantity;
                $lineTotal = (int) round($lineSubtotal * (1 - ($discount / 100)));

                $prepared[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'discount_percent' => $discount,
                    'line_total' => $lineTotal,
                ];
                $subtotal += $lineSubtotal;
                $total += $lineTotal;
            }

            $lead = ! empty($data['lead_id'])
                ? Lead::query()->visibleTo($actor)->find((int) $data['lead_id'])
                : null;

            $ownerId = $actor->isSale()
                ? $actor->id
                : ($data['owner_id'] ?? $lead?->owner_id ?? $actor->id);

            $order = Order::query()->create([
                'lead_id' => $lead?->id,
                'customer_name' => trim((string) ($data['customer_name'] ?? $lead?->name ?? '')) ?: ($lead?->name ?? 'Khách hàng'),
                'customer_phone' => $data['customer_phone'] ?? $lead?->phone,
                'source_id' => $data['source_id'] ?? $lead?->source_id,
                'owner_id' => $ownerId,
                'created_by' => $actor->id,
                'subtotal' => $subtotal,
                'total' => $total,
                'status' => Order::STATUS_CONFIRMED,
                'note' => $data['note'] ?? null,
            ]);

            $order->code = Order::assignCode($order);
            $order->save();

            foreach ($prepared as $item) {
                $order->items()->create($item);
            }

            if ($lead) {
                LeadActivity::query()->create([
                    'lead_id' => $lead->id,
                    'user_id' => $actor->id,
                    'type' => 'note',
                    'content' => 'Tạo đơn hàng nội bộ '.$order->code.' · '.number_format($order->total, 0, ',', '.').' đ',
                ]);

                $paidStage = LeadStage::query()->where('slug', 'l6')->first();
                if ($paidStage && (int) $lead->stage_id !== (int) $paidStage->id) {
                    $this->leads->changeStage($lead, (int) $paidStage->id, $actor, 'Tạo đơn '.$order->code);
                }
            }

            return $order->fresh(['items', 'source', 'owner', 'lead']);
        });
    }
}
