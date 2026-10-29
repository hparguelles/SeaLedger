<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'image_path',
        'price',
        'stock',
        'min_stock',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function restockPlanItems(): HasMany
    {
        return $this->hasMany(RestockPlanItem::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('is_available', true)->whereColumn('stock', '<=', 'min_stock');
    }

    public function getIsLowAttribute(): bool
    {
        return $this->is_available && $this->stock <= $this->min_stock;
    }

    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_available) {
            return 'Unavailable';
        }

        if ($this->stock <= 0) {
            return 'Sold out';
        }

        if ($this->stock <= $this->min_stock) {
            return 'Low stock';
        }

        return 'In stock';
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        // asset() follows the host the visitor is actually on, so it keeps
        // working even if APP_URL is misconfigured on the server.
        return asset('storage/'.$this->image_path);
    }

    /**
     * Store a new photo for this product and remove the previous one.
     * Pass null to just remove the current photo.
     */
    public function replaceImage(?UploadedFile $file): void
    {
        $old = $this->image_path;

        $this->image_path = $file ? $file->store('products', 'public') : null;
        $this->save();

        if ($old && $old !== $this->image_path) {
            Storage::disk('public')->delete($old);
        }
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(mb_substr($this->name, 0, 2));
    }
}
