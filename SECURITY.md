# Security Policy

## Supported scope
This repository is presented as a recruiter-facing PHP/MySQL B2B RFQ platform and may contain ongoing development work. Treat reported security issues seriously, especially around:

- authentication and session handling
- role-based access control
- file uploads and document access
- customer-to-customer data isolation
- admin actions and audit logging
- SQL query safety
- quotation and RFQ document access

## Reporting a vulnerability
Please do **not** open a public GitHub issue for a suspected vulnerability.

Instead, report it privately through GitHub security reporting when enabled, or contact the repository owner directly with:

- a short summary
- affected file(s) or route(s)
- reproduction steps
- impact level
- suggested mitigation if known

## Expected response
Target handling goals for valid reports:

- acknowledgement within 3 business days
- triage / validation within 7 business days
- remediation planning after impact review

## Safe-harbor expectations
Good-faith reporting is welcome. Please avoid:

- accessing or modifying data that is not your own
- destructive testing
- denial-of-service attempts
- public disclosure before a fix is available

## Security priorities for this project
High-priority classes of issues include:

1. Broken access control between users or company accounts
2. Admin privilege escalation
3. Authentication or session fixation flaws
4. Insecure file upload / download access
5. SQL injection or unsafe query paths
6. Quotation / RFQ document exposure
7. CSRF gaps on state-changing actions
