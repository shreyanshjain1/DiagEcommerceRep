# Pharmastar Diagnostics RFQ Platform

![PHP](https://img.shields.io/badge/PHP-Procedural-777BB4?logo=php&logoColor=white)
<<<<<<< HEAD
![MySQL](https://img.shields.io/badge/MySQL-8%2B-4479A1?logo=mysql&logoColor=white)
![RFQ Workflow](https://img.shields.io/badge/Workflow-RFQ%20%2B%20Quotation-0f766e)
![Admin](https://img.shields.io/badge/Admin-B2B%20Operations-1d4ed8)
![Status](https://img.shields.io/badge/Repo-Recruiter%20Ready-111827)
=======
![MySQL](https://img.shields.io/badge/MySQL-Relational-4479A1?logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Deployed%20-0A7EA4)
![Workflow](https://img.shields.io/badge/Workflow-RFQ%20First-1F8A70)
![Security](https://img.shields.io/badge/Security-CSRF%20%7C%20Session%20Hardening-8E44AD)
![CI](https://img.shields.io/badge/GitHub%20Actions-PHP%20Lint-2088FF?logo=githubactions&logoColor=white)
>>>>>>> 918a32e574357145bc8506016459086334a0a296

A recruiter-facing **RFQ-first B2B diagnostics platform** built with procedural PHP and MySQL. The project is structured like a real internal commerce and quotation system rather than a simple catalog demo.

## Why this repo stands out

- RFQ-first customer flow instead of generic cart-only commerce
- Admin quotation operations with quote sending and status handling
- Customer inquiries, product catalog, admin product management, and reporting surfaces
- Export center for RFQs, quote line items, inquiries, users, products, and company-account data
- GitHub-ready repo polish with workflows, templates, security and contribution docs

## Core platform areas

- Customer authentication and account flows
- Product catalog with categories, product detail pages, and brochures
- RFQ cart and quotation pipeline
- Inquiry handling for pre-sales and product-specific questions
- Admin dashboard for operations visibility
- Admin export/reporting layer with CSV downloads

## Export and reporting layer

This repo includes an **Export Center** for recruiter-facing business realism.

Included CSV exports:
- RFQs
- Quotation line items
- Inquiries
- Users
- Products
- Company accounts (graceful placeholder if the company account table is not present yet)

Why this matters:
- makes the system feel more operational and complete
- shows understanding of business reporting requirements
- gives the repo stronger “real business app” signals on GitHub

## Stack

<<<<<<< HEAD
- Procedural PHP (PDO)
- MySQL / MariaDB
- Vanilla JavaScript
- CSS
=======
---

## Business workflow

```text
Product discovery
   -> RFQ cart
   -> RFQ submission
   -> Admin review
   -> Quotation issued
   -> Quote revisions (if needed)
   -> Customer approves or rejects
   -> RFQ timeline + audit trace remain visible
```

This makes the repo much closer to a **B2B sales operations platform** than a simple storefront.

---

## Tech stack

- **Backend:** Procedural PHP with PDO
- **Database:** MySQL
- **Frontend:** HTML, CSS, Vanilla JavaScript
- **Email:** native `mail()` integration in current implementation
- **Repo quality:** GitHub Actions, PR template, issue templates, CODEOWNERS, SECURITY.md, CONTRIBUTING.md

---

## Key schema additions in the current repo

The project now includes stronger business-oriented tables such as:
- `audit_logs`
- `quote_status_history`
- `quote_revisions`
- `quote_revision_items`
- `quote_documents`
- `company_accounts`
- `company_account_contacts`
- `company_account_addresses`
- `suppliers`
- password reset support tables

---

## Security and integrity improvements

Recent repo upgrades include:
- cart item ownership checks
- safer password reset token handling
- local DB config pattern instead of committing credentials directly
- product search fulltext support with fallback logic
- session hardening and secure session regeneration
- admin activity audit logging
- inactive-user login blocking
- role/status-aware admin user controls

---

## Admin dashboard highlights

The admin landing page is positioned as an RFQ operations dashboard and includes:
- total RFQs and stage counts
- quoted value snapshot
- response health metrics
- RFQ trend visibility
- watchlists for stale or expiring work
- top requested products
- company account activity visibility
- inquiry and stock-risk awareness

---

## Project structure

```text
config/                 Database and configuration files
includes/               Shared helpers, session, layout utilities
actions/                Customer-side handlers
admin/                  Admin pages
admin_actions/          Admin workflow handlers
pages/                  Customer-facing pages
assets/                 Frontend assets
uploads/                Uploaded product / quote documents
.github/                Workflows, templates, repo governance
database.sql            Full base schema
*.sql patches           Incremental schema upgrades
```

---
>>>>>>> 918a32e574357145bc8506016459086334a0a296

## Quick setup

1. Create a MySQL database.
2. Import `database.sql`.
3. Configure `config/db.php` with local credentials.
4. Upload the project to your document root or subfolder.
5. Make sure `uploads/` is writable if you plan to store files.

## Seeded admin account

- Email: `admin@pharmastar.local`
- Password: `Admin@123`

## Folder structure

- `admin/` – admin pages and reporting views
- `actions/` – customer-facing form handlers
- `admin_actions/` – admin-side handlers
- `pages/` – public/customer pages
- `includes/` – helpers, settings, mail helpers, shared layout
- `config/` – DB and security config
- `assets/` – CSS/JS/images
- `uploads/` – product documents and media
- `database.sql` – schema and seed data

## Good recruiter signals already present

- business workflow orientation
- B2B quotation use case
- reporting/export thinking
- admin operations pages
- GitHub repo maintenance signals

## Notes

- Email currently uses `mail()` and can be swapped to SMTP later.
- Some newer recruiter-facing patches may introduce optional tables that are safe to add incrementally.
- This repo is intentionally positioned as a **PHP business systems / internal operations** style project.
