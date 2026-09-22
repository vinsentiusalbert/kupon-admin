<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaigns extends Model
{
    protected $table = 'campaigns';

    protected $fillable = [
        'campaign_name',
        'campaign_code',
        'campaign_title',
        'logo',
        'image',
        'start_date',
        'end_date',
        'created_by',
        'phone_outlet_code',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPublicUrlAttribute(): string
    {
        $query = http_build_query([
            'utm_name' => $this->campaign_name,
            'utm_code' => $this->campaign_code,
            'utm_title' => $this->campaign_title,
            'utm_term' => $this->start_date?->format('dMY').'_'.$this->end_date?->format('dMY'),
        ], encoding_type: PHP_QUERY_RFC3986);

        return url('/').'?'.$query;
    }
}
