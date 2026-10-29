<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Activity extends Model
{
    protected $fillable = [
        'user_id',
        'text',
        'detail',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $text, ?string $detail = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'text' => $text,
            'detail' => $detail,
        ]);
    }
}
