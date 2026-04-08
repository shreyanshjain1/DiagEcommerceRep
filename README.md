# Pharmastar Diagnostics – E-commerce (HTML+PHP+CSS+JS)

**Stack:** Procedural PHP (PDO), MySQL, Vanilla JS, CSS.  
**Features:** Auth (signup/login), products with filters/search, product details with tabs, CSRF-protected forms, cart, checkout (orders), inquiries with email, responsive UI.

## Quick Setup (cPanel / Localhost)
1. Create a MySQL database (e.g., `pharmastar_db`).
2. Import `database.sql`.
3. Edit `config/db.php` with your DB credentials and admin email.
4. Upload the whole folder to your document root. If using a subfolder, adjust includes/links or place contents directly in `public_html`.
5. Ensure `uploads/` is writable if you plan to upload files.
6. Login with seeded accounts (from `database.sql`):
   - **Admin** (for `/admin`):
     - Email: `admin@pharmastar.local`
     - Password: `Admin@123`
   - You can create customer accounts via `/pages/signup.php`.

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

## GitHub Actions

- `PHP Lint` workflow validates PHP syntax on every push and pull request.
- Helps the repo present as actively maintained and review-ready for hiring teams.
