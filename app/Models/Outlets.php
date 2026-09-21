<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlets extends Model
{
    protected $table = 'outlets';

    protected $fillable = [
        'campaign_id',
        'outlet_name',
        'outlet_code',
        'voucher_code',
        'created_by',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaigns::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(OutletVoucher::class, 'outlet_id');
    }

    public function availableVouchers(): HasMany
    {
        return $this->vouchers()->whereNull('redeemed_at');
    }
}
