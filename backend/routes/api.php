<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\MultiGroupDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::post('/login', [UserController::class, 'login'])->middleware(MultiGroupDatabase::class);

Route::middleware([EnsureFrontendRequestsAreStateful::class, 'auth:sanctum', MultiGroupDatabase::class])
->group(function() {
    Route::get('/logout', [UserController::class, 'logout']);
    Route::prefix('user')->controller(UserController::class)->group(function() {
        Route::get('/', 'getUsers');
        Route::get('/{userId}', 'getUserById');
        Route::post('/', 'storeUser');
        Route::put('/{userId}', 'updateUser');
    });

    Route::prefix('account-type')->controller(AccountTypeController::class)->group(function() {
        Route::get('/', 'getAccountTypes');
    });

    Route::prefix('account')->controller(AccountController::class)->group(function() {
        Route::get('/', 'getAccounts');
        Route::post('/', 'storeAccount');
        Route::get('/pdf', 'printAccounts');
        Route::get('/excel', 'exportAccounts');
        Route::get('/{accountId}', 'getAccountById');
        Route::post('/import', 'importAccount');
        Route::post('/delete', 'deleteAccount');
    });

    Route::prefix('general-ledger')->controller(GeneralLedgerController::class)->group(function() {
        Route::get('/', 'getGeneralLedgerImports');
        Route::get('/detail/{importNo}', 'getGeneralLedgers');
        Route::post('/', 'storeGeneralLedger');
        Route::get('/pdf', 'printGeneralLedgers');
        Route::get('/excel', 'exportGeneralLedgers');
        Route::post('/import', 'importGeneralLedger');
    });

    Route::prefix('report')->controller(ReportController::class)->group(function() {
        Route::get('/balance-sheet', 'getBalanceSheets');
        Route::get('/income-statement', 'getIncomeStatements');
        Route::get('/ledger', 'getLedgers');
        Route::get('/accounts', 'getAccounts');
    });
});
