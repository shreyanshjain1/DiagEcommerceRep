# Contributing

Thanks for contributing.

## Project direction
This repository is positioned as a **B2B RFQ and quotation workflow platform** built with PHP and MySQL. Contributions should strengthen that direction instead of drifting back toward a generic direct-checkout store.

## Preferred contribution types
Strong contributions usually improve one of these areas:

- security hardening
- B2B account and quotation workflows
- admin operations and auditability
- product data realism
- reporting and dashboard quality
- documentation and setup clarity

## Development expectations
Before opening a pull request:

1. Keep changes focused and easy to review.
2. Preserve the existing folder structure unless a restructure is part of the improvement.
3. Add database patch files for schema changes.
4. Update `README.md` when setup or features change.
5. Run PHP syntax checks on changed files.
6. Think through customer-side and admin-side impact.

## Branch and commit guidance
Use clear, business-relevant commit messages, for example:

- `Harden cart actions with ownership checks`
- `Add quote revision history with versioned snapshots`
- `Upgrade admin dashboard with RFQ analytics`

## Pull request checklist
A good PR should include:

- problem being solved
- changed files/modules
- database impact
- screenshots if UI changed
- manual test notes
- rollback notes if relevant

## Schema change rules
For any schema update:

- update `database.sql` for clean installs
- add a dedicated `database_patch_*.sql` file for existing installs
- keep names descriptive and easy to follow

## Security notes
Do not commit:

- real database credentials
- production mail credentials
- private API secrets
- personally sensitive customer documents

## Style guidance
Aim for changes that feel:

- operationally realistic
- secure by default
- recruiter-friendly on GitHub
- consistent with a flagship internal business system
