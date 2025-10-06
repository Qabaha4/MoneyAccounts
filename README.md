# Money Accounts

A simple personal finance management application built with Laravel and Vue.js. Track your accounts, manage transactions, and monitor your financial health with an intuitive dashboard.

## Features

- 💰 **Multi-Account Management** - Create and manage multiple financial accounts
- 📊 **Transaction Tracking** - Record income, expenses, and transfers
- 🌍 **Multi-Currency Support** - Handle different currencies with conversion
- 📈 **Dashboard Overview** - Real-time balance and transaction summaries
- 🔄 **Account Transfers** - Transfer funds between your accounts
- 🌐 **Internationalization** - English and Arabic language support

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
- [Features](#features)
- [Contributing](#contributing)
- [License](#license)

## Installation

### Requirements
- PHP 8.2+
- Composer
- Node.js & npm
- MySQL/PostgreSQL

### Quick Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/Qabaha4/MoneyAccounts.git
   cd MoneyAccounts
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database in `.env`**
   ```env
   DB_DATABASE=money_accounts
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate --seed
   ```

6. **Build assets and start**
   ```bash
   npm run build
   php artisan serve
   ```

Visit `http://localhost:8000` to get started!

## Usage

### Getting Started
1. **Create an Account**: Go to Accounts → Add Account
2. **Add Transactions**: Use the dashboard quick actions to record income/expenses
3. **Transfer Funds**: Move money between your accounts
4. **View Dashboard**: Monitor your financial overview

### Account Types
- Checking, Savings, Credit, Investment, Cash, Other

### Transaction Types
- **Income**: Money coming in
- **Expense**: Money going out  
- **Transfer**: Move between accounts

### Common Commands
```bash
# Run tests
php artisan test

# Clear cache
php artisan cache:clear

# Development server
npm run dev
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if needed
5. Submit a pull request

### Guidelines
- Follow PSR-12 coding standards
- Write clear commit messages
- Add tests for new features
- Ensure all tests pass

## License

This project is licensed under the MIT License.

---

**Built with Laravel & Vue.js** <mcreference link="https://github.com/Qabaha4/MoneyAccounts" index="0">0</mcreference>