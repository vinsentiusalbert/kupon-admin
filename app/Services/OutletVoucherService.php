<?php

namespace App\Services;

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

        // Shuffle the five-digit code space to guarantee unique codes within this outlet.
        $codes = array_slice((new Randomizer(new Secure))->shuffleArray(range(10000, 99999)), 0, $quantity);

        return DB::transaction(function () use ($data, $codes): Outlets {
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
            $lockedOutlet = Outlets::query()->lockForUpdate()->findOrFail($outlet->id);
            // Include redeemed codes so newly generated vouchers never reuse an existing code.
            $existingCodes = $lockedOutlet->vouchers()->pluck('code')->all();
            $availableCodes = array_values(array_diff(range(10000, 99999), $existingCodes));
            $remaining = count($availableCodes);

            Validator::make($data, [
                'voucher_quantity' => ['required', 'integer', 'min:1', 'max:'.$remaining],
            ], [
                'voucher_quantity.max' => "Maksimal {$remaining} kode voucher baru dapat ditambahkan ke outlet ini.",
            ])->validate();

            $codes = array_slice(
                (new Randomizer(new Secure))->shuffleArray($availableCodes),
                0,
                (int) $data['voucher_quantity'],
            );

            $this->insertVouchers($lockedOutlet, $codes);
        }, attempts: 5);
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

    public function redeem(Outlets $outlet): ?OutletVoucher
    {
        return DB::transaction(function () use ($outlet): ?OutletVoucher {
            // Serialize redemptions for the same outlet so concurrent requests cannot reuse a code.
            $lockedOutlet = Outlets::query()->lockForUpdate()->findOrFail($outlet->id);
            $voucher = $lockedOutlet->availableVouchers()->inRandomOrder()->lockForUpdate()->first();

            if (! $voucher) {
                return null;
            }

            $voucher->update(['redeemed_at' => now()]);

            return $voucher;
        }, attempts: 5);
    }
}
