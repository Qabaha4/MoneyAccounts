# Reusable Account Management Model

This document outlines the comprehensive account management system that handles both account creation and editing functionality with a clean, maintainable architecture.

## Architecture Overview

The account management system follows a layered architecture pattern:

1. **Models** - Data representation and relationships
2. **Form Requests** - Validation and authorization
3. **Services** - Business logic and operations
4. **Repositories** - Data access abstraction
5. **Controllers** - HTTP request handling

## Components

### 1. Account Model (`app/Models/Account.php`)
- **Existing model** with user relationships and balance management
- Includes global scopes for user filtering
- Handles balance calculations and updates
- Relationships with User, Currency, and Transaction models

### 2. Form Request (`app/Http/Requests/AccountRequest.php`)
- Centralized validation rules for account creation and editing
- Authorization checks for account ownership
- Custom validation messages and attribute names
- Separate methods for creation and update data preparation

**Key Features:**
```php
// Validation rules
'name' => 'required|string|max:255'
'currency_id' => 'required|exists:currencies,id'
'type' => 'required|in:checking,savings,credit,investment'
'initial_balance' => 'nullable|numeric|min:0'
'is_active' => 'boolean'

// Data preparation methods
getValidatedDataForCreation() // Includes initial_balance
getValidatedDataForUpdate()   // Excludes balance fields
```

### 3. Account Service (`app/Services/AccountService.php`)
- Encapsulates all account business logic
- Handles account CRUD operations
- Provides account statistics and summaries
- Manages account ownership and security

**Key Methods:**
```php
createAccount(array $data): Account
updateAccount(Account $account, array $data): Account
deleteAccount(Account $account): bool
deactivateAccount(Account $account): Account
activateAccount(Account $account): Account
getAccountSummary(Account $account): array
getDashboardStatistics(): array
```

### 4. Account Repository (`app/Repositories/AccountRepository.php`)
- Abstracts data access layer
- Provides complex query methods
- Handles filtering and searching
- Optimizes database queries with eager loading

**Key Methods:**
```php
findById(int $id): ?Account
getAll(array $filters = []): Collection
getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
search(string $term): Collection
getByType(string $type): Collection
getStatistics(): array
isNameUnique(string $name, ?int $excludeId = null): bool
```

### 5. Account Management Controller (`app/Http/Controllers/AccountManagementController.php`)
- Handles HTTP requests for account operations
- Supports both web and API responses
- Integrates all components seamlessly
- Provides comprehensive error handling

## Usage Examples

### Creating an Account

```php
// Using the service directly
$accountService = app(AccountService::class);
$account = $accountService->createAccount([
    'name' => 'My Savings Account',
    'currency_id' => 1,
    'type' => 'savings',
    'initial_balance' => 1000.00,
    'description' => 'Personal savings account',
    'is_active' => true
]);

// Using the controller (handles validation automatically)
POST /accounts
{
    "name": "My Savings Account",
    "currency_id": 1,
    "type": "savings",
    "initial_balance": 1000.00,
    "description": "Personal savings account",
    "is_active": true
}
```

### Updating an Account

```php
// Using the service
$account = Account::find(1);
$updatedAccount = $accountService->updateAccount($account, [
    'name' => 'Updated Account Name',
    'description' => 'Updated description',
    'is_active' => false
]);

// Using the controller
PUT /accounts/1
{
    "name": "Updated Account Name",
    "description": "Updated description",
    "is_active": false
}
```

### Retrieving Accounts with Filters

```php
// Using the repository
$accountRepository = app(AccountRepository::class);

// Get all active accounts
$activeAccounts = $accountRepository->getAll(['active' => true]);

// Get accounts by type
$savingsAccounts = $accountRepository->getByType('savings');

// Search accounts
$searchResults = $accountRepository->search('savings');

// Get paginated accounts with filters
$accounts = $accountRepository->getPaginated(15, [
    'active' => true,
    'type' => 'checking',
    'min_balance' => 100
]);
```

### Getting Account Statistics

```php
// Dashboard statistics
$stats = $accountService->getDashboardStatistics();

// Repository statistics
$repoStats = $accountRepository->getStatistics();

// Account-specific summary
$account = Account::find(1);
$summary = $accountService->getAccountSummary($account);
```

## Integration with Views/Components

### Blade Components
```php
// In a Blade template
@foreach($accounts as $account)
    <div class="account-card">
        <h3>{{ $account->name }}</h3>
        <p>Balance: {{ $account->currency->symbol }}{{ number_format($account->balance, 2) }}</p>
        <span class="badge {{ $account->is_active ? 'active' : 'inactive' }}">
            {{ $account->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
@endforeach
```

### Vue.js Components
```javascript
// In a Vue component
export default {
    data() {
        return {
            accounts: [],
            filters: {
                active: true,
                type: '',
                search: ''
            }
        }
    },
    methods: {
        async fetchAccounts() {
            const response = await axios.get('/accounts', {
                params: this.filters
            });
            this.accounts = response.data.accounts;
        },
        
        async createAccount(accountData) {
            const response = await axios.post('/accounts', accountData);
            this.accounts.push(response.data.account);
        }
    }
}
```

### API Integration
```javascript
// JavaScript API client
class AccountAPI {
    static async getAccounts(filters = {}) {
        const response = await fetch('/accounts?' + new URLSearchParams(filters));
        return response.json();
    }
    
    static async createAccount(data) {
        const response = await fetch('/accounts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return response.json();
    }
    
    static async updateAccount(id, data) {
        const response = await fetch(`/accounts/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return response.json();
    }
}
```

## Testing

The system includes comprehensive tests (`tests/Feature/AccountManagementTest.php`) that verify:

- Account creation and validation
- Account updates and modifications
- Repository filtering and searching
- Service business logic
- Statistics and summaries
- Security and ownership checks

## Benefits

1. **Reusability** - Components can be used across different views and applications
2. **Maintainability** - Clear separation of concerns and single responsibility
3. **Testability** - Each component can be tested independently
4. **Flexibility** - Easy to extend with new features and functionality
5. **Security** - Built-in authorization and ownership checks
6. **Performance** - Optimized queries and eager loading

## Extension Points

The architecture allows for easy extension:

1. **Custom Validation Rules** - Add new validation rules in AccountRequest
2. **Business Logic** - Extend AccountService with new methods
3. **Query Methods** - Add specialized queries in AccountRepository
4. **Event Handling** - Add model events for account operations
5. **API Endpoints** - Extend controller with new endpoints
6. **Caching** - Add caching layer in repository methods

This reusable account management model provides a solid foundation for any application requiring account management functionality while maintaining clean architecture principles.