<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('code');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->unique(['outlet_id', 'code']);
            $table->index(['outlet_id', 'redeemed_at']);
        });

        // Existing outlets start with their original voucher, with no recorded redemption.
        DB::table('outlets')->orderBy('id')->chunkById(500, function ($outlets) {
            $vouchers = [];

            foreach ($outlets as $outlet) {
                if ($outlet->voucher_code === null || $outlet->voucher_code === '') {
                    continue;
                }

                $vouchers[] = [
                    'outlet_id' => $outlet->id,
                    'code' => $outlet->voucher_code,
                    'redeemed_at' => null,
                    'created_at' => $outlet->created_at ?? now(),
                    'updated_at' => $outlet->updated_at ?? now(),
                ];
            }

            if ($vouchers !== []) {
                DB::table('outlet_vouchers')->insert($vouchers);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_vouchers');
    }
};
