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

    public function test_redeem_accepts_generated_voucher_and_returns_outlet_code_once(): void
    {
        $outlet = app(OutletVoucherService::class)->createOutlet($this->outletData(3));
        $this->get(Campaigns::findOrFail($outlet->campaign_id)->public_url)
            ->assertOk()->assertSee('Masukkan Kode Voucher')->assertSee('Kode outlet');
        foreach ($outlet->vouchers()->get() as $voucher) {
            $payload = ['voucher_code' => $voucher->code, 'campaign_id' => (string) $outlet->campaign_id];
            $this->postJson(route('outlet.check'), $payload)->assertOk()->assertExactJson([
                'success' => true,
                'outlet_name' => $outlet->outlet_name,
                'outlet_code' => $outlet->outlet_code,
            ]);
            $this->assertNotNull($voucher->fresh()->redeemed_at);
            $this->postJson(route('outlet.check'), $payload)->assertStatus(409)
                ->assertJson(['message' => 'Kode voucher sudah digunakan.'])
                ->assertJsonMissingPath('outlet_code');
        }
        $this->assertSame(0, $outlet->availableVouchers()->count());
    }

    public function test_redeem_is_scoped_to_campaign_and_rejects_outlet_codes(): void
    {
        $service = app(OutletVoucherService::class);
        $first = $service->createOutlet($this->outletData(2));
        $second = $service->createOutlet($this->outletData(2));
        $voucher = $first->vouchers()->firstOrFail();
        if (! $second->vouchers()->where('code', $voucher->code)->exists()) {
            $second->vouchers()->firstOrFail()->update(['code' => $voucher->code]);
        }
        $this->postJson(route('outlet.check'), [
            'voucher_code' => $voucher->code, 'campaign_id' => (string) $first->campaign_id,
        ])->assertOk()->assertJson(['outlet_code' => $first->outlet_code]);
        $this->postJson(route('outlet.check'), [
            'voucher_code' => $voucher->code, 'campaign_id' => 'missing-campaign',
        ])->assertNotFound();
        $this->postJson(route('outlet.check'), [
            'outlet_code' => $first->outlet_code, 'campaign_id' => (string) $first->campaign_id,
        ])->assertUnprocessable()->assertJsonValidationErrors('voucher_code');
        $this->postJson(route('outlet.check'), [
            'voucher_code' => '00000', 'campaign_id' => (string) $first->campaign_id,
        ])->assertNotFound();
        $this->assertSame(1, $first->availableVouchers()->count());
        $this->assertSame(2, $second->availableVouchers()->count());
    }

    public function test_new_voucher_codes_are_unique_across_outlets_in_a_campaign(): void
    {
        $service = app(OutletVoucherService::class);
        $data = $this->outletData(1000);
        $first = $service->createOutlet($data);
        $second = $service->createOutlet([...$data, 'outlet_code' => '654321']);
        $service->addVouchers($second, ['voucher_quantity' => 1000]);
        $this->assertEmpty(array_intersect($first->vouchers()->pluck('code')->all(), $second->vouchers()->pluck('code')->all()));
        $this->postJson(route('outlet.check'), [
            'voucher_code' => $second->vouchers()->firstOrFail()->code,
            'campaign_id' => (string) $second->campaign_id,
        ])->assertOk()->assertJson(['outlet_code' => '654321']);
        $this->assertSame(1000, $first->availableVouchers()->count());
        $this->assertSame(1999, $second->availableVouchers()->count());
    }

    public function test_invalid_voucher_input_does_not_consume_vouchers(): void
    {
        $outlet = app(OutletVoucherService::class)->createOutlet($this->outletData(1));
        foreach (['', '1234', '123456', 'abcde', ['12345']] as $code) {
            $this->postJson(route('outlet.check'), [
                'voucher_code' => $code, 'campaign_id' => (string) $outlet->campaign_id,
            ])->assertUnprocessable()->assertJsonValidationErrors('voucher_code');
        }
        $this->assertSame(1, $outlet->availableVouchers()->count());
    }

    public function test_legacy_duplicate_codes_are_rejected_without_consuming_either_voucher(): void
    {
        $service = app(OutletVoucherService::class);
        $data = $this->outletData(1);
        $first = $service->createOutlet($data);
        $second = $service->createOutlet([...$data, 'outlet_code' => '654321']);
        $code = $first->vouchers()->sole()->code;
        $second->vouchers()->update(['code' => $code]);
        $this->postJson(route('outlet.check'), [
            'voucher_code' => $code, 'campaign_id' => (string) $first->campaign_id,
        ])->assertStatus(409)->assertJsonMissingPath('outlet_code');
        $this->assertSame(1, $first->availableVouchers()->count());
        $this->assertSame(1, $second->availableVouchers()->count());
    }

    public function test_edit_preserves_voucher_codes_and_redemption_status_and_displays_counts(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(3));
        $service->redeem($outlet, $outlet->availableVouchers()->firstOrFail()->code);
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
        $redeemed = $service->redeem($outlet, $outlet->availableVouchers()->firstOrFail()->code);

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
        $this->assertSame('12345', $service->redeem($first, '12345')->code);
        $this->assertNull($service->redeem($first, '12345'));
        $this->assertSame(1, $second->availableVouchers()->count());
    }

    public function test_add_vouchers_action_adds_ten_unique_codes_without_changing_existing_vouchers(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(60));
        $otherOutlet = $service->createOutlet($this->outletData(2));
        $service->redeem($outlet, $outlet->availableVouchers()->firstOrFail()->code);
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
        $first = $service->redeem($outlet, $outlet->availableVouchers()->firstOrFail()->code);
        $second = $service->redeem($outlet, $outlet->availableVouchers()->firstOrFail()->code);
        $otherOutlet = $service->createOutlet($this->outletData(1));
        $otherVoucher = $service->redeem($otherOutlet, $otherOutlet->availableVouchers()->value('code'));

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
        $this->assertSame($first->code, $service->redeem($outlet, $first->code)->code);
        $this->assertNull($service->redeem($outlet, $first->code));
    }

    public function test_voucher_mutation_actions_are_unavailable_for_an_outlet_outside_the_users_role_group(): void
    {
        $this->signInAdmin();
        $service = app(OutletVoucherService::class);
        $outlet = $service->createOutlet($this->outletData(1));
        $voucher = $service->redeem($outlet, $outlet->availableVouchers()->firstOrFail()->code);
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
