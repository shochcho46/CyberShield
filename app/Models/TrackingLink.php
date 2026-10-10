<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TrackingLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'title',
        'code',
        'destination_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $trackingLink) {
            if (empty($trackingLink->code)) {
                do {
                    $code = Str::random(8);
                } while (self::where('code', $code)->exists());

                $trackingLink->code = $code;
            }
        });
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(TrackingLinkVisit::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('track.visit', $this->code);
    }
}
