<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    protected $fillable = ['original_url', 'code', 'expires_at'];

    protected $appends = ['short_url', 'is_expired'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected function shortUrl(): Attribute
    {
        return Attribute::get(fn () => rtrim(config('app.url'), '/').'/'.$this->code);
    }

    protected function isExpired(): Attribute
    {
        return Attribute::get(fn () => $this->expires_at?->isPast() ?? false);
    }

    public static function generateCode(int $length = 6): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($alphabet) - 1;

        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[random_int(0, $max)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(LinkClick::class);
    }
}
