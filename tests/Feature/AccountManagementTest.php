<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Models\Currency;
use App\Services\AccountService;
use App\Repositories\AccountRepository;
use App\Http\Requests\AccountRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Currency $currency;
    protected AccountService $accountService;
    protected AccountRepository $accountRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test currency
        $this->currency = Currency::factory()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ]);

        // Initialize services
        $this->accountService = app(AccountService::class);
        $this->accountRepository = app(AccountRepository::class);
    }

    public function test_it_can_create_an_account_through_service()
    {
        $accountData = [
            'name' => 'Test Savings Account',
            'currency_id' => $this->currency->id,
            'type' => 'savings',
            'initial_balance' => 1000.00,
            'description' => 'Test account for savings',
            'is_active' => true,
        ];

        $account = $this->accountService->createAccount($accountData);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('Test Savings Account', $account->name);
        $this->assertEquals(1000.00, $account->balance);
        $this->assertEquals($this->user->id, $account->user_id);
        $this->assertTrue($account->is_active);
    }

    public function test_it_can_update_an_account_through_service()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'Original Name',
            'balance' => 500.00,
        ]);

        $updateData = [
            'name' => 'Updated Account Name',
            'description' => 'Updated description',
            'is_active' => false,
        ];

        $updatedAccount = $this->accountService->updateAccount($account, $updateData);

        $this->assertEquals('Updated Account Name', $updatedAccount->name);
        $this->assertEquals('Updated description', $updatedAccount->description);
        $this->assertFalse($updatedAccount->is_active);
        $this->assertEquals(500.00, $updatedAccount->balance); // Balance should remain unchanged
    }

    /** @test */
    public function it_can_retrieve_accounts_through_repository()
    {
        // Create multiple accounts
        Account::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        $accounts = $this->accountRepository->getAll();

        $this->assertCount(3, $accounts);
        $this->assertTrue($accounts->every(fn($account) => $account->user_id === $this->user->id));
    }

    /** @test */
    public function it_can_filter_accounts_by_type()
    {
        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'type' => 'checking',
        ]);

        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'type' => 'savings',
        ]);

        $checkingAccounts = $this->accountRepository->getByType('checking');
        $savingsAccounts = $this->accountRepository->getByType('savings');

        $this->assertCount(1, $checkingAccounts);
        $this->assertCount(1, $savingsAccounts);
        $this->assertEquals('checking', $checkingAccounts->first()->type);
        $this->assertEquals('savings', $savingsAccounts->first()->type);
    }

    /** @test */
    public function it_can_search_accounts_by_name()
    {
        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'My Savings Account',
        ]);

        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'Business Checking',
        ]);

        $searchResults = $this->accountRepository->search('Savings');

        $this->assertCount(1, $searchResults);
        $this->assertEquals('My Savings Account', $searchResults->first()->name);
    }

    /** @test */
    public function it_can_get_account_statistics()
    {
        // Create accounts with different balances
        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'balance' => 1000.00,
            'is_active' => true,
        ]);

        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'balance' => 500.00,
            'is_active' => false,
        ]);

        $statistics = $this->accountRepository->getStatistics();

        $this->assertEquals(2, $statistics['total_count']);
        $this->assertEquals(1, $statistics['active_count']);
        $this->assertEquals(1, $statistics['inactive_count']);
        $this->assertEquals(1500.00, $statistics['total_balance']);
        $this->assertEquals(750.00, $statistics['average_balance']);
    }

    public function test_it_can_deactivate_an_account()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'is_active' => true,
        ]);

        $result = $this->accountService->deactivateAccount($account);

        $this->assertTrue($result);
        $this->assertFalse($account->fresh()->is_active);
    }

    public function test_it_can_activate_an_account()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'is_active' => false,
        ]);

        $activatedAccount = $this->accountService->activateAccount($account);

        $this->assertTrue($activatedAccount->is_active);
    }

    /** @test */
    public function test_it_validates_unique_account_names()
    {
        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'name' => 'Existing Account',
        ]);

        $isUnique = $this->accountRepository->isNameUnique('Existing Account');
        $this->assertFalse($isUnique);

        $isUniqueNew = $this->accountRepository->isNameUnique('New Account Name');
        $this->assertTrue($isUniqueNew);
    }

    public function test_it_can_get_accounts_for_transfer()
    {
        $sourceAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'is_active' => true,
        ]);

        $targetAccount = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'is_active' => true,
        ]);

        Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
            'is_active' => false, // This should be excluded
        ]);

        $transferAccounts = $this->accountRepository->getForTransfer($sourceAccount->id);

        $this->assertCount(1, $transferAccounts);
        $this->assertEquals($targetAccount->id, $transferAccounts->first()->id);
    }

    public function test_it_provides_comprehensive_account_summary()
    {
        $account = Account::factory()->create([
            'user_id' => $this->user->id,
            'currency_id' => $this->currency->id,
        ]);

        $summary = $this->accountService->getAccountSummary($account);

        $this->assertArrayHasKey('account', $summary);
        $this->assertArrayHasKey('statistics', $summary);
        $this->assertArrayHasKey('recent_transactions', $summary);
        $this->assertArrayHasKey('balance_trend', $summary);
        $this->assertEquals($account->id, $summary['account']->id);
    }
}