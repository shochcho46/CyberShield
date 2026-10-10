<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrackingLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TrackingLinkController extends Controller
{
    public function index(): View
    {
        $trackingLinks = TrackingLink::where('admin_id', Auth::guard('admin')->id())
            ->withCount('visits')
            ->latest()
            ->get();

        return view('admin.tracking-links.index', compact('trackingLinks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'destination_url' => ['required', 'url', 'max:2048'],
        ]);

        TrackingLink::create([
            'admin_id' => Auth::guard('admin')->id(),
            'title' => $request->input('title'),
            'destination_url' => $request->input('destination_url'),
        ]);

        return redirect()
            ->route('admin.trackingLinks.index')
            ->with('success', 'Tracking link created successfully.');
    }

    public function show(TrackingLink $trackingLink, Request $request): View
    {
        abort_if($trackingLink->admin_id !== Auth::guard('admin')->id(), 403);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $visits = $trackingLink->visits()
            ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.tracking-links.show', compact('trackingLink', 'visits'));
    }

    public function destroy(TrackingLink $trackingLink): RedirectResponse
    {
        abort_if($trackingLink->admin_id !== Auth::guard('admin')->id(), 403);

        $trackingLink->delete();

        return redirect()
            ->route('admin.trackingLinks.index')
            ->with('success', 'Tracking link deleted successfully.');
    }
}
