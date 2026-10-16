<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BackOffice\CreditController;
use App\Http\Controllers\BackOffice\DashboardController;
use App\Http\Controllers\BackOffice\GameConfigController;
use App\Http\Controllers\BackOffice\LogController;
use App\Http\Controllers\BackOffice\PatronController;
use App\Http\Controllers\BackOffice\PlayerController;
use App\Http\Controllers\BackOffice\RankingController;
use App\Http\Controllers\BackOffice\RetentionController;
use App\Http\Controllers\BackOffice\RtpController;
use App\Http\Controllers\BackOffice\SettingController;
use App\Http\Controllers\BackOffice\TransactionController;
use App\Http\Controllers\BackOffice\TrendController;
use App\Http\Controllers\Webmaster\GameConfigChangeController;
use App\Http\Controllers\Webmaster\MerchantAccountController;
use App\Http\Controllers\Webmaster\MerchantCreditController;
use Illuminate\Support\Facades\Route;

// Two roles only: `webmaster` and `merchant`. One login page; where you land
// depends on your role.
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/', [LoginController::class, 'home'])->name('home');

    // Merchant back office — the OMG merchant account holder.
    Route::middleware('role:merchant')->prefix('backoffice')->name('backoffice.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('credits', [CreditController::class, 'index'])->name('credits.index');

        Route::get('rankings', [RankingController::class, 'index'])->name('rankings.index');
        Route::get('trend', [TrendController::class, 'index'])->name('trend.index');
        Route::get('retention', [RetentionController::class, 'index'])->name('retention.index');

        Route::get('players', [PlayerController::class, 'index'])->name('players.index');
        Route::get('game-records', [TransactionController::class, 'index'])->name('game-records.index');
        Route::get('order-records', [TransactionController::class, 'index'])->name('order-records.index');

        Route::get('rtp', [RtpController::class, 'index'])->name('rtp.index');
        Route::get('rtp/players', [RtpController::class, 'searchPlayers'])->name('rtp.players');
        Route::post('rtp', [RtpController::class, 'store'])->name('rtp.store');
        Route::delete('rtp/{user}', [RtpController::class, 'destroy'])->name('rtp.destroy');

        Route::get('patron', [PatronController::class, 'index'])->name('patron.index');
        Route::post('patron', [PatronController::class, 'store'])->name('patron.store');
        Route::post('patron/cancel', [PatronController::class, 'cancel'])->name('patron.cancel');
        Route::post('patron/auto', [PatronController::class, 'auto'])->name('patron.auto');

        Route::get('game-config', [GameConfigController::class, 'index'])->name('game-config.index');
        Route::get('game-config/batch', [GameConfigController::class, 'batch'])->name('game-config.batch');
        Route::post('game-config/batch', [GameConfigController::class, 'batchUpdate'])->name('game-config.batch.update');
        Route::get('game-config/{gameId}', [GameConfigController::class, 'edit'])->name('game-config.edit');
        Route::post('game-config/{gameId}', [GameConfigController::class, 'update'])->name('game-config.update');

        Route::get('logs', [LogController::class, 'index'])->name('logs.index');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // Webmaster — sells credit, applies the merchant's game-config requests,
    // owns the merchant's login.
    Route::middleware('role:webmaster')->prefix('webmaster')->name('webmaster.')->group(function () {
        Route::get('merchant-credits', [MerchantCreditController::class, 'index'])->name('merchant-credits.index');
        Route::post('merchant-credits/topup', [MerchantCreditController::class, 'topup'])->name('merchant-credits.topup');
        Route::post('merchant-credits/deduct', [MerchantCreditController::class, 'deduct'])->name('merchant-credits.deduct');
        Route::post('merchant-credits/reset', [MerchantCreditController::class, 'reset'])->name('merchant-credits.reset');

        Route::get('game-config-changes', [GameConfigChangeController::class, 'index'])->name('game-config-changes.index');
        Route::post('game-config-changes/apply-all', [GameConfigChangeController::class, 'applyAll'])->name('game-config-changes.apply-all');
        Route::post('game-config-changes/{change}/toggle', [GameConfigChangeController::class, 'toggle'])->name('game-config-changes.toggle');

        Route::get('merchant-account', [MerchantAccountController::class, 'index'])->name('merchant-account.index');
        Route::post('merchant-account', [MerchantAccountController::class, 'update'])->name('merchant-account.update');
    });
});
