<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('phone_outlet_code', 20)->nullable();
        });

        Schema::create('phone_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('phone_number', 16);
            $table->string('outlet_code', 20);
            $table->timestamp('redeemed_at');
            $table->timestamps();
            $table->unique(['campaign_id', 'phone_number']);
            $table->index('redeemed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_redemptions');
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('phone_outlet_code');
        });
    }
};
