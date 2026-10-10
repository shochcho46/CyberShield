<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingLinkVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_link_id',
        'ip_address',
        'country',
        'country_code',
        'region_name',
        'city',
        'zip_code',
        'latitude',
        'longitude',
        'timezone',
        'isp',
        'organization',
        'device_type',
        'device_model',
        'platform',
        'platform_version',
        'browser',
        'browser_version',
        'is_robot',
        'referer',
        'user_agent',
    ];

    protected $casts = [
        'is_robot' => 'boolean',
    ];

    public function trackingLink(): BelongsTo
    {
        return $this->belongsTo(TrackingLink::class);
    }

    /**
     * Whether this visit's IP is on a private/reserved range, meaning
     * location/ISP could never be resolved for it (not a lookup failure).
     */
    public function isPrivateIp(): bool
    {
        if (empty($this->ip_address)) {
            return false;
        }

        return filter_var(
            $this->ip_address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
