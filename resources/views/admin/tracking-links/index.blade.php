@extends('layouts.app')

@push('custome-css')

@endpush

@section('content')

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Tracking Links</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tracking Links</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <h1 class="mt-3">Link List</h1>

            <div class="d-flex justify-content-between align-items-end mt-3 mb-3">
                <p class="text-muted mb-0">Create a link, share it, and see who clicks it &mdash; location, ISP, device, OS and browser are logged automatically.</p>

                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createTrackingLinkModal">
                    <i class="mdi mdi-plus-circle mdi-12px"></i> New Link
                </button>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Title</th>
                            <th scope="col">Tracking URL</th>
                            <th scope="col">Destination</th>
                            <th scope="col" class="text-center">Visits</th>
                            <th scope="col">Created</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trackingLinks as $index => $link)
                            <tr>
                                <th scope="row">{{ $index + 1 }}</th>
                                <td>{{ $link->title }}</td>
                                <td>
                                    <div class="input-group input-group-sm" style="max-width: 280px;">
                                        <input type="text" class="form-control form-control-sm" value="{{ $link->public_url }}" readonly onclick="this.select()">
                                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $link->public_url }}')" title="Copy link">
                                            <i class="mdi mdi-content-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-truncate" style="max-width: 220px;" title="{{ $link->destination_url }}">{{ $link->destination_url }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.trackingLinks.show', $link->id) }}" class="badge text-bg-primary text-decoration-none">
                                        {{ $link->visits_count }}
                                    </a>
                                </td>
                                <td>{{ $link->created_at->format('d M Y') }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.trackingLinks.show', $link->id) }}" class="btn btn-sm btn-outline-info">
                                        <i class="mdi mdi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-url="{{ route('admin.trackingLinks.destroy', $link->id) }}">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No tracking links yet. Create one to get started.</td>
                            </tr>
                        @endforelse
                        @include('components.delete')
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Create Tracking Link Modal -->
<div class="modal fade" id="createTrackingLinkModal" tabindex="-1" aria-labelledby="createTrackingLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.trackingLinks.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createTrackingLinkModalLabel">New Tracking Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Campaign link" required>
                    </div>
                    <div class="mb-3">
                        <label for="destination_url" class="form-label">Destination URL</label>
                        <input type="url" name="destination_url" id="destination_url" class="form-control" placeholder="https://example.com" required>
                        <div class="form-text">Visitors are redirected here after their info is logged.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('custome-js')

@endpush
