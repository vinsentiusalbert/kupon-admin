<?php

namespace App\Models;

use App\Models\Campaigns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Locations extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'campaign_id',
        'name',
        'addresss',
        'maps',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaigns::class, 'campaign_id');
    }
}
