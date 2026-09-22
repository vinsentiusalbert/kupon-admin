<?php

namespace Tests\Feature;

use App\Filament\Resources\Campaigns\Pages\EditCampaigns;
use App\Filament\Resources\PhoneRedemptions\Pages\ListPhoneRedemptions;
use App\Filament\Resources\PhoneRedemptions\PhoneRedemptionResource;
use App\Models\Campaigns;
use App\Models\PhoneRedemption;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PhoneRedemptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_redemption_records_number_time_and_configured_outlet_without_using_vouchers(): void
    {
        $this->travelTo(now()->startOfSecond());
        $campaign = $this->campaign();
        $this->get($campaign->public_url)->assertOk()
            ->assertSee('Gunakan Nomor HP')->assertSee('Gunakan Kode Voucher')
            ->assertSee('+62')->assertDontSee('998877');

        $this->postJson(route('phone.redeem'), [
            'phone_number' => '+6281234567890', 'campaign_id' => $campaign->id,
            'outlet_code' => '111111',
        ])->assertOk()->assertJson(['success' => true, 'outlet_code' => '998877']);

        $record = PhoneRedemption::query()->sole();
        $this->assertSame('+6281234567890', $record->phone_number);
        $this->assertSame($campaign->id, $record->campaign_id);
        $this->assertTrue($record->redeemed_at->equalTo(now()));
        $this->assertDatabaseCount('outlet_vouchers', 0);
    }

    public function test_repeated_submission_preserves_original_code_and_time(): void
    {
        $campaign = $this->campaign();
        $payload = ['phone_number' => '+6281234567890', 'campaign_id' => $campaign->id];
        $response = $this->postJson(route('phone.redeem'), $payload)->assertOk()->json();
        $campaign->update(['phone_outlet_code' => '112233']);
        $this->travel(1)->hours();
        $this->postJson(route('phone.redeem'), $payload)->assertOk()->assertExactJson($response);
        $this->assertDatabaseCount('phone_redemptions', 1);
    }

    public function test_same_phone_can_redeem_in_different_campaigns_using_each_campaigns_code(): void
    {
        foreach (['998877', '001122'] as $code) {
            $campaign = $this->campaign(['phone_outlet_code' => $code]);
            $this->postJson(route('phone.redeem'), [
                'phone_number' => '+6281234567890', 'campaign_id' => (string) $campaign->id,
            ])->assertOk()->assertJson(['outlet_code' => $code]);
        }
        $this->assertDatabaseCount('phone_redemptions', 2);
    }

    public function test_invalid_numbers_and_unconfigured_campaigns_do_not_create_records(): void
    {
        $campaign = $this->campaign();
        foreach (['', '081234567890', '+62081234567890', '+62812', '+6281234567890123', '+62812345678x', ['+6281234567890']] as $phone) {
            $this->postJson(route('phone.redeem'), [
                'phone_number' => $phone, 'campaign_id' => $campaign->id,
            ])->assertUnprocessable()->assertJsonValidationErrors('phone_number');
        }

        $campaign->update(['phone_outlet_code' => null]);
        $this->get($campaign->public_url)->assertOk()->assertDontSee('Gunakan Nomor HP')->assertSee('Gunakan Kode Voucher');
        $this->postJson(route('phone.redeem'), [
            'phone_number' => '+6281234567890', 'campaign_id' => $campaign->id,
        ])->assertStatus(409)->assertJsonMissingPath('outlet_code');
        $this->postJson(route('phone.redeem'), [
            'phone_number' => '+6281234567890', 'campaign_id' => 999999,
        ])->assertNotFound();
        $this->assertDatabaseCount('phone_redemptions', 0);
    }

    public function test_admin_can_configure_and_disable_phone_redemption_on_campaign_form(): void
    {
        $this->signIn('admin');
        $campaign = $this->campaign(['phone_outlet_code' => null]);
        Livewire::test(EditCampaigns::class, ['record' => $campaign->id])
            ->fillForm(['phone_outlet_code' => '001122'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('001122', $campaign->fresh()->phone_outlet_code);
        Livewire::test(EditCampaigns::class, ['record' => $campaign->id])
            ->fillForm(['phone_outlet_code' => 'invalid'])->call('save')->assertHasFormErrors(['phone_outlet_code']);
        Livewire::test(EditCampaigns::class, ['record' => $campaign->id])
            ->fillForm(['phone_outlet_code' => ''])->call('save')->assertHasNoFormErrors();
        $this->assertNull($campaign->fresh()->phone_outlet_code);
    }

    public function test_admin_menu_lists_searchable_numbers_and_redemption_time_in_wib(): void
    {
        $this->signIn('admin');
        $first = $this->record($this->campaign(), '+6281234567890');
        $second = $this->record($this->campaign(), '+6289876543210');
        $first->update(['redeemed_at' => '2026-09-22 03:15:30']);

        Livewire::test(ListPhoneRedemptions::class)
            ->assertCanSeeTableRecords([$first, $second])
            ->assertSee('22/09/2026 10:15:30')
            ->searchTable('6281234567890')->assertCanSeeTableRecords([$first])
            ->assertCanNotSeeTableRecords([$second]);

        Livewire::test(ListPhoneRedemptions::class)->filterTable('campaign_id', $second->campaign_id)
            ->assertCanSeeTableRecords([$second])->assertCanNotSeeTableRecords([$first]);
        $this->assertFalse(PhoneRedemptionResource::canCreate());
        $this->assertFalse(PhoneRedemptionResource::canEdit($first));
        $this->assertFalse(PhoneRedemptionResource::canDelete($first));
    }

    public function test_records_follow_campaign_role_visibility_and_are_not_public(): void
    {
        $salesOwner = $this->signIn('sales');
        $first = $this->record($this->campaign(['created_by' => $salesOwner->id]), '+6281234567890');
        $financeOwner = $this->signIn('finance');
        $second = $this->record($this->campaign(['created_by' => $financeOwner->id]), '+6289876543210');

        $this->signIn('sales');
        Livewire::test(ListPhoneRedemptions::class)->assertCanSeeTableRecords([$first])->assertCanNotSeeTableRecords([$second]);
        $this->actingAs(User::factory()->create());
        $this->assertFalse(PhoneRedemptionResource::canViewAny());
        $this->get(PhoneRedemptionResource::getUrl('index'))->assertForbidden();
        auth()->logout();
        $this->get(PhoneRedemptionResource::getUrl('index'))->assertRedirect();
    }

    private function campaign(array $attributes = []): Campaigns
    {
        return Campaigns::query()->create([
            'campaign_name' => 'Phone campaign', 'campaign_code' => 'PHONE',
            'campaign_title' => 'Phone promo', 'start_date' => now(), 'end_date' => now()->addDay(),
            'phone_outlet_code' => '998877', ...$attributes,
        ]);
    }

    private function record(Campaigns $campaign, string $phone): PhoneRedemption
    {
        return PhoneRedemption::query()->create([
            'campaign_id' => $campaign->id, 'phone_number' => $phone,
            'outlet_code' => $campaign->phone_outlet_code, 'redeemed_at' => now(),
        ]);
    }

    private function signIn(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role));
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }
}
