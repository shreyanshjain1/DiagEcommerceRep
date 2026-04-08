# Pharmastar Diagnostics RFQ Platform

![PHP](https://img.shields.io/badge/PHP-Procedural-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Relational-4479A1?logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Deployed-0A7EA4)
![Workflow](https://img.shields.io/badge/Workflow-RFQ%20First-1F8A70)
![Security](https://img.shields.io/badge/Security-CSRF%20%7C%20Session%20Hardening-8E44AD)
![CI](https://img.shields.io/badge/GitHub%20Actions-PHP%20Lint-2088FF?logo=githubactions&logoColor=white)

A **B2B diagnostics catalog and quotation workflow platform** built with **procedural PHP, MySQL, vanilla JavaScript, and CSS**.

This project started as a traditional e-commerce catalog and was refocused into an **RFQ-first commercial workflow** for medical and diagnostics sales, where customers browse products, submit quotation requests, and receive tracked quotation responses through an admin back office.

---

## Why this project stands out

This is not just a basic CRUD catalog.

It includes a stronger business workflow layer with:
- customer authentication and session hardening
- RFQ cart and quotation request submission
- admin quotation management and quote sending
- quote approval / rejection workflow
- quote revision history and quote document snapshots
- RFQ timeline and status history
- audit logging for admin activity
- B2B company accounts and multi-contact structure
- company billing / shipping address profiles
- supplier metadata and commercial product fields
- admin pagination, filtering, and analytics dashboards
- GitHub workflows, issue templates, contribution docs, and repo governance files

---

## Core product modules

### Customer side
- signup / login / logout
- product catalog with search, filters, and product detail pages
- RFQ cart workflow
- quotation request submission
- inquiry form flow
- customer RFQ history
- stored quotation document access
- quote approval and rejection actions
- customer-visible RFQ timeline
- company profile and saved address visibility

### Admin side
- executive RFQ dashboard
- RFQ pipeline tracking
- quote sending and quote revision snapshots
- inquiry tracking
- product management
- supplier/commercial product metadata
- user management with role/status controls
- company accounts overview
- company addresses overview
- audit log foundation across major admin actions
- admin filtering and pagination across key modules

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

## Quick setup

### Local / cPanel setup
1. Create a MySQL database.
2. Import `database.sql`.
3. Copy `config/db.local.example.php` to `config/db.local.php` if present in your branch, then update credentials.
4. Ensure writable upload folders where needed.
5. Point your local server or cPanel document root at the project.
6. Log in with your seeded admin account if your imported schema includes it.

### Existing database patch flow
If you are applying incremental upgrades rather than reimporting the base schema, apply the included `database_patch_*.sql` files in order as needed.

---

## Suggested GitHub repository topics

```text
php mysql procedural-php b2b ecommerce rfq quotation inventory admin-dashboard crm business-software cpanel
```

---

## Repo workflow and engineering polish

This repository includes:
- PHP lint workflow via GitHub Actions
- markdown/link hygiene workflow
- pull request template
- issue templates
- CODEOWNERS
- SECURITY.md
- CONTRIBUTING.md

These help the project present as a more mature and maintainable engineering repo.

---

## Best positioning for this repo

The strongest way to present this project is as:

> **A procedural PHP + MySQL B2B diagnostics RFQ platform with quotation workflows, auditability, company accounts, and admin operations tooling.**

That framing is much stronger than calling it only a basic e-commerce site.

---

## Notes

- The current platform is positioned **RFQ-first**, not as a pure online card-checkout storefront.
- Some data and assets in the repo are placeholders for demonstration and portfolio purposes.
- Email delivery uses the native PHP mail layer in the current implementation and can be upgraded to SMTP later.

---

## Future roadmap ideas

Good next upgrades for portfolio value:
- export/reporting layer
- order conversion after quote approval
- granular permissions matrix
- richer supplier management
- scheduled reminders / cron workflows
- PDF generation service hardening
- admin audit log viewer UI

---

## License / usage

This repository is presented as a portfolio and internal business system showcase. Review and adapt usage based on your own deployment and commercial needs.
