<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestockPlan extends Model
{
    protected $fillable = [
        'title',
        'plan_date',
        'plan_time',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestockPlanItem::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'Pending');
    }

    public function getTimeLabelAttribute(): string
    {
        return substr($this->plan_time, 0, 5);
    }
}
