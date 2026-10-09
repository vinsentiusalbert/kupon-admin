<?php

namespace App\Services;

use App\Models\Campaigns;
use App\Models\PhoneRedemption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PhoneRedemptionService
{
    public function redeem(Campaigns $campaign, string $phone): PhoneRedemption
    {
        Validator::make(['phone_number' => $phone], [
            'phone_number' => ['required', 'string', 'regex:/^\+628[0-9]{8,11}$/'],
        ], [
            'phone_number.regex' => 'Masukkan nomor HP yang valid, contoh +6281234567890.',
        ])->validate();

        return DB::transaction(function () use ($campaign, $phone): PhoneRedemption {
            $campaign = Campaigns::query()->lockForUpdate()->findOrFail($campaign->id);

            if (blank($campaign->phone_outlet_code)) {
                throw ValidationException::withMessages([
                    'campaign_id' => 'Redeem nomor HP belum tersedia untuk campaign ini.',
                ]);
            }

            // Serialize redemptions across campaigns that share the same outlet code.
            Campaigns::query()->where('phone_outlet_code', $campaign->phone_outlet_code)
                ->orderBy('id')->lockForUpdate()->get();

            if (PhoneRedemption::query()->where('phone_number', $phone)
                ->where('outlet_code', $campaign->phone_outlet_code)->exists()) {
                throw ValidationException::withMessages(['phone_number' => 'Nomor ini sudah redeem.']);
            }

            $redemption = PhoneRedemption::query()->firstOrCreate([
                'campaign_id' => $campaign->id,
                'phone_number' => $phone,
            ], [
                'outlet_code' => $campaign->phone_outlet_code,
                'redeemed_at' => now(),
            ]);

            if (! $redemption->wasRecentlyCreated) {
                throw ValidationException::withMessages(['phone_number' => 'Nomor ini sudah redeem.']);
            }

            return $redemption;
        }, attempts: 5);
    }
}
