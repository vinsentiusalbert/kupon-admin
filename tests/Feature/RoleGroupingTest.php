<?php

namespace Tests\Feature;

use App\Filament\Resources\Campaigns\CampaignsResource;
use App\Filament\Resources\Locations\LocationsResource;
use App\Filament\Resources\Outlets\OutletsResource;
use App\Filament\Resources\Roles\RolesResource;
use App\Models\Campaigns;
use App\Models\Locations;
use App\Models\Outlets;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_campaigns_created_by_users_with_the_same_role(): void
    {
        $salesRole = Role::findOrCreate('sales');
        $financeRole = Role::findOrCreate('finance');

        $salesOwner = User::factory()->create();
        $salesOwner->assignRole($salesRole);

        $salesMember = User::factory()->create();
        $salesMember->assignRole($salesRole);

        $financeOwner = User::factory()->create();
        $financeOwner->assignRole($financeRole);

        $salesCampaign = Campaigns::query()->create($this->campaignData($salesOwner, 'SALES'));
        Campaigns::query()->create($this->campaignData($financeOwner, 'FINANCE'));

        $this->actingAs($salesMember);

        $this->assertSame(
            [$salesCampaign->id],
            CampaignsResource::getEloquentQuery()->pluck('id')->all()
        );
        $this->assertFalse(RolesResource::canViewAny());
    }

    public function test_outlets_and_locations_follow_the_campaign_role_group(): void
    {
        $salesRole = Role::findOrCreate('sales');
        $financeRole = Role::findOrCreate('finance');

        $salesOwner = User::factory()->create();
        $salesOwner->assignRole($salesRole);

        $salesMember = User::factory()->create();
        $salesMember->assignRole($salesRole);

        $financeOwner = User::factory()->create();
        $financeOwner->assignRole($financeRole);

        $salesCampaign = Campaigns::query()->create($this->campaignData($salesOwner, 'SALES'));
        $financeCampaign = Campaigns::query()->create($this->campaignData($financeOwner, 'FINANCE'));

        $salesOutlet = Outlets::query()->create([
            'campaign_id' => $salesCampaign->id,
            'outlet_name' => 'Sales Outlet',
            'outlet_code' => 'SO',
            'voucher_code' => '10001',
            'created_by' => $salesOwner->id,
        ]);
        Outlets::query()->create([
            'campaign_id' => $financeCampaign->id,
            'outlet_name' => 'Finance Outlet',
            'outlet_code' => 'FO',
            'voucher_code' => '10002',
            'created_by' => $financeOwner->id,
        ]);

        $salesLocation = Locations::query()->create([
            'campaign_id' => $salesCampaign->id,
            'name' => 'Sales Location',
            'addresss' => 'Sales Address',
            'maps' => 'https://maps.example/sales',
        ]);
        Locations::query()->create([
            'campaign_id' => $financeCampaign->id,
            'name' => 'Finance Location',
            'addresss' => 'Finance Address',
            'maps' => 'https://maps.example/finance',
        ]);

        $this->actingAs($salesMember);

        $this->assertSame(
            [$salesOutlet->id],
            OutletsResource::getEloquentQuery()->pluck('id')->all()
        );
        $this->assertSame(
            [$salesLocation->id],
            LocationsResource::getEloquentQuery()->pluck('id')->all()
        );
    }

    public function test_admin_sees_all_campaigns_and_can_manage_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $firstOwner = User::factory()->create();
        $firstOwner->assignRole(Role::findOrCreate('sales'));

        $secondOwner = User::factory()->create();
        $secondOwner->assignRole(Role::findOrCreate('finance'));

        Campaigns::query()->create($this->campaignData($firstOwner, 'SALES'));
        Campaigns::query()->create($this->campaignData($secondOwner, 'FINANCE'));

        $this->actingAs($admin);

        $this->assertCount(2, CampaignsResource::getEloquentQuery()->get());
        $this->assertTrue(RolesResource::canViewAny());
    }

    private function campaignData(User $owner, string $code): array
    {
        return [
            'campaign_name' => $code,
            'campaign_code' => $code,
            'campaign_title' => $code,
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'created_by' => $owner->id,
        ];
    }
}
