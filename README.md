# Pharmastar Diagnostics – E-commerce (HTML+PHP+CSS+JS)

**Stack:** Procedural PHP (PDO), MySQL, Vanilla JS, CSS.  
**Features:** Auth (signup/login), products with filters/search, product details with tabs, CSRF-protected forms, cart, checkout (orders), inquiries with email, responsive UI.

## Quick Setup (cPanel / Localhost)
1. Create a MySQL database (e.g., `pharmastar_db`).
2. Import `database.sql`.
3. Copy `config/db.local.example.php` to `config/db.local.php` and add your DB credentials and admin email there.
4. Upload the whole folder to your document root. If using a subfolder, adjust includes/links or place contents directly in `public_html`.
5. Ensure `uploads/` is writable if you plan to upload files.
6. Create or update your admin account after import. Do not keep default demo credentials in production.
7. You can create customer accounts via `/pages/signup.php`.

## Folder Structure
- `config/` – DB connection & CSRF utilities  
- `includes/` – shared header/footer/helpers  
- `pages/` – home, products, product, cart, checkout, login, signup, inquiry, order-confirmation  
- `actions/` – auth, cart, checkout, inquiry handlers  
- `assets/` – CSS/JS + base no-image.png  
- `uploads/` – for product images & documents  
- `database.sql` – schema + seed

## Notes
- Email uses `mail()`; configure server mail or replace with SMTP later.
- Product images/documents are seeded to `/assets/no-image.png` and `/uploads/docs/*.pdf` (placeholder brochures) – replace with your official images/brochures.
- Order number format: `PITC-YYYYMMDD-######`.