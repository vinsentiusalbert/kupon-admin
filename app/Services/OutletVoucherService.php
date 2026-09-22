<?php

namespace App\Services;

use App\Models\Campaigns;
use App\Models\Outlets;
use App\Models\OutletVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Random\Engine\Secure;
use Random\Randomizer;

class OutletVoucherService
{
    public const MAX_VOUCHERS = 90000;

    public function createOutlet(array $data): Outlets
    {
        Validator::make($data, [
            'voucher_quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_VOUCHERS],
        ])->validate();

        $quantity = (int) $data['voucher_quantity'];
        unset($data['voucher_quantity']);

        return DB::transaction(function () use ($data, $quantity): Outlets {
            Campaigns::query()->lockForUpdate()->findOrFail($data['campaign_id']);
            $codes = $this->generateCodes($data['campaign_id'], $quantity);
            // Retained for compatibility with the original schema; redemption uses vouchers only.
            $data['voucher_code'] = (string) $codes[0];
            $outlet = Outlets::query()->create($data);
            $this->insertVouchers($outlet, $codes);

            return $outlet;
        });
    }

    public function addVouchers(Outlets $outlet, array $data): void
    {
        DB::transaction(function () use ($outlet, $data): void {
            Campaigns::query()->lockForUpdate()->findOrFail($outlet->campaign_id);
            $lockedOutlet = Outlets::query()->lockForUpdate()->findOrFail($outlet->id);
            $codes = $this->generateCodes($lockedOutlet->campaign_id, $data['voucher_quantity'] ?? null);

            $this->insertVouchers($lockedOutlet, $codes);
        }, attempts: 5);
    }

    private function generateCodes(int|string $campaignId, mixed $quantity): array
    {
        // Include used vouchers and every outlet in the campaign to keep lookup unambiguous.
        $existingCodes = OutletVoucher::query()
            ->whereHas('outlet', fn ($query) => $query->where('campaign_id', $campaignId))
            ->pluck('code')->all();
        $availableCodes = array_values(array_diff(range(10000, 99999), $existingCodes));
        $remaining = count($availableCodes);

        Validator::make(['voucher_quantity' => $quantity], [
            'voucher_quantity' => ['required', 'integer', 'min:1', 'max:'.$remaining],
        ], [
            'voucher_quantity.max' => "Maksimal {$remaining} kode voucher baru dapat ditambahkan pada campaign ini.",
        ])->validate();

        return array_slice((new Randomizer(new Secure))->shuffleArray($availableCodes), 0, (int) $quantity);
    }

    public function resetRedemption(Outlets $outlet, OutletVoucher $voucher): void
    {
        DB::transaction(function () use ($outlet, $voucher): void {
            $lockedOutlet = Outlets::query()->lockForUpdate()->findOrFail($outlet->id);
            $lockedOutlet->vouchers()->findOrFail($voucher->id)->update(['redeemed_at' => null]);
        }, attempts: 5);
    }

    private function insertVouchers(Outlets $outlet, array $codes): void
    {
        $now = now();

        foreach (array_chunk($codes, 500) as $chunk) {
            $outlet->vouchers()->insert(array_map(fn (int $code): array => [
                'outlet_id' => $outlet->id,
                'code' => (string) $code,
                'redeemed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk));
        }
    }

    public function redeem(Outlets $outlet, string $code): ?OutletVoucher
    {
        return DB::transaction(function () use ($outlet, $code): ?OutletVoucher {
            // Serialize redemptions for the same outlet so concurrent requests cannot reuse a code.
            $lockedOutlet = Outlets::query()->lockForUpdate()->findOrFail($outlet->id);
            $voucher = $lockedOutlet->availableVouchers()->where('code', $code)->lockForUpdate()->first();

            if (! $voucher) {
                return null;
            }

            $voucher->update(['redeemed_at' => now()]);

            return $voucher;
        }, attempts: 5);
    }
}
