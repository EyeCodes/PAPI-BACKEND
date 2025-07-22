# 🚀 Project Update - July 2025

This README documents recent changes to the Laravel + Filament project and provides instructions for interns.

---

## ✅ What Changed

### 🔒 Filament Shield Integration
- Installed **[Filament Shield](https://github.com/bezhansalleh/filament-shield)** for role-based access control.
- Generated CRUD permissions for all existing Filament resources.
- All resources (`CompanyResource`, `LoyaltyProgramResource`, `LoyaltyProgramRuleResource`, etc.) now respect user roles and permissions for:
  - Viewing
  - Creating
  - Editing
  - Deleting
- Bulk actions and page routes are permission-aware.

### ⚡ Location Helper Optimization
- `GetLocationDataHelper` now uses **Laravel Cache** for JSON data to improve performance.
- Removed repeated disk reads for Philippine location data.
- Cache automatically updates when the JSON file changes or after running `php artisan cache:clear`.

### 🏢 Company Resource Refactor
- Simplified form and table layouts.
- Business types and location dropdowns load faster.
- Added toggleable active status column in the table.
- Added permission checks for CRUD operations.

---

### Best Practices

- Use snake_case for table names (e.g., loyalty_program_rules, not LoyaltyProgramRules).
- Use StudlyCase for model names.

- Always include nullable() where applicable.
- Optionally you can use foreignId()->constrained() and optionally adding a ->cascade('UPDATE | DELETE') for creating foreign key constraints
- Use Laravel Cache for heavy data (like large JSONs or API calls)

