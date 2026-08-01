<?php

use App\Models\User;
use App\Models\Account;
use App\Models\Currency;
use App\Services\PasscodeService;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->currency = Currency::factory()->create([
        'code' => 'USD',
        'symbol' => '$',
        'is_active' => true,
    ]);
    $this->passcodeService = app(PasscodeService::class);
});

test('locked account show is blocked without passcode', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'is_locked' => true,
    ]);

    $response = $this->get(route('accounts.show', $account));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Accounts/Show')->where('locked', true));
});

test('locked account show works when verified', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    session(['passcode_verified' => true]);

    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'is_locked' => true,
    ]);

    $response = $this->get(route('accounts.show', $account));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Accounts/Show')->has('account'));
});

test('locked account without any passcode set shows normally', function () {
    $this->actingAs($this->user);

    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'is_locked' => true,
    ]);

    $response = $this->get(route('accounts.show', $account));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Accounts/Show')->has('account'));
});

test('hidden balance masks the balance field', function () {
    $this->actingAs($this->user);

    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'balance' => 500,
        'hide_balance' => true,
    ]);

    $response = $this->get(route('accounts.index'));
    $response->assertStatus(200);
});

test('hidden balance is revealed after balance_reveal flag', function () {
    $this->user->update(['hide_dashboard_balance' => true]);
    $this->actingAs($this->user);
    session(['balance_reveal' => true]);

    Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'balance' => 500,
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);

    // The one-time flag should be consumed
    expect(session('balance_reveal'))->toBeNull();

    // A subsequent request without the flag should hide the balance again
    $response2 = $this->get(route('dashboard'));
    $response2->assertStatus(200);
    $response2->assertInertia(fn ($page) =>
        $page->component('Dashboard')
            ->where('totalBalance', null)
    );
});

test('update requires passcode when configured', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
    ]);

    $response = $this->put(route('accounts.update', $account), [
        'name' => 'New Name',
        'currency_id' => $this->currency->id,
        'type' => 'checking',
    ]);

    $response->assertSessionHasErrors(['passcode']);
});

test('update succeeds with correct passcode', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'name' => 'Old Name',
    ]);

    $response = $this->put(route('accounts.update', $account), [
        'name' => 'New Name',
        'currency_id' => $this->currency->id,
        'type' => 'checking',
        'passcode' => '1234',
    ]);

    $response->assertSessionHasNoErrors();
    $account->refresh();
    expect($account->name)->toBe('New Name');
});

test('dashboard balance is masked when hide_dashboard_balance is on', function () {
    $this->user->update(['hide_dashboard_balance' => true]);
    $this->actingAs($this->user);

    Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'balance' => 500,
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Dashboard')->where('totalBalance', null));
});

test('dashboard balance is visible when balance_reveal flag is set', function () {
    $this->user->update(['hide_dashboard_balance' => true]);
    $this->actingAs($this->user);
    session(['balance_reveal' => true]);

    Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'balance' => 500,
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('Dashboard')
            ->whereNot('totalBalance', null)
    );
});

test('toggle hide balance toggles without passcode', function () {
    $this->actingAs($this->user);

    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'currency_id' => $this->currency->id,
        'hide_balance' => false,
    ]);

    $response = $this->post(route('accounts.toggle-hide-balance', $account));
    $response->assertRedirect();

    $account->refresh();
    expect($account->hide_balance)->toBeTrue();

    $response = $this->post(route('accounts.toggle-hide-balance', $account));
    $account->refresh();
    expect($account->hide_balance)->toBeFalse();
});
