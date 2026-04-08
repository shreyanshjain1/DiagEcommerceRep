# Pharmastar Diagnostics RFQ Platform

![PHP](https://img.shields.io/badge/PHP-Procedural-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-RFQ--First-0f766e)
![Security](https://img.shields.io/badge/Security-CSRF%20%7C%20Password%20Reset%20Tokens-0ea5e9)
![Admin](https://img.shields.io/badge/Admin-Quotation%20Workflow-2563eb)
![License](https://img.shields.io/badge/Use-Internal%20Project-black)

A production-minded **B2B RFQ and quotation platform** for a diagnostics catalogue workflow.

This project is built for businesses that do not want a direct card checkout flow. Instead, it focuses on a cleaner operational journey:

**catalogue browsing → RFQ list → quotation review → admin pricing → customer follow-up**

---

## Why this repo exists

Many B2B medical and diagnostics businesses do not sell purely like a retail storefront. Pricing may depend on:
- quantity
- shipping and installation
- warranty and lead time
- distributor or importer coordination
- customer requirements and documentation

This repo is structured around that reality.

---

## Core highlights

- Customer authentication with signup, login, and password reset
- Product catalogue with categories, filters, search suggestions, and product detail pages
- RFQ list flow in place of a direct public checkout journey
- Customer quotation history and RFQ tracking
- Admin RFQ review with unit pricing and commercial fields
- Admin product management with media/document support
- Inquiry handling module
- CSRF-protected form actions
- Safer local database configuration pattern
- FULLTEXT-backed search suggestion support with fallback behavior

---

## Product positioning

This repository is now intentionally positioned as an **RFQ-first platform**.

### Primary live workflow
1. Customer browses products
2. Customer adds products to RFQ list
3. Customer submits RFQ / quotation request
4. Admin reviews request and prepares pricing
5. Customer receives quote and follow-up

### Legacy compatibility
A legacy order module still exists for compatibility and historical records, but the main intended business flow is **RFQ-first**, not public self-checkout.

---

## Tech stack

- **Backend:** Procedural PHP with PDO
- **Database:** MySQL
- **Frontend:** HTML, CSS, Vanilla JavaScript
- **Email:** PHP `mail()` based notifications
- **Architecture style:** lightweight multi-page PHP app with admin and customer areas

---

## Feature map

### Customer-facing
- Home page and public catalogue
- Product listing and product detail pages
- RFQ list / cart experience
- RFQ submission flow
- My RFQs area
- Inquiry/contact pages
- Wishlist and compare support
- Profile page
- Password reset flow

### Admin-facing
- RFQ queue and RFQ detail review
- Quote pricing and commercial terms management
- Product CRUD
- Product media and brochure handling
- Inquiry review
- User list
- Settings page
- Legacy order visibility for historical records

---

## Repo structure

```text
config/            database config, CSRF helpers
includes/          shared helpers, header/footer, mailer, settings
pages/             public and customer pages
actions/           customer-facing handlers
admin/             admin pages and dashboard
admin_actions/     admin form handlers
assets/            CSS, JS, images
uploads/           uploaded files and product documents
database.sql       base schema and seed data
```

---

## Quick setup

### 1) Create your database
Create a MySQL database in your local environment or hosting panel.

### 2) Import the schema
Import:

```sql
SOURCE database.sql;
```

If you are applying incremental fixes to an existing environment, also run the relevant patch files you added during upgrades.

### 3) Configure database credentials safely
The app now prefers a local config file that should **not** be committed.

Copy:

```bash
config/db.local.example.php
```

into:

```bash
config/db.local.php
```

Then edit it with your real database values.

### 4) Upload the project
Upload the project to your web root or subfolder.

### 5) Ensure writable upload paths
Make sure the `uploads/` directory is writable if product media or documents will be uploaded.

### 6) Access the app
- public site: `index.php`
- admin area: `admin/`

---

## Environment and configuration notes

The database loader now checks in this order:
1. `config/db.local.php`
2. environment variables
3. placeholder fallback values

That means you can keep secrets outside the tracked repo while still having a simple deployment path.

---

## Security improvements already applied

- cart update/remove ownership checks
- password reset tokens stored securely as hashes
- reset tokens are expiring and one-time use
- local DB config pattern added to avoid committing credentials
- search suggestions now support FULLTEXT with fallback behavior

---

## Suggested data model areas already represented

- users
- products
- carts and cart items
- inquiries
- quotes and quote items
- orders and order items (legacy compatibility)
- settings

---

## Current limitations

This repo is much stronger than a basic student CRUD demo, but it is still not the final form of a mature B2B commerce platform.

Still worth improving further:
- audit logs
- admin role management
- quote revision history
- quote approval / rejection flow
- stronger dashboard analytics
- pagination and filters on larger admin datasets
- supplier and lead-time metadata
- company account depth

---

## Suggested GitHub pin description

**RFQ-first B2B diagnostics catalogue and quotation platform built with PHP and MySQL, with customer RFQ flows, admin quotation handling, product management, and production-minded upgrade work.**

---

## Suggested topics

```text
php mysql pdo ecommerce rfq quotation admin-panel inventory b2b diagnostics crm-like internal-tools
```

---

## Deployment notes

For cPanel-style deployment:
- import schema in phpMyAdmin or MySQL CLI
- create `config/db.local.php`
- verify mail delivery if using inquiry or password reset email features
- verify `uploads/` permissions
- replace placeholder product docs and images with real assets

---

## Internal use note

This project is designed around a real business workflow and is best treated as a **commercial internal platform / client system base**, not a generic public open-source package.
