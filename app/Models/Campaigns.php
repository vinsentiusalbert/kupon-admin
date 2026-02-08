<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
