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
                  'game-records.index', 'order-records.index', 'bet-records.index', 'rtp.index', 'patron.index', 'game-config.index', 'game-config.batch', 'logs.index'] as $name) {
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

    public function test_merchant_nurtures_and_cancels_players(): void
    {
        Http::fake(['*' => Http::response(['code' => 1, 'msg' => 'success'])]);
        $a = Player::create(['username' => 'alice']);
        $b = Player::create(['username' => 'bob']);
        $this->actingAs($this->staff('merchant'));

        $this->post(route('backoffice.patron.store'), ['usernames' => "alice, bob", 'kind' => 'nurture', 'patron_type' => 1, 'amount' => '12.5'])
            ->assertSessionHasNoErrors();

        Http::assertSent(function ($r) use ($a, $b) {
            $d = $r->data();
            return str_contains($r->url(), '/patron/control/set')
                && $d['uname'] === "{$a->id},{$b->id}" && $d['type'] === 14 && $d['amount'] === 125000 && $d['patron_type'] === 1;
        });
        $this->assertSame(2, \App\Models\PatronControl::where('status', 'active')->count());

        // Setting again replaces the earlier active control.
        $this->post(route('backoffice.patron.store'), ['usernames' => 'alice', 'kind' => 'kill', 'amount' => '5']);
        $this->assertSame(1, \App\Models\PatronControl::where('user_id', $a->id)->where('status', 'active')->count());
        $this->assertSame('kill', \App\Models\PatronControl::where('user_id', $a->id)->where('status', 'active')->value('kind'));

        $this->post(route('backoffice.patron.cancel'), ['usernames' => 'alice bob'])->assertSessionHasNoErrors();
        $this->assertSame(0, \App\Models\PatronControl::where('status', 'active')->count());
        $this->get(route('backoffice.patron.index'))->assertOk();
    }

    public function test_patron_rejects_unknown_players_and_omg_failures_are_not_recorded(): void
    {
        Player::create(['username' => 'alice']);
        $this->actingAs($this->staff('merchant'));

        $this->post(route('backoffice.patron.store'), ['usernames' => 'alice ghost', 'kind' => 'kill', 'amount' => 5])
            ->assertSessionHasErrors('usernames');

        Http::fake(['*' => Http::response(['code' => 20008, 'msg' => 'sign error'])]);
        $this->post(route('backoffice.patron.store'), ['usernames' => 'alice', 'kind' => 'kill', 'amount' => 5])
            ->assertSessionHas('error');
        $this->assertSame(0, \App\Models\PatronControl::count());
    }

    public function test_auto_nurturing_toggle(): void
    {
        Http::fake(['*' => Http::response(['code' => 1, 'msg' => 'success'])]);
        $this->actingAs($this->staff('merchant'));

        $this->post(route('backoffice.patron.auto'), ['enabled' => 1, 'amount' => '100', 'patron_type' => 0])->assertSessionHasNoErrors();
        Http::assertSent(fn ($r) => str_contains($r->url(), '/patron/auto/config') && ($r->data()['patron_status'] ?? null) === 1 && $r->data()['patron_amount'] === 1000000);
        $this->assertTrue(\App\Models\PatronAutoConfig::get()->enabled);

        $this->post(route('backoffice.patron.auto'), ['enabled' => 0, 'cancel_all' => 1])->assertSessionHasNoErrors();
        Http::assertSent(fn ($r) => str_contains($r->url(), '/patron/auto/config') && ($r->data()['patron_status'] ?? null) === 0 && $r->data()['is_cancel_all'] === 1);
        $this->assertFalse(\App\Models\PatronAutoConfig::get()->enabled);

        $this->post(route('backoffice.patron.auto'), ['enabled' => 1])->assertSessionHasErrors(['amount', 'patron_type']);
    }

    public function test_webmaster_cannot_use_patron_controls(): void
    {
        $this->actingAs($this->staff('webmaster'))->get(route('backoffice.patron.index'))->assertForbidden();
    }

    public function test_omg_bet_records_page(): void
    {
        $p = Player::create(['username' => 'alice']);
        OmgTransaction::create(['user_id' => $p->id, 'order_id' => 'o1', 'type' => 1, 'money' => -10, 'balance_after' => 90, 'round_id' => '1806264170304233472']);

        Http::fake(['*' => Http::response(['code' => 1, 'msg' => 'success', 'total' => 2, 'data' => [
            ['create_time' => 1719481910, 'round_id' => 1806264170304233472, 'account' => 'nick', 'account_id' => (string) $p->id,
             'game_id' => '10049', 'win' => 0, 'bet' => 10, 'enter_money' => 100, 'after_settlement_money' => 90, 'small_game_type' => 0],
            ['create_time' => 1719481924, 'round_id' => 1806264228693139456, 'account' => 'nick', 'account_id' => (string) $p->id,
             'game_id' => '10049', 'win' => 2.5, 'bet' => 10, 'enter_money' => 90, 'after_settlement_money' => 82.5, 'small_game_type' => 2],
        ]])]);

        $this->actingAs($this->staff('merchant'));
        $this->get(route('backoffice.bet-records.index', ['from' => '2024-06-27T09:00', 'to' => '2024-06-27T12:00', 'search' => 'alice', 'game_id' => '10049', 'size' => 25]))
            ->assertOk()->assertSee('alice')->assertSee('Free spins')->assertSee('Not found')->assertSee('-17.50');

        Http::assertSent(function ($r) use ($p) {
            $d = $r->data();
            return str_contains($r->url(), 'GetGameRecordList') && $d['uname'] === (string) $p->id && $d['game_id'] === '10049'
                && $d['size'] === 25 && $d['page'] === 1 && $d['start_time'] === 1719478800 && $d['end_time'] === 1719489600;
        });
    }

    public function test_omg_bet_records_limits_window_and_surfaces_errors(): void
    {
        Http::fake(['*' => Http::response(['code' => 20003, 'msg' => 'query limit exceeded'])]);
        $this->actingAs($this->staff('merchant'));

        $this->get(route('backoffice.bet-records.index', ['from' => '2024-06-01T00:00', 'to' => '2024-06-10T00:00']))
            ->assertOk()->assertSee('shortened to the 24 hours')->assertSee('Query limit exceeded (code 20003)');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'GetGameRecordList') && $r->data()['end_time'] - $r->data()['start_time'] === 86400);

        $this->get(route('backoffice.bet-records.index', ['search' => 'ghost']))->assertOk()->assertSee('No player named');
    }

    public function test_dashboard_game_and_provider_breakdown(): void
    {
        Http::fake(['*' => Http::response(['code' => 0, 'data' => ['list' => [
            ['gameid' => '101', 'name' => 'Super Ace', 'platform_sub_brand' => 3],
            ['gameid' => '102', 'name' => 'Fortune Gems', 'platform_sub_brand' => 3],
            ['gameid' => '201', 'name' => 'Mahjong Ways', 'platform_sub_brand' => 2],
        ]]])]);

        $p = Player::create(['username' => 'alice']);
        $q = Player::create(['username' => 'bob']);
        $tx = fn ($player, $n, $type, $money, $game) => OmgTransaction::create([
            'user_id' => $player->id, 'order_id' => $n, 'type' => $type, 'money' => $money, 'balance_after' => 0, 'game_id' => $game,
        ]);
        $tx($p, 'a1', 1, -100, 101); $tx($p, 'a2', 3, 40, 101);
        $tx($q, 'b1', 1, -50, 102);  $tx($q, 'b2', 3, 100, 102);
        $tx($p, 'c1', 1, -200, 201); $tx($p, 'c2', 3, 100, 201);

        \App\Models\GameConfig::create(['game_id' => '101', 'game_name' => 'Super Ace', 'settings' => ['internal_rtp' => '90']]);
        \App\Models\PatronControl::create(['user_id' => $p->id, 'kind' => 'nurture', 'patron_type' => 0, 'amount' => 10, 'status' => 'active']);

        $this->actingAs($this->staff('merchant'));
        $res = $this->get(route('backoffice.dashboard'))->assertOk();

        $res->assertSee('Super Ace')->assertSee('Mahjong Ways')->assertSee('JILI')->assertSee('PGSoft');
        // JILI: bet 150, win 140 -> RTP 93.33%; PGSoft: bet 200, win 100 -> 50%.
        $res->assertSee('93.33%')->assertSee('50.00%');

        $games = $res->viewData('games');
        $this->assertSame('201', $games->first()['id']);          // highest bet first
        $this->assertSame(90.0, $games->firstWhere('id', '101')['target']);
        $this->assertSame(1, $res->viewData('patron')['nurture']);
        $this->assertSame(2, $res->viewData('stats')['new_players_today']);

        $this->get(route('backoffice.dashboard', ['period' => '30d']))->assertOk();
        $this->get(route('backoffice.dashboard', ['period' => 'bogus']))->assertOk();
    }

    public function test_dashboard_works_when_omg_game_list_unavailable(): void
    {
        Http::fake(['*' => Http::response(['code' => 500])]);
        $p = Player::create(['username' => 'alice']);
        OmgTransaction::create(['user_id' => $p->id, 'order_id' => 'x', 'type' => 1, 'money' => -10, 'balance_after' => 0, 'game_id' => 999]);

        $this->actingAs($this->staff('merchant'))->get(route('backoffice.dashboard'))->assertOk()->assertSee('Game 999')->assertSee('Other');
    }

    public function test_omg_error_codes_are_translated(): void
    {
        $this->assertSame(
            'Signature verification failed (code 20008). Check that the OMG app ID and key in this app match the merchant back office.',
            \App\Support\OmgErrorCodes::message(20008, 'sign error')
        );
        $this->assertSame('The query time range cannot exceed 24 hours (code 20014). Use a shorter time range.', \App\Support\OmgErrorCodes::message(20014));
        $this->assertSame('Something odd (code 99999)', \App\Support\OmgErrorCodes::message(99999, 'Something odd'));
        $this->assertSame('Unknown OMG error (code 99999)', \App\Support\OmgErrorCodes::message(99999));
        $this->assertSame('Unexpected response from OMG', \App\Support\OmgErrorCodes::message(null));
    }

    public function test_rtp_failure_shows_readable_omg_error(): void
    {
        Http::fake(['*' => Http::response(['code' => 20011, 'msg' => 'merchant deactivated'])]);
        Player::create(['username' => 'alice']);

        $this->actingAs($this->staff('merchant'))
            ->post(route('backoffice.rtp.store'), ['username' => 'alice', 'switch' => 1, 'control_type' => 0])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'The merchant account is deactivated (code 20011). Contact OMG.'));
    }

    public function test_unreachable_omg_is_reported_plainly(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('cURL error 6'));
        Player::create(['username' => 'alice']);

        $this->actingAs($this->staff('merchant'))
            ->post(route('backoffice.patron.store'), ['usernames' => 'alice', 'kind' => 'kill', 'amount' => 5])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Could not reach OMG'));
        $this->assertSame(0, \App\Models\PatronControl::count());
    }

    public function test_game_config_explains_why_the_game_list_is_empty(): void
    {
        Http::fake(['*' => Http::response(['code' => 10018, 'msg' => 'disabled'])]);

        $this->actingAs($this->staff('merchant'))->get(route('backoffice.game-config.index'))
            ->assertOk()->assertSee('load the game list from OMG')->assertSee('merchant account has been disabled by OMG');
    }
}
