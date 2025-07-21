# ejournal Application

`ejournal` is a comprehensive journal management system designed to simplify financial record keeping and reporting. It allows users to manage general journals, master data, accounts, and generate financial reports seamlessly.

## Features

### 1. **Dashboard**
The dashboard serves as the main overview page of the application. It provides users with quick access to key functionalities and an overview of recent activities and financial summaries.

### 2. **General Journals**
This section allows users to manage general journal entries, which are the fundamental records in accounting. You can view, edit, or delete journal entries as necessary.

#### **Sub-features:**
- **Journal Entry**: Manually input new journal transactions directly from the app without importing files.
- **Journal Voucher List**: View all journal vouchers (entries), whether imported or manually entered, with filter options for start and end date.
- **Journal List**: See a bundled list of imported journals.
- **Journal Detail**: Drill down into each imported journal bundle for transaction details.
- **Import General Journals**: Easily import journal entries from external sources (e.g., CSV, Excel).
- **Journal Adjustment / Correction**: Menu for correcting or adjusting existing journal entries, accessible from the Journal Detail view (via the "Adjustment" button).

### 3. **Master Data**
The Master Data feature allows users to manage core data used across the system, such as accounts, account types, and other critical financial parameters.

#### **Sub-features:**
- **Account List**: View and manage all accounts.
- **Import Accounts**: Import accounts from external files for easy setup.
- **Account Types**: Define and categorize account types (e.g., Asset, Liability, Equity, Revenue, Expense).
- **Company Lists**: Add or delete company data in each transaction, either as a supplier or as a customer.

### 4. **Reports**
The Reports section provides various financial reports to help users track and analyze their financial status.

#### **Sub-features:**
- **Balance Sheet (Neraca)**: View a detailed balance sheet that summarizes the financial position of the organization at a specific point in time.
- **Trial Balance (Neraca Saldo)**: View a detailed trial balance that summarizes the financial position of the organization at a specific point in time for all acounts.
- **Ledger (Buku Besar)**: View detailed transaction history for each account.
- **Ledger Detail (Buku Besar Rinci)**: View detailed transaction history for all accounts.
- **Profit and Loss / Income Statement (Laba Rugi)**: Generate a profit and loss statement to analyze revenue, expenses, and net profit over a specific period.

---

## Getting Started

To get started with this application, you will need to clone the repository and set up the environment. Please follow the steps below.

### Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/medLibe/ejournal.git
2. Install dependencies:
    cd ejournal
    npm install # for frontend (Vue.js)
    composer install # for backend (Laravel)
3. Set up the environment variables in .env (for backend and frontend)
4. Run the application:
    ```php artisan serve```
    ```npm run serve```

