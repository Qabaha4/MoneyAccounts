<?php

use App\Models\User;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($this->user);

    $this->usd = Currency::factory()->usd()->create();
    $this->eur = Currency::factory()->eur()->create();
});

test('search returns matching accounts and transactions', function () {
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->usd->id,
        'name' => 'My Savings Account',
        'description' => 'For emergency funds',
    ]);

    $transaction = Transaction::create([
        'user_id' => $this->user->id,
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 100.00,
        'description' => 'Groceries and food',
        'transaction_date' => now(),
    ]);

    $response = $this->getJson(route('search', ['query' => 'Savings']));

    $response->assertOk();
    $response->assertJsonStructure(['accounts', 'transactions', 'total_results']);
    $response->assertJsonFragment(['name' => 'My Savings Account']);
});

test('search returns empty results for non-matching query', function () {
    Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->usd->id,
        'name' => 'Checking Account',
    ]);

    $response = $this->getJson(route('search', ['query' => 'zzznotfound']));

    $response->assertOk();
    expect($response->json('total_results'))->toBe(0);
    expect($response->json('accounts'))->toBe([]);
    expect($response->json('transactions'))->toBe([]);
});

test('search respects different currencies in response', function () {
    $accountUsd = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->usd->id,
        'name' => 'USD Account',
        'balance' => 500.00,
    ]);

    $accountEur = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->eur->id,
        'name' => 'EUR Account',
        'balance' => 300.00,
    ]);

    $response = $this->getJson(route('search', ['query' => 'Account']));

    $response->assertOk();
    $accounts = $response->json('accounts');

    $usdResult = collect($accounts)->firstWhere('name', 'USD Account');
    $eurResult = collect($accounts)->firstWhere('name', 'EUR Account');

    expect($usdResult['currency'])->toBe('USD');
    expect($eurResult['currency'])->toBe('EUR');
    expect((float) $usdResult['balance'])->toEqual(500.00);
    expect((float) $eurResult['balance'])->toEqual(300.00);
});

test('search is case-insensitive', function () {
    Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->usd->id,
        'name' => 'My Savings Account',
    ]);

    $lowercase = $this->getJson(route('search', ['query' => 'savings']));
    $uppercase = $this->getJson(route('search', ['query' => 'SAVINGS']));
    $mixedcase = $this->getJson(route('search', ['query' => 'SavInGS']));

    $lowercase->assertOk();
    $uppercase->assertOk();
    $mixedcase->assertOk();

    expect($lowercase->json('total_results'))->toBe(1);
    expect($uppercase->json('total_results'))->toBe(1);
    expect($mixedcase->json('total_results'))->toBe(1);
});

test('search requires authentication', function () {
    auth()->logout();

    $response = $this->getJson(route('search', ['query' => 'test']));
    $response->assertUnauthorized();
});
