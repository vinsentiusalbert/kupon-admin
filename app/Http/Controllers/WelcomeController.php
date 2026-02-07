<?php

namespace App\Http\Controllers;

use App\Models\Campaigns;
use Illuminate\Support\Facades\Storage;

class WelcomeController extends Controller
{
    public function index()
    {
        $campaign = Campaigns::query()
            ->latest('created_at')
            ->first();

        // dd(Storage::disk('public')->url($campaign->image));
        return view('welcome', [
            'campaign' => $campaign,
            'logoUrl' => $campaign?->logo
                ? Storage::disk('public')->url($campaign->logo)
                : '',
            'imageUrl' => $campaign?->image
                ? Storage::disk('public')->url($campaign->image)
                : '',
            'countdownIso' => $campaign?->end_date
                ? $campaign->end_date->endOfDay()->toIso8601String()
                : null,
        ]);
    }
}
