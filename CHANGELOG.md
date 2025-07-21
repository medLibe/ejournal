# Changelog

## [1.0.3] - 2025-07-21
### Added
- **Report Menu Enhancements**:
  - Introduced **Trial Balance (Neraca Saldo)** report
  - Added **Ledger Detail (Buku Besar Rinci)** report

### Improved
- **Negative Value Formatting**: Changed display format of negative values from `-1.000.000` to `(1.000.000)`
- **Report Table UX**: Table headers in report views now remain visible even when no data is shown (e.g., before applying filters)
- **Division Input for MAC Database**: A division selection dropdown now appears for users accessing MAC-specific data

### Fixed
- **Sidebar Bug**: Resolved layout/display issue occurring in the sidebar navigation

---

## [1.0.2] - 2025-07-15
### Added
- **Dashboard Info Cards**: Introduced new summary cards on the dashboard for:
  - Revenue
  - Expense
  - Cash/Bank
  - General Ledger
  - Department
- **Company Master Menu**: Added a new "Company" menu for managing journal-related companies.
  - Users can now add and delete companies
  - Mark companies as either *Supplier* or *Customer* for journal reference

### Improved
- **Journal Import Performance**: Optimized journal import process using a Golang-based worker, significantly improving speed and responsiveness.

---

## [1.0.1] - 2025-06-15
### Added
- **Manual Journal Entry**: Users can now input journal transactions directly without importing files.
- **Journal Voucher List**: Added a consolidated view for all journal vouchers (both imported and manual), including date range filtering.
- **Detailed Journal Bundle View**: Implemented Journal List and Journal Detail navigation for imported bundles.
- **Journal Adjustment/Correction Menu**: Correction and adjustment functionality is now accessible via the Journal Detail page.
- Improved UI for editing journal rows: Double-click to edit individual cells (account, debit, or credit) with seamless switching between debit/credit, including "unset" default on new rows.
- Enhanced UX: Debit and credit columns only display values when set, otherwise showing “-”.
- Bug fixes and refinements for journal entry interaction, table editing, and total calculation.

---

## [1.0.0] - 2025-05-08
### Added
- Initial project setup with Vue (frontend) and Laravel (backend)
- Core features implemented:
  - Chart of Account
  - General Ledger
  - Report: Ledger, Balance Sheet, Income Statement
