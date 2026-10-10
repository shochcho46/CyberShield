@extends('layouts.app')

@push('custome-css')

@endpush

@php
    // Cosmetic-only mapping of jenssegers/agent's internal platform key
    // names to their common display names — the raw value stays in the DB.
    $platformLabels = [
        'AndroidOS' => 'Android',
        'OS X' => 'macOS',
    ];
    $platformLabel = fn ($platform) => $platform ? ($platformLabels[$platform] ?? $platform) : '—';
@endphp

@section('content')

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">{{ $trackingLink->title }}</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.trackingLinks.index') }}">Tracking Links</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Visits</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container">

        <div class="row mb-3">
            <div class="col-12">
                <div class="input-group" style="max-width: 420px;">
                    <input type="text" class="form-control" value="{{ $trackingLink->public_url }}" readonly onclick="this.select()">
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $trackingLink->public_url }}')" title="Copy link">
                        <i class="mdi mdi-content-copy"></i>
                    </button>
                </div>
                <small class="text-muted">Destination: {{ $trackingLink->destination_url }}</small>
            </div>
        </div>

        <div class="row">
            <h1 class="mt-3">Visitor Log</h1>

            <div class="d-flex justify-content-between align-items-end mt-3 mb-3">
                <form action="{{ route('admin.trackingLinks.show', $trackingLink->id) }}" method="GET" class="d-flex align-items-end gap-2">
                    @include('components.daterange')
                    <button type="submit" class="btn btn-primary">Filter</button>
                    @if(request('from_date') || request('to_date'))
                        <a href="{{ route('admin.trackingLinks.show', $trackingLink->id) }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Time</th>
                            <th scope="col">IP Address</th>
                            <th scope="col">Location</th>
                            <th scope="col">ISP</th>
                            <th scope="col">Device</th>
                            <th scope="col">OS</th>
                            <th scope="col">Browser</th>
                            <th scope="col" class="text-center">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($visits as $index => $visit)
                            <tr>
                                <th scope="row">{{ $visits->firstItem() + $index }}</th>
                                <td>{{ $visit->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $visit->ip_address ?? '—' }}</td>
                                <td>
                                    @php $location = collect([$visit->city, $visit->region_name, $visit->country])->filter()->implode(', '); @endphp
                                    @if($location)
                                        {{ $location }}
                                    @elseif($visit->isPrivateIp())
                                        <span class="text-muted fst-italic">Private network IP</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($visit->isp)
                                        {{ $visit->isp }}
                                    @elseif($visit->isPrivateIp())
                                        <span class="text-muted fst-italic">n/a</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    {{ ucfirst($visit->device_type ?? '—') }}
                                    @if($visit->device_model)
                                        ({{ $visit->device_model }})
                                    @endif
                                </td>
                                <td>{{ $platformLabel($visit->platform) }} {{ $visit->platform_version }}</td>
                                <td>{{ $visit->browser ?? '—' }} {{ $visit->browser_version }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-info visit-details-btn"
                                        data-bs-toggle="modal" data-bs-target="#visitDetailModal"
                                        data-time="{{ $visit->created_at->format('d M Y, h:i A') }}"
                                        data-ip="{{ $visit->ip_address }}"
                                        data-isp="{{ $visit->isp }}"
                                        data-organization="{{ $visit->organization }}"
                                        data-country="{{ $visit->country }}"
                                        data-country-code="{{ $visit->country_code }}"
                                        data-region="{{ $visit->region_name }}"
                                        data-city="{{ $visit->city }}"
                                        data-zip="{{ $visit->zip_code }}"
                                        data-lat="{{ $visit->latitude }}"
                                        data-lng="{{ $visit->longitude }}"
                                        data-timezone="{{ $visit->timezone }}"
                                        data-device-type="{{ ucfirst($visit->device_type ?? '') }}"
                                        data-device-model="{{ $visit->device_model }}"
                                        data-platform="{{ trim($platformLabel($visit->platform).' '.$visit->platform_version) }}"
                                        data-browser="{{ trim($visit->browser.' '.$visit->browser_version) }}"
                                        data-is-robot="{{ $visit->is_robot ? 'Yes' : 'No' }}"
                                        data-referer="{{ $visit->referer }}"
                                        data-user-agent="{{ $visit->user_agent }}"
                                        data-is-private="{{ $visit->isPrivateIp() ? '1' : '0' }}">
                                        <i class="mdi mdi-information-outline"></i> Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No visits recorded for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex mt-3">
                    {{ $visits->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Shared Visit Detail Modal (filled in by JS per row clicked) -->
<div class="modal fade" id="visitDetailModal" tabindex="-1" aria-labelledby="visitDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visitDetailModalLabel">Visit Details &ndash; <span id="vd-time"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Network &amp; Location</h6>
                        <p id="vd-private-note" class="text-muted fst-italic small mb-2 d-none">This visit came from a private/local network IP — public geolocation services cannot resolve it.</p>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th>IP Address</th><td id="vd-ip"></td></tr>
                            <tr><th>ISP</th><td id="vd-isp"></td></tr>
                            <tr><th>Organization</th><td id="vd-organization"></td></tr>
                            <tr><th>Country</th><td id="vd-country"></td></tr>
                            <tr><th>Region</th><td id="vd-region"></td></tr>
                            <tr><th>City</th><td id="vd-city"></td></tr>
                            <tr><th>Zip Code</th><td id="vd-zip"></td></tr>
                            <tr><th>Latitude / Longitude</th><td id="vd-latlng"></td></tr>
                            <tr><th>Timezone</th><td id="vd-timezone"></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Device &amp; Software</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th>Device Type</th><td id="vd-device-type"></td></tr>
                            <tr><th>Device Model</th><td id="vd-device-model"></td></tr>
                            <tr><th>Operating System</th><td id="vd-platform"></td></tr>
                            <tr><th>Browser</th><td id="vd-browser"></td></tr>
                            <tr><th>Is Bot/Crawler</th><td id="vd-is-robot"></td></tr>
                        </table>

                        <h6 class="mt-3">Request</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><th>Referer</th><td id="vd-referer" class="text-break"></td></tr>
                        </table>
                    </div>
                    <div class="col-12 mt-2">
                        <h6>Raw User Agent</h6>
                        <code id="vd-user-agent" class="d-block text-break"></code>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('custome-js')
<script>
    (function () {
        var modal = document.getElementById('visitDetailModal');
        if (!modal) return;

        function setText(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value && value.trim() !== '' ? value : '—';
        }

        modal.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (!btn) return;

            var d = btn.dataset;

            setText('vd-time', d.time);
            setText('vd-ip', d.ip);
            setText('vd-isp', d.isp);
            setText('vd-organization', d.organization);
            setText('vd-country', d.country ? (d.country + (d.countryCode ? ' (' + d.countryCode + ')' : '')) : '');
            setText('vd-region', d.region);
            setText('vd-city', d.city);
            setText('vd-zip', d.zip);
            setText('vd-latlng', (d.lat || d.lng) ? ((d.lat || '—') + ', ' + (d.lng || '—')) : '');
            setText('vd-timezone', d.timezone);
            setText('vd-device-type', d.deviceType);
            setText('vd-device-model', d.deviceModel);
            setText('vd-platform', d.platform);
            setText('vd-browser', d.browser);
            setText('vd-is-robot', d.isRobot);
            setText('vd-referer', d.referer);
            setText('vd-user-agent', d.userAgent);

            var note = document.getElementById('vd-private-note');
            if (note) {
                note.classList.toggle('d-none', !(d.isPrivate === '1' && !d.country));
            }
        });
    })();
</script>
@endpush
