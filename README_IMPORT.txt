IMPORT INSTRUCTIONS (IMPORTANT)

FRESH SETUP
1) In cPanel, create a MySQL database + user and grant ALL PRIVILEGES to that DB.
2) Edit config/db.php or config/db.local.php to match your DB credentials.
3) In phpMyAdmin, use the Import tab and upload database.sql.
4) After import, open /pages/products.php to confirm the catalog loads.

EXISTING DATABASE UPGRADE
1) If your database already exists and you want the consolidated upgrade path, import:
   - database_patch_all.sql
2) This file merges the major schema changes from the old database_patch_*.sql files into one upgrade bundle.
3) Keep database.sql as the fresh-install baseline and database_patch_all.sql as the single upgrade script.

IMPORTANT NOTES
- Use the phpMyAdmin Import tab instead of pasting large SQL files into the SQL editor.
- The consolidated patch is meant for older databases being upgraded toward the current repo structure.
- For recruiter-facing GitHub presentation, using one upgrade bundle is cleaner than showing many scattered patch files.
