<?php

use App\Models\Activity;
use App\Models\Currency;
use App\Models\User;
use App\Services\PasscodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->currency = Currency::factory()->create([
        'code' => 'USD',
        'symbol' => '$',
        'is_active' => true,
    ]);
    $this->passcodeService = app(PasscodeService::class);
});

test('user can set a passcode', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('passcode.update'), [
        'current_password' => 'password',
        'passcode' => '1234',
        'passcode_confirmation' => '1234',
    ]);

    $response->assertSessionHasNoErrors();
    $this->user->refresh();
    expect($this->user->passcode_hash)->not->toBeNull();
    expect(Hash::check('1234', $this->user->passcode_hash))->toBeTrue();
});

test('user can change passcode', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $response = $this->post(route('passcode.update'), [
        'current_password' => 'password',
        'passcode' => '5678',
        'passcode_confirmation' => '5678',
    ]);

    $response->assertSessionHasNoErrors();
    $this->user->refresh();
    expect(Hash::check('5678', $this->user->passcode_hash))->toBeTrue();
});

test('user can disable passcode', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $response = $this->delete(route('passcode.destroy'), [
        'current_password' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $this->user->refresh();
    expect($this->user->passcode_hash)->toBeNull();
});

test('wrong current password rejects passcode set', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('passcode.update'), [
        'current_password' => 'wrong_password',
        'passcode' => '1234',
        'passcode_confirmation' => '1234',
    ]);

    $response->assertSessionHasErrors(['current_password']);
});

test('passcode must be 4-6 digits', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('passcode.update'), [
        'current_password' => 'password',
        'passcode' => '12',
        'passcode_confirmation' => '12',
    ]);

    $response->assertSessionHasErrors(['passcode']);
});

test('verify correct passcode sets session', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $response = $this->post(route('passcode.verify'), [
        'passcode' => '1234',
    ]);

    $response->assertSessionHasNoErrors();
    expect(session('passcode_verified'))->toBeTrue();
});

test('verify incorrect passcode fails', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $response = $this->post(route('passcode.verify'), [
        'passcode' => '0000',
    ]);

    $response->assertSessionHasErrors(['passcode']);
    expect(session('passcode_verified'))->toBeNull();
});

test('verify works only when passcode is set', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('passcode.verify'), [
        'passcode' => '1234',
    ]);

    $response->assertSessionHasErrors(['passcode']);
});

test('verify is rate limited after five attempts with a validation error', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('passcode.verify'), [
            'passcode' => '0000',
        ]);
    }

    $response = $this->post(route('passcode.verify'), [
        'passcode' => '0000',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors(['passcode' => 'too_many_attempts']);
    $response->assertSessionHasErrors(['passcode_retry_after']);
});

test('setting a passcode is logged as activity', function () {
    $this->actingAs($this->user);

    $this->post(route('passcode.update'), [
        'current_password' => 'password',
        'passcode' => '1234',
        'passcode_confirmation' => '1234',
    ]);

    expect(Activity::where('user_id', $this->user->id)->orderByDesc('id')->first())
        ->action->toBe('passcode_set')
        ->subject_type->toBe('passcode')
        ->description->toBe('Set app passcode');
});

test('changing a passcode is logged as activity', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $this->post(route('passcode.update'), [
        'current_password' => 'password',
        'passcode' => '5678',
        'passcode_confirmation' => '5678',
    ]);

    expect(Activity::where('user_id', $this->user->id)->orderByDesc('id')->first())
        ->action->toBe('passcode_changed');
});

test('disabling a passcode is logged as activity', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $this->delete(route('passcode.destroy'), [
        'current_password' => 'password',
    ]);

    expect(Activity::where('user_id', $this->user->id)->orderByDesc('id')->first())
        ->action->toBe('passcode_disabled')
        ->subject_type->toBe('passcode');
});

test('toggling dashboard balance is logged as activity', function () {
    $this->actingAs($this->user);

    $this->post(route('passcode.dashboard-balance'));

    expect(Activity::where('user_id', $this->user->id)->orderByDesc('id')->first())
        ->action->toBe('dashboard_balance_hidden')
        ->subject_type->toBe('passcode');

    $this->post(route('passcode.dashboard-balance'));

    expect(Activity::where('user_id', $this->user->id)->orderByDesc('id')->first())
        ->action->toBe('dashboard_balance_shown');
});

test('successful verification is logged as activity', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $this->post(route('passcode.verify'), [
        'passcode' => '1234',
    ]);

    expect(Activity::where('user_id', $this->user->id)->orderByDesc('id')->first())
        ->action->toBe('passcode_verified')
        ->subject_type->toBe('passcode');
});

test('failed verification is logged as activity', function () {
    $this->user->update(['passcode_hash' => Hash::make('1234')]);
    $this->actingAs($this->user);

    $this->post(route('passcode.verify'), [
        'passcode' => '0000',
    ]);

    expect(Activity::where('user_id', $this->user->id)->orderByDesc('id')->first())
        ->action->toBe('passcode_failed')
        ->subject_type->toBe('passcode');
});
