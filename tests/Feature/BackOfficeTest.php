<?php

namespace Tests\Feature;

use App\Models\GameConfigChangeRequest;
use App\Models\MerchantCreditWallet;
use App\Models\OmgTransaction;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(DatabaseSeeder::class);
    }

    private function staff(string $role): User
    {
        $u = User::create(['username' => $role . '1', 'password' => 'secret-pass-1']);
        $u->assignRole($role);

        return $u;
    }

    public function test_only_two_roles_exist(): void
    {
        $this->assertEqualsCanonicalizing(['webmaster', 'merchant'], \Spatie\Permission\Models\Role::pluck('name')->all());
    }

    public function test_login_routes_each_role_to_its_section(): void
    {
        $this->staff('webmaster');
        $this->staff('merchant');

        $this->post('/login', ['username' => 'webmaster1', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('home'));
        $this->get('/')->assertRedirect(route('webmaster.merchant-credits.index'));
        $this->post('/logout');

        $this->post('/login', ['username' => 'merchant1', 'password' => 'secret-pass-1']);
        $this->get('/')->assertRedirect(route('backoffice.dashboard'));
    }

    public function test_roles_are_isolated(): void
    {
        $this->actingAs($this->staff('merchant'))->get('/webmaster/merchant-credits')->assertForbidden();
        $this->actingAs($this->staff('webmaster'))->get('/backoffice')->assertForbidden();
        auth()->logout();
        $this->get('/backoffice')->assertRedirect('/login');
    }

    public function test_user_without_role_cannot_sign_in(): void
    {
        User::create(['username' => 'norole', 'password' => 'secret-pass-1']);
        $this->post('/login', ['username' => 'norole', 'password' => 'secret-pass-1'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_merchant_pages_render_with_data(): void
    {
        Http::fake(['*' => Http::response(['code' => 0, 'data' => ['list' => [
            ['gameid' => '101', 'name' => 'Super Ace', 'platform_sub_brand' => 3],
        ]]])]);

        $p = Player::create(['username' => 'alice', 'last_seen_at' => now()]);
        OmgTransaction::create(['user_id' => $p->id, 'order_id' => 'o1', 'type' => 1, 'money' => -100, 'balance_after' => 900, 'game_id' => 101]);
        OmgTransaction::create(['user_id' => $p->id, 'order_id' => 'o2', 'type' => 3, 'money' => 250, 'balance_after' => 1150, 'game_id' => 101]);

        $this->actingAs($this->staff('merchant'));

        foreach (['dashboard', 'credits.index', 'rankings.index', 'trend.index', 'retention.index', 'players.index',
                  'game-records.index', 'order-records.index', 'rtp.index', 'game-config.index', 'game-config.batch', 'logs.index'] as $name) {
            $this->get(route('backoffice.' . $name))->assertOk();
        }

        $this->get(route('backoffice.game-config.edit', '101'))->assertOk();
        $this->get(route('backoffice.rtp.players', ['q' => 'ali']))->assertOk()->assertJson(['alice']);
        $this->get(route('backoffice.players.index'))->assertSee('alice');
    }

    public function test_game_config_change_flows_to_webmaster(): void
    {
        Http::fake(['*' => Http::response(['code' => 0, 'data' => ['list' => [
            ['gameid' => '101', 'name' => 'Super Ace', 'platform_sub_brand' => 3],
        ]]])]);

        $this->actingAs($this->staff('merchant'))
            ->post(route('backoffice.game-config.update', '101'), ['min_bet' => '1', 'max_bet' => '50'])
            ->assertRedirect();

        $this->assertSame(2, GameConfigChangeRequest::where('status', 'pending')->count());

        auth()->logout();
        $this->actingAs($this->staff('webmaster'))
            ->get(route('webmaster.game-config-changes.index'))->assertOk()->assertSee('Super Ace');

        $change = GameConfigChangeRequest::first();
        $this->post(route('webmaster.game-config-changes.toggle', $change))->assertRedirect();
        $this->assertSame('applied', $change->fresh()->status);
    }

    public function test_webmaster_credit_topup_deduct_reset(): void
    {
        $this->actingAs($this->staff('webmaster'));

        $this->post(route('webmaster.merchant-credits.topup'), ['amount' => 500])->assertSessionHasNoErrors();
        $this->assertEquals(500, MerchantCreditWallet::get()->balance);

        $this->post(route('webmaster.merchant-credits.deduct'), ['amount' => 9999])->assertSessionHasErrors('amount');
        $this->post(route('webmaster.merchant-credits.deduct'), ['amount' => 200]);
        $this->assertEquals(300, MerchantCreditWallet::get()->balance);

        $this->post(route('webmaster.merchant-credits.reset'), ['confirm' => 'RESET'])->assertSessionHasNoErrors();
        $this->assertEquals(0, MerchantCreditWallet::get()->balance);
        $this->get(route('webmaster.merchant-credits.index'))->assertOk();
    }

    public function test_webmaster_manages_merchant_account(): void
    {
        $this->actingAs($this->staff('webmaster'));
        $this->get(route('webmaster.merchant-account.index'))->assertOk();

        $this->post(route('webmaster.merchant-account.update'), ['username' => 'shop', 'password' => 'longenough1', 'password_confirmation' => 'longenough1'])
            ->assertSessionHasNoErrors();

        $shop = User::where('username', 'shop')->first();
        $this->assertTrue($shop->hasRole('merchant'));

        // Updating again changes the same account rather than creating another.
        $this->post(route('webmaster.merchant-account.update'), ['username' => 'shop2']);
        $this->assertSame(1, User::role('merchant')->count());
        $this->assertSame('shop2', $shop->fresh()->username);
    }

    public function test_merchant_registers_callback_urls(): void
    {
        $this->actingAs($this->staff('merchant'));
        $this->get(route('backoffice.settings.index'))->assertOk();

        $this->post(route('backoffice.settings.update'), [
            'verify_session_url' => 'https://shop.example.com/api/verify',
            'get_balance_url'    => 'https://shop.example.com/api/balance',
            'change_balance_url' => '',
        ])->assertSessionHasNoErrors();

        $urls = \App\Models\MerchantCallbackUrl::get();
        $this->assertSame('https://shop.example.com/api/verify', $urls->verify_session_url);
        $this->assertNull($urls->change_balance_url);
        $this->assertDatabaseHas('backoffice_operation_logs', ['action' => 'callback_urls']);

        $this->post(route('backoffice.settings.update'), ['get_balance_url' => 'http://insecure.example.com'])
            ->assertSessionHasErrors('get_balance_url');
        $this->post(route('backoffice.settings.update'), ['get_balance_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('get_balance_url');
    }

    public function test_webmaster_cannot_open_merchant_settings(): void
    {
        $this->actingAs($this->staff('webmaster'))->get(route('backoffice.settings.index'))->assertForbidden();
    }
}
