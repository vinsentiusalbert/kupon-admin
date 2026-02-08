<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('end_date');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            if (! Schema::hasColumn('outlets', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('voucher_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });

        Schema::table('outlets', function (Blueprint $table) {
            if (Schema::hasColumn('outlets', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
