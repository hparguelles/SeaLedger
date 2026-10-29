<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    public const PENDING = 'Pending';
    public const CONFIRMED = 'Confirmed';
    public const OUT = 'Out for delivery';
    public const DELIVERED = 'Delivered';
    public const CANCELLED = 'Cancelled';

    protected $fillable = [
        'customer_name',
        'contact',
        'address',
        'amount',
        'status',
        'estimated_at',
        'notes',
        'delivered_at',
        'proof_path',
        'proof_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'estimated_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /** Orders that still need work (not delivered or cancelled). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [self::PENDING, self::CONFIRMED, self::OUT]);
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, [self::DELIVERED, self::CANCELLED], true);
    }

    public function getProofUrlAttribute(): ?string
    {
        return $this->proof_path ? asset('storage/'.$this->proof_path) : null;
    }
}
