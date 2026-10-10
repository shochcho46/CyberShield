<?php

namespace App\Http\Controllers;

use App\Models\TrackingLink;
use App\Models\TrackingLinkVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class TrackingRedirectController extends Controller
{
    /**
     * Log the visitor's info then redirect them to the link's destination.
     */
    public function handle(string $code): RedirectResponse
    {
        $trackingLink = TrackingLink::where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        $ip = request()->ip();
        $userAgent = (string) request()->userAgent();

        $geo = $this->resolveGeo($ip);
        $device = $this->resolveDevice($userAgent);

        TrackingLinkVisit::create(array_merge(
            [
                'tracking_link_id' => $trackingLink->id,
                'ip_address' => $ip,
                'referer' => request()->headers->get('referer'),
                'user_agent' => $userAgent,
            ],
            $geo,
            $device
        ));

        return redirect()->away($trackingLink->destination_url);
    }

    /**
     * Resolve IP geolocation + ISP/org info. Never throws — a lookup
     * failure (e.g. a local/private IP) must not block the redirect.
     */
    private function resolveGeo(?string $ip): array
    {
        $geo = [
            'country' => null,
            'country_code' => null,
            'region_name' => null,
            'city' => null,
            'zip_code' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'isp' => null,
            'organization' => null,
        ];

        if (empty($ip) || $this->isPrivateOrReservedIp($ip)) {
            // Private/reserved IPs (e.g. a phone on the same LAN as this
            // dev server) can never be geolocated by a public IP API —
            // skip the network calls instead of letting them fail.
            return $geo;
        }

        try {
            $position = Location::get($ip);

            if ($position && ! $position->isEmpty()) {
                $geo['country'] = $position->countryName;
                $geo['country_code'] = $position->countryCode;
                $geo['region_name'] = $position->regionName;
                $geo['city'] = $position->cityName;
                $geo['zip_code'] = $position->zipCode;
                $geo['latitude'] = $position->latitude;
                $geo['longitude'] = $position->longitude;
                $geo['timezone'] = $position->timezone;
            }
        } catch (\Throwable $e) {
            Log::warning('TrackingLink geolocation lookup failed: '.$e->getMessage());
        }

        try {
            // The default ip-api.com driver doesn't map isp/org onto the
            // Position object, so fetch those two fields directly.
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'isp,org',
            ]);

            if ($response->ok()) {
                $geo['isp'] = $response->json('isp');
                $geo['organization'] = $response->json('org');
            }
        } catch (\Throwable $e) {
            Log::warning('TrackingLink ISP lookup failed: '.$e->getMessage());
        }

        return $geo;
    }

    /**
     * Resolve device/OS/browser info from the User-Agent string.
     */
    private function resolveDevice(string $userAgent): array
    {
        $device = [
            'device_type' => null,
            'device_model' => null,
            'platform' => null,
            'platform_version' => null,
            'browser' => null,
            'browser_version' => null,
            'is_robot' => false,
        ];

        try {
            $agent = new Agent;
            $agent->setUserAgent($userAgent);

            $device['device_type'] = $agent->deviceType();
            $device['device_model'] = $this->resolveDeviceModel($agent, $userAgent);
            $device['platform'] = $agent->platform() ?: null;
            $device['platform_version'] = $agent->version($agent->platform()) ?: null;
            $device['browser'] = $agent->browser() ?: null;
            $device['browser_version'] = $agent->version($agent->browser()) ?: null;
            $device['is_robot'] = $agent->isRobot();
        } catch (\Throwable $e) {
            Log::warning('TrackingLink user-agent parsing failed: '.$e->getMessage());
        }

        return $device;
    }

    /**
     * jenssegers/agent's device() matches phone/tablet brand regexes
     * merged with its internal "utility" regexes (WebKit, Gecko, Trident,
     * etc.) — on most modern Android UAs no brand regex matches, so it
     * falls through and wrongly returns the rendering-engine name instead
     * of a device model. Discard those false matches and fall back to
     * extracting the real model straight out of the raw UA string.
     */
    private function resolveDeviceModel(Agent $agent, string $userAgent): ?string
    {
        $nonModelUtilities = [
            'webkit', 'gecko', 'trident', 'presto', 'netfront',
            'opera mini', 'opera', 'ucbrowser', 'blink',
        ];

        // Android UAs look like: "... (Linux; Android 10; SM-G998B Build/...) ..."
        // — this exact model string is more specific than anything
        // Mobile_Detect's brand regexes can match, so prefer it first.
        if (preg_match('/Android[\s\/][\d.]+;\s*([^;)]+?)\s*(Build|\))/i', $userAgent, $matches)) {
            $model = trim($matches[1]);

            if ($model !== '' && strtolower($model) !== 'wv') {
                return $model;
            }
        }

        $detected = $agent->device();

        if ($detected && ! in_array(strtolower($detected), $nonModelUtilities, true)) {
            return $detected;
        }

        // iOS UAs never expose the exact hardware identifier (e.g.
        // "iPhone14,3") to the browser — "iPhone"/"iPad" is the most
        // specific model info realistically available.
        if ($agent->isiPhone()) {
            return 'iPhone';
        }

        if ($agent->isiPad()) {
            return 'iPad';
        }

        return null;
    }

    /**
     * Whether the given IP is on a private or reserved range (RFC 1918 /
     * loopback / link-local), which no public geolocation API can resolve.
     */
    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
