<?php

namespace Tests\Unit;

use App\Models\Campaigns;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CampaignPublicUrlTest extends TestCase
{
    public function test_public_url_matches_microsite_get_request(): void
    {
        $campaign = new Campaigns([
            'campaign_name' => 'Promo Juni',
            'campaign_code' => 'JUN26',
            'campaign_title' => 'Hadiah Spesial',
            'start_date' => Carbon::parse('2026-06-01'),
            'end_date' => Carbon::parse('2026-06-30'),
        ]);

        parse_str(parse_url($campaign->public_url, PHP_URL_QUERY), $query);

        $this->assertSame([
            'utm_name' => 'Promo Juni',
            'utm_code' => 'JUN26',
            'utm_title' => 'Hadiah Spesial',
            'utm_term' => '01Jun2026_30Jun2026',
        ], $query);
    }
}
