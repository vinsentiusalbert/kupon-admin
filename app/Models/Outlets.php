<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outlets extends Model
{
    protected $table = 'outlets';

    protected $fillable = [
        'campaign_id',
        'outlet_name',
        'outlet_code',
        'voucher_code',
    ];
}
