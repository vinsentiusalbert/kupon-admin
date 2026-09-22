<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneRedemption extends Model
{
    protected $fillable = ['campaign_id', 'phone_number', 'outlet_code', 'redeemed_at'];

    protected function casts(): array
    {
        return ['redeemed_at' => 'datetime'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaigns::class, 'campaign_id');
    }
}
