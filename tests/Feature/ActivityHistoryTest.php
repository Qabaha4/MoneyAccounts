<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ActivityHistoryTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->currency = Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ]);
    }

    public function test_it_logs_account_creation(): void
    {
        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'Test Account',
        ]);

        $this->assertEquals(1, Activity::count());
        $this->assertEquals('created', Activity::first()->action);
        $this->assertEquals(Account::class, Activity::first()->subject_type);
    }

    public function test_it_logs_transaction_creation_and_update(): void
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        Activity::where('user_id', $this->user->id)->delete();

        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 50.00,
            'transaction_date' => now(),
        ]);

        $createdActivity = Activity::where('subject_type', Transaction::class)->where('action', 'created')->first();
        $this->assertNotNull($createdActivity);
        $this->assertEquals(1, Activity::where('subject_type', Transaction::class)->where('action', 'created')->count());
        $this->assertNotNull($createdActivity->metadata);
        $this->assertEquals($account->id, $createdActivity->metadata['account_id']);
        $this->assertEquals($account->name, $createdActivity->metadata['account_name']);
        $this->assertEquals(50.00, $createdActivity->metadata['amount']);
        $this->assertEquals('expense', $createdActivity->metadata['type']);

        $transaction->update(['amount' => 75.00]);

        $updatedActivity = Activity::where('subject_type', Transaction::class)->where('action', 'updated')->first();
        $this->assertNotNull($updatedActivity);
        $this->assertEquals(1, Activity::where('subject_type', Transaction::class)->where('action', 'updated')->count());
        $this->assertEquals(2, Activity::where('subject_type', Transaction::class)->count());
        $this->assertNotNull($updatedActivity->metadata);
        $this->assertEquals($account->id, $updatedActivity->metadata['account_id']);
        $this->assertEquals($account->name, $updatedActivity->metadata['account_name']);
        $this->assertEquals(75.00, $updatedActivity->metadata['amount']);
        $this->assertEquals('expense', $updatedActivity->metadata['type']);
    }

    public function test_it_shows_activity_on_history_page(): void
    {
        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'My Account',
        ]);

        $response = $this->get('/activity');

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('Activity/Index')
                ->has('activities.data', 1);
        });
    }

    public function test_it_filters_by_subject_type(): void
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        Activity::where('user_id', $this->user->id)->delete();

        Transaction::create([
            'user_id' => $this->user->id,
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 100.00,
            'transaction_date' => now(),
        ]);

        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'Filter Target',
        ]);

        // 1 account created + 1 transaction created (no balance-update activity)
        $this->assertEquals(2, Activity::count());
        $this->assertEquals(0, Activity::where('action', 'updated')->count());

        $filtered = Activity::where('subject_type', Account::class)->get();
        $this->assertEquals(1, $filtered->count());

        $response = $this->get('/activity?subject_type=' . urlencode(Account::class));
        $response->assertInertia(function ($page) {
            $page->has('activities.data', 1);
        });
    }

    public function test_it_scopes_to_current_user(): void
    {
        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $response = $this->get('/activity');

        $response->assertInertia(function ($page) {
            $page->has('activities.data', 0);
        });
    }
}
