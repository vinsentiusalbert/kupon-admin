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
            ->assertSee('Lokasi Penukaran')->assertDontSee('TUKAR VOUCHER')
            ->assertDontSee('Gunakan Nomor HP')->assertDontSee('Masukkan Nomor HP')
            ->assertDontSee('Gunakan Kode Voucher')->assertDontSee('998877');

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

    public function test_repeated_submission_is_rejected_and_preserves_original_code_and_time(): void
    {
        $campaign = $this->campaign();
        $payload = ['phone_number' => '+6281234567890', 'campaign_id' => $campaign->id];
        $response = $this->postJson(route('phone.redeem'), $payload)->assertOk()->json();
        $this->travel(1)->hours();
        $this->postJson(route('phone.redeem'), $payload)->assertStatus(409)
            ->assertExactJson(['success' => false, 'message' => 'Nomor ini sudah redeem.']);
        $record = PhoneRedemption::query()->sole();
        $this->assertSame($response['outlet_code'], $record->outlet_code);
        $this->assertSame($response['redeemed_at'], $record->redeemed_at->toIso8601String());
        $this->assertDatabaseCount('phone_redemptions', 1);
    }

    public function test_same_phone_cannot_redeem_in_another_campaign_with_the_same_outlet_code(): void
    {
        $firstCampaign = $this->campaign();
        $secondCampaign = $this->campaign();
        $this->postJson(route('phone.redeem'), [
            'phone_number' => '+6281234567890', 'campaign_id' => $firstCampaign->id,
        ])->assertOk();

        $this->postJson(route('phone.redeem'), [
            'phone_number' => '+6281234567890', 'campaign_id' => $secondCampaign->id,
            'outlet_code' => '001122',
        ])->assertStatus(409)
            ->assertExactJson(['success' => false, 'message' => 'Nomor ini sudah redeem.']);
        $this->assertDatabaseCount('phone_redemptions', 1);
    }

    public function test_changing_campaign_outlet_code_does_not_allow_repeated_submission(): void
    {
        $campaign = $this->campaign();
        $record = $this->record($campaign, '+6281234567890');
        $campaign->update(['phone_outlet_code' => '001122']);

        $this->postJson(route('phone.redeem'), [
            'phone_number' => $record->phone_number, 'campaign_id' => $campaign->id,
        ])->assertStatus(409)
            ->assertExactJson(['success' => false, 'message' => 'Nomor ini sudah redeem.']);
        $this->assertSame('998877', $record->fresh()->outlet_code);
        $this->assertDatabaseCount('phone_redemptions', 1);
    }

    public function test_different_phones_can_redeem_with_the_same_outlet_code(): void
    {
        $campaign = $this->campaign();
        foreach (['+6281234567890', '+6289876543210'] as $phone) {
            $this->postJson(route('phone.redeem'), [
                'phone_number' => $phone, 'campaign_id' => $campaign->id,
            ])->assertOk()->assertJson(['success' => true, 'outlet_code' => '998877']);
        }
        $this->assertDatabaseCount('phone_redemptions', 2);
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
        $this->get($campaign->public_url)->assertOk()
            ->assertDontSee('TUKAR VOUCHER')->assertDontSee('Gunakan Nomor HP')->assertDontSee('Gunakan Kode Voucher');
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
        $this->assertTrue(PhoneRedemptionResource::canCreate());
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
        $this->assertFalse(PhoneRedemptionResource::canCreate());
        $this->get(PhoneRedemptionResource::getUrl('index'))->assertForbidden();
        auth()->logout();
        $this->get(PhoneRedemptionResource::getUrl('index'))->assertRedirect();
    }

    public function test_admin_input_records_phone_numbers_with_the_fixed_country_prefix(): void
    {
        $this->signIn('admin');
        $this->travelTo(now()->startOfSecond());
        $campaign = $this->campaign();

        foreach (['81234567890', '81234567891', '81234567892', '81234567893'] as $index => $phone) {
            Livewire::test(ListPhoneRedemptions::class)
                ->callAction('redeemPhone', data: ['campaign_id' => $campaign->id, 'phone_number' => $phone])
                ->assertHasNoActionErrors();

            $this->assertDatabaseHas('phone_redemptions', [
                'campaign_id' => $campaign->id,
                'phone_number' => '+628123456789'.$index,
                'outlet_code' => '998877',
                'redeemed_at' => now()->format('Y-m-d H:i:s'),
            ]);
        }
        $this->assertDatabaseCount('phone_redemptions', 4);
        $this->assertDatabaseCount('outlet_vouchers', 0);
    }

    public function test_admin_input_rejects_a_number_already_redeemed_at_the_same_outlet(): void
    {
        $this->signIn('admin');
        $this->record($this->campaign(), '+6281234567890');
        $campaign = $this->campaign();

        Livewire::test(ListPhoneRedemptions::class)
            ->callAction('redeemPhone', data: ['campaign_id' => $campaign->id, 'phone_number' => '81234567890'])
            ->assertHasActionErrors(['phone_number' => 'Nomor ini sudah redeem.']);
        $this->assertDatabaseCount('phone_redemptions', 1);
    }

    public function test_admin_input_allows_the_same_phone_at_a_different_outlet(): void
    {
        $this->signIn('admin');
        $this->record($this->campaign(), '+6281234567890');
        $campaign = $this->campaign(['phone_outlet_code' => '001122']);

        Livewire::test(ListPhoneRedemptions::class)
            ->callAction('redeemPhone', data: ['campaign_id' => $campaign->id, 'phone_number' => '81234567890'])
            ->assertHasNoActionErrors();
        $this->assertDatabaseHas('phone_redemptions', [
            'campaign_id' => $campaign->id, 'phone_number' => '+6281234567890', 'outlet_code' => '001122',
        ]);
        $this->assertDatabaseCount('phone_redemptions', 2);
    }

    public function test_admin_input_rejects_invalid_numbers_and_unconfigured_campaigns(): void
    {
        $this->signIn('admin');
        $campaign = $this->campaign();
        foreach (['', '812', '8123456789x', '081234567890', '6281234567890', '+6281234567890', '812-3456-7890'] as $phone) {
            Livewire::test(ListPhoneRedemptions::class)
                ->callAction('redeemPhone', data: ['campaign_id' => $campaign->id, 'phone_number' => $phone])
                ->assertHasActionErrors(['phone_number']);
        }
        $disabled = $this->campaign(['phone_outlet_code' => null]);
        Livewire::test(ListPhoneRedemptions::class)
            ->callAction('redeemPhone', data: ['campaign_id' => $disabled->id, 'phone_number' => '81234567890'])
            ->assertHasActionErrors(['campaign_id']);
        $this->assertDatabaseCount('phone_redemptions', 0);
    }

    public function test_admin_input_cannot_use_campaigns_outside_the_users_role(): void
    {
        $financeOwner = $this->signIn('finance');
        $campaign = $this->campaign(['created_by' => $financeOwner->id]);
        $this->signIn('sales');

        Livewire::test(ListPhoneRedemptions::class)
            ->callAction('redeemPhone', data: ['campaign_id' => $campaign->id, 'phone_number' => '81234567890'])
            ->assertHasActionErrors(['campaign_id']);
        $this->assertDatabaseCount('phone_redemptions', 0);
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
