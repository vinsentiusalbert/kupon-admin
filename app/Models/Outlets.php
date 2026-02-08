<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
