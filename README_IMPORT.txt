IMPORT INSTRUCTIONS (IMPORTANT)

1) In cPanel, create a MySQL database + user and grant ALL PRIVILEGES to that DB.
2) Edit config/db.php to match your DB credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS).
3) In phpMyAdmin:
   - Select your database on the left
   - Go to Import
   - Choose database.sql from this project
   - Click Go

If you see an error that starts with the word "Error", it usually means you copied the phpMyAdmin error text into the SQL editor.
Use the Import tab and upload the database.sql file directly.

After import, open /pages/products.php — you should see the full catalog.
