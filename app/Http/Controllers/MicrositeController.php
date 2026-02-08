<?php

namespace App\Http\Controllers;

use App\Models\Campaigns;
use App\Models\Outlets;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class MicrositeController extends Controller
{
    public function index(Request $request)
    {
        $utmName = $request->query('utm_name');
        $utmCode = $request->query('utm_code');
        $utmTitle = $request->query('utm_title');
        $utmTerm = $request->query('utm_term');

        if (! $utmName || ! $utmCode || ! $utmTitle || ! $utmTerm) {
            abort(404);
        }

        $dates = explode('_', $utmTerm, 2);
        if (count($dates) !== 2) {
            abort(404);
        }

        try {
            $start = Carbon::createFromFormat('dMY', $dates[0])->startOfDay();
            $end = Carbon::createFromFormat('dMY', $dates[1])->endOfDay();
        } catch (\Throwable $e) {
            abort(404);
        }

        $campaign = Campaigns::query()
            ->where('campaign_name', $utmName)
            ->where('campaign_code', $utmCode)
            ->where('campaign_title', $utmTitle)
            ->whereDate('start_date', '<=', $start)
            ->whereDate('end_date', '>=', $end)
            ->first();

        if (! $campaign) {
            abort(404);
        }

        // dd(Storage::disk('public')->url($campaign->image));
        return view('index', [
            'campaign' => $campaign,
            'logoUrl' => $campaign?->logo
                ? Storage::disk('public')->url($campaign->logo)
                : '',
            'imageUrl' => $campaign?->image
                ? Storage::disk('public')->url($campaign->image)
                : '',
            'countdownIso' => $campaign?->end_date
                ? $campaign->end_date->endOfDay()->toIso8601String()
                : null,
        ]);
    }

    public function checkOutlet(Request $request)
    {
        $data = $request->validate([
            'outlet_code' => ['required', 'string', 'max:20'],
        ]);

        $outlet = Outlets::query()
            ->where('outlet_code', $data['outlet_code'])
            ->first();

        if (! $outlet) {
            return response()->json([
                'success' => false,
                'message' => 'Kode outlet tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'outlet_name' => $outlet->outlet_name,
            'voucher_code' => $outlet->voucher_code,
        ]);
    }
}
