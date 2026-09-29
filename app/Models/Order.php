<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Order extends Model
{
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'code',
        'lead_id',
        'customer_name',
        'customer_phone',
        'source_id',
        'owner_id',
        'created_by',
        'subtotal',
        'total',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'total' => 'integer',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('owner_id', $user->id);
    }

    public function formattedTotal(): string
    {
        return number_format($this->total, 0, ',', '.').' đ';
    }

    public function productNames(): string
    {
        return $this->items
            ->map(fn (OrderItem $item) => $item->product_name.($item->quantity > 1 ? ' x'.$item->quantity : ''))
            ->implode(', ') ?: '—';
    }

    public static function assignCode(self $order): string
    {
        $year = ($order->created_at ?? Carbon::now())->format('Y');

        return 'DH-'.$year.$order->id;
    }
}
