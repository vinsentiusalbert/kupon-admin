<?php

namespace Tests\Feature;

use App\Filament\Resources\Outlets\Pages\CreateOutlets;
use App\Filament\Resources\Outlets\Pages\EditOutlets;
use App\Filament\Resources\Outlets\Pages\ListOutlets;
use App\Filament\Resources\Outlets\RelationManagers\VouchersRelationManager;
use App\Models\Campaigns;
use App\Models\Outlets;
use App\Models\User;
use App\Services\OutletVoucherService;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OutletVouchersTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_generates_the_requested_number_of_unique_vouchers(): void
    {
        $this->signInAdmin();
        $data = $this->outletData(60);

        Livewire::test(CreateOutlets::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $outlet = Outlets::query()->sole();
        $codes = $outlet->vouchers()->pluck('code');

        $this->assertSame(auth()->id(), $outlet->created_by);
        $this->assertCount(60, $codes);
        $this->assertCount(60, $codes->unique());
        $this->assertSame(60, $outlet->availableVouchers()->count());
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[1-9][0-9]{4}$/', $code);
        }
    }

    #[DataProvider('invalidQuantities')]
    public function test_create_form_rejects_invalid_quantities(mixed $quantity): void
    {
        $this->signInAdmin();

        Livewire::test(CreateOutlets::class)
            ->fillForm($this->outletData($quantity))
            ->call('create')
            ->assertHasFormErrors(['voucher_quantity']);

        $this->assertDatabaseCount('outlets', 0);
        $this->assertDatabaseCount('outlet_vouchers', 0);
    }

    public static function invalidQuantities(): array
    {
        return [[null], [0], [-1], [1.5], ['abc'], [90001]];
    }

    public function test_create_another_generates_a_separate_pool_for_each_outlet(): void
    {
        $this->signInAdmin();
        $data = $this->outletData(3);

        Livewire::test(CreateOutlets::class)
            ->fillForm($data)
            ->call('createAnother')
            ->assertHasNoFormErrors()
            ->fillForm([...$data, 'outlet_code' => '654321', 'voucher_quantity' => 7])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame([3, 7], Outlets::query()->withCount('vouchers')->orderBy('id')->pluck('vouchers_count')->all());
    }

    public function test_redeem_returns_each_voucher_once_in_random_order_and_reports_when_empty(): void
    {
        $outlet = app(OutletVoucherService::class)->createOutlet($this->outletData(60));
        $originalCodes = $outlet->vouchers()->orderBy('id')->pluck('code')->all();
        $redeemedCodes = [];

        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson(route('outlet.check'), [
                'outlet_code' => $outlet->outlet_code,
                'campaign_id' => (string) $outlet->campaign_id,
            ])->assertOk()->assertJson([
                'success' => true,
                'outlet_name' => $outlet->outlet_name,
            ]);

            $code = $response->json('voucher_code');
            $this->assertNotContains($code, $redeemedCodes);
            $this->assertNotNull($outlet->vouchers()->where('code', $code)->firstOrFail()->redeemed_at);
            $redeemedCodes[] = $code;
        }

        $this->assertEqualsCanonicalizing($originalCodes, $redeemedCodes);
        $this->assertNotSame($originalCodes, $redeemedCodes);
        $this->assertSame(0, $outlet->availableVouchers()->count());

        $this->postJson(route('outlet.check'), [
            'outlet_code' => $outlet->outlet_code,
            'campaign_id' => (string) $outlet->campaign_id,
        ])->assertStatus(409)->assertJson([
            'success' => false,
            'message' => 'Voucher untuk outlet ini sudah habis.',
        ])->assertJsonMissingPath('voucher_code');
    }

    public function test_redeem_only_uses_vouchers_from_the_matching_outlet_and_campaign(): void
    {
        $service = app(OutletVoucherService::class);
        $first = $service->createOutlet($this->outletData(2));
        $second = $service->createOutlet($this->outletData(2));
        $third = $service->createOutlet([
            'campaign_id' => $first->campaign_id,
            'outlet_name' => 'Other outlet',
            'outlet_code' => '654321',
            'voucher_quantity' => 2,
        ]);

        $this->postJson(route('outlet.check'), [
            'outlet_code' => $first->outlet_code,
            'campaign_id' => (string) $first->campaign_id,
        ])->assertOk();

        $this->assertSame(1, $first->availableVouchers()->count());
        $this->assertSame(2, $second->availableVouchers()->count());
        $this->assertSame(2, $third->availableVouchers()->count());

        $this->postJson(route('outlet.check'), [
            'outlet_code' => 'unknown',
            'campaign_id' => (string) $first->campaign_id,
        ])->assertNotFound()->assertJson(['success' => false]);

        $this->postJson(route('outlet.check'), [
            'outlet_code' => $first->outlet_code,
            'campaign_id' => 'missing-campaign',
        ])->assertNotFound();

        $this->assertSame(1, $first->availableVouchers()->count());
    }

    public function test_edit_preserves_voucher_codes_and_redemption_status_and_displays_counts(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(3));
        $service->redeem($outlet);
        $vouchers = $outlet->vouchers()->get()->toArray();

        Livewire::test(EditOutlets::class, ['record' => $outlet->id])
            ->fillForm(['outlet_name' => 'Updated outlet'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated outlet', $outlet->fresh()->outlet_name);
        $this->assertSame($vouchers, $outlet->vouchers()->get()->toArray());

        Livewire::test(ListOutlets::class)
            ->assertCanSeeTableRecords([$outlet])
            ->assertTableColumnStateSet('vouchers_count', 3, $outlet)
            ->assertTableColumnStateSet('available_vouchers_count', 2, $outlet);

        Livewire::test(VouchersRelationManager::class, [
            'ownerRecord' => $outlet,
            'pageClass' => EditOutlets::class,
        ])->assertCanSeeTableRecords($outlet->vouchers()->get());
    }

    public function test_creation_rolls_back_the_outlet_if_voucher_storage_fails(): void
    {
        DB::unprepared("CREATE TRIGGER fail_voucher_insert BEFORE INSERT ON outlet_vouchers BEGIN SELECT RAISE(ABORT, 'Test storage failure'); END");

        try {
            app(OutletVoucherService::class)->createOutlet($this->outletData(3));
            $this->fail('Voucher insertion should fail.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Test storage failure', $exception->getMessage());
        } finally {
            DB::unprepared('DROP TRIGGER fail_voucher_insert');
        }

        $this->assertDatabaseCount('outlets', 0);
        $this->assertDatabaseCount('outlet_vouchers', 0);
    }

    public function test_csv_download_contains_all_vouchers_for_only_the_selected_outlet(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(60));
        $service->createOutlet($this->outletData(3));
        $redeemed = $service->redeem($outlet);

        $component = Livewire::test(VouchersRelationManager::class, [
            'ownerRecord' => $outlet,
            'pageClass' => EditOutlets::class,
        ])
            ->searchTable($redeemed->code)
            ->callTableAction('downloadCsv')
            ->assertFileDownloaded('voucher-outlet-'.$outlet->id.'.csv', contentType: 'text/csv; charset=UTF-8');

        $csv = base64_decode($component->effects['download']['content']);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $rows = array_map(
            fn (string $line): array => str_getcsv($line, escape: ''),
            explode("\n", trim(substr($csv, 3))),
        );

        $this->assertSame(['Kode voucher', 'Status', 'Waktu redeem'], array_shift($rows));
        $this->assertCount(60, $rows);
        $this->assertSame($outlet->vouchers()->orderBy('id')->pluck('code')->all(), array_column($rows, 0));
        $redeemedRow = array_values(array_filter($rows, fn (array $row): bool => $row[0] === $redeemed->code));
        $this->assertSame([[$redeemed->code, 'Sudah digunakan', $redeemed->redeemed_at->format('Y-m-d H:i:s')]], $redeemedRow);
        $this->assertCount(59, array_filter($rows, fn (array $row): bool => $row[1] === 'Tersedia' && $row[2] === ''));
        $this->assertSame(59, $outlet->availableVouchers()->count());
    }

    public function test_migration_preserves_legacy_codes_and_makes_them_redeemable_once(): void
    {
        $migration = require database_path('migrations/2026_09_21_000001_create_outlet_vouchers_table.php');
        $migration->down();
        $data = $this->outletData(1);
        unset($data['voucher_quantity']);
        $first = Outlets::query()->create([...$data, 'voucher_code' => '12345']);
        $second = Outlets::query()->create([...$data, 'outlet_code' => '654321', 'voucher_code' => '12345']);

        $migration->up();

        $this->assertSame('12345', $first->vouchers()->sole()->code);
        $this->assertSame('12345', $second->vouchers()->sole()->code);
        $service = app(OutletVoucherService::class);
        $this->assertSame('12345', $service->redeem($first)->code);
        $this->assertNull($service->redeem($first));
        $this->assertSame(1, $second->availableVouchers()->count());
    }

    public function test_add_vouchers_action_adds_ten_unique_codes_without_changing_existing_vouchers(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(60));
        $otherOutlet = $service->createOutlet($this->outletData(2));
        $service->redeem($outlet);
        $existing = $outlet->vouchers()->orderBy('id')->get();

        Livewire::test(VouchersRelationManager::class, [
            'ownerRecord' => $outlet,
            'pageClass' => EditOutlets::class,
        ])
            ->callTableAction('addVouchers', data: ['voucher_quantity' => 10])
            ->assertHasNoTableActionErrors();

        $this->assertSame(70, $outlet->vouchers()->count());
        $this->assertCount(70, $outlet->vouchers()->pluck('code')->unique());
        $this->assertSame(69, $outlet->availableVouchers()->count());
        $this->assertSame($existing->toArray(), $outlet->vouchers()->whereKey($existing->modelKeys())->orderBy('id')->get()->toArray());
        $this->assertSame(2, $otherOutlet->vouchers()->count());
    }

    #[DataProvider('invalidQuantities')]
    public function test_add_vouchers_action_rejects_invalid_quantities(mixed $quantity): void
    {
        $this->signInAdmin();
        $outlet = app(OutletVoucherService::class)->createOutlet($this->outletData(1));

        Livewire::test(VouchersRelationManager::class, [
            'ownerRecord' => $outlet,
            'pageClass' => EditOutlets::class,
        ])
            ->callTableAction('addVouchers', data: ['voucher_quantity' => $quantity])
            ->assertHasTableActionErrors(['voucher_quantity']);

        $this->assertSame(1, $outlet->vouchers()->count());
    }

    public function test_add_vouchers_action_reports_when_requested_quantity_exceeds_remaining_code_space(): void
    {
        $this->signInAdmin();
        $outlet = app(OutletVoucherService::class)->createOutlet($this->outletData(1));

        Livewire::test(VouchersRelationManager::class, [
            'ownerRecord' => $outlet,
            'pageClass' => EditOutlets::class,
        ])
            ->callTableAction('addVouchers', data: ['voucher_quantity' => OutletVoucherService::MAX_VOUCHERS])
            ->assertHasTableActionErrors(['voucher_quantity']);

        $this->assertSame(1, $outlet->vouchers()->count());
    }

    public function test_reset_action_clears_redemption_and_allows_the_same_voucher_to_be_redeemed_again(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(2));
        $first = $service->redeem($outlet);
        $second = $service->redeem($outlet);
        $otherOutlet = $service->createOutlet($this->outletData(1));
        $otherVoucher = $service->redeem($otherOutlet);

        Livewire::test(VouchersRelationManager::class, [
            'ownerRecord' => $outlet,
            'pageClass' => EditOutlets::class,
        ])
            ->callTableAction('resetRedemption', $first)
            ->assertHasNoTableActionErrors()
            ->assertTableColumnStateSet('status', 'Tersedia', $first)
            ->assertTableColumnStateSet('redeemed_at', null, $first);

        $this->assertNull($first->fresh()->redeemed_at);
        $this->assertSame($first->code, $first->fresh()->code);
        $this->assertTrue($second->redeemed_at->equalTo($second->fresh()->redeemed_at));
        $this->assertTrue($otherVoucher->redeemed_at->equalTo($otherVoucher->fresh()->redeemed_at));
        $this->assertSame(1, $outlet->availableVouchers()->count());
        $this->assertSame($first->code, $service->redeem($outlet)->code);
        $this->assertNull($service->redeem($outlet));
    }

    public function test_voucher_mutation_actions_are_unavailable_for_an_outlet_outside_the_users_role_group(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(1));
        $voucher = $service->redeem($outlet);
        $otherUser = User::factory()->create();
        $otherUser->assignRole(Role::findOrCreate('sales'));
        $this->actingAs($otherUser);

        Livewire::test(VouchersRelationManager::class, [
            'ownerRecord' => $outlet,
            'pageClass' => EditOutlets::class,
        ])
            ->assertTableActionHidden('addVouchers')
            ->assertTableActionHidden('resetRedemption', $voucher);
    }

    private function signInAdmin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function outletData(mixed $quantity): array
    {
        $campaign = Campaigns::query()->create([
            'campaign_name' => 'Voucher campaign',
            'campaign_code' => 'PROMO',
            'campaign_title' => 'Promo vouchers',
            'start_date' => now(),
            'end_date' => now()->addDay(),
            'created_by' => auth()->id(),
        ]);

        return [
            'campaign_id' => $campaign->id,
            'outlet_name' => 'Test outlet',
            'outlet_code' => '123456',
            'voucher_quantity' => $quantity,
        ];
    }
}
