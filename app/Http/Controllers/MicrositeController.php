<?php

namespace App\Http\Controllers;

use App\Models\Campaigns;
use App\Models\Locations;
use App\Models\OutletVoucher;
use App\Models\PhoneRedemption;
use App\Services\OutletVoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
            'locations' => Locations::query()
                ->where('campaign_id', $campaign->id)
                ->orderBy('name')
                ->get(),
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

    public function redeemPhone(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^\+628[0-9]{8,11}$/'],
            'campaign_id' => ['required', 'integer'],
        ], [
            'phone_number.required' => 'Silakan masukkan nomor HP.',
            'phone_number.regex' => 'Gunakan nomor HP dengan awalan +628, contoh +6281234567890.',
        ]);

        return DB::transaction(function () use ($data) {
            $campaign = Campaigns::query()->lockForUpdate()->find($data['campaign_id']);

            if (! $campaign) {
                return response()->json(['success' => false, 'message' => 'Campaign tidak ditemukan.'], 404);
            }

            if (blank($campaign->phone_outlet_code)) {
                return response()->json(['success' => false, 'message' => 'Redeem nomor HP belum tersedia untuk campaign ini.'], 409);
            }

            // Keep the original code and timestamp when the same request is submitted again.
            $redemption = PhoneRedemption::query()->firstOrCreate([
                'campaign_id' => $campaign->id,
                'phone_number' => $data['phone_number'],
            ], [
                'outlet_code' => $campaign->phone_outlet_code,
                'redeemed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'outlet_code' => $redemption->outlet_code,
                'redeemed_at' => $redemption->redeemed_at->toIso8601String(),
            ]);
        }, attempts: 5);
    }

    public function checkOutlet(Request $request, OutletVoucherService $vouchers)
    {
        $data = $request->validate([
            'voucher_code' => ['required', 'string', 'regex:/^[0-9]{5}$/'],
            'campaign_id' => ['required', 'string'],
        ], [
            'voucher_code.required' => 'Silakan masukkan kode voucher.',
            'voucher_code.regex' => 'Kode voucher harus terdiri dari 5 digit angka.',
        ]);

        $matches = OutletVoucher::query()
            ->where('code', $data['voucher_code'])
            ->whereHas('outlet', fn ($query) => $query->where('campaign_id', $data['campaign_id']))
            ->with('outlet')
            ->limit(2)
            ->get();

        if ($matches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak ditemukan.',
            ], 404);
        }

        if ($matches->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher terdaftar di beberapa outlet. Silakan hubungi admin.',
            ], 409);
        }

        $outlet = $matches->first()->outlet;
        $voucher = $vouchers->redeem($outlet, $data['voucher_code']);

        if (! $voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher sudah digunakan.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'outlet_name' => $outlet->outlet_name,
            'outlet_code' => $outlet->outlet_code,
        ]);
    }
}
