# Pet Grooming Salon

A PHP, PDO, and MySQL application for managing pet owners and pets. User accounts protect the application, and each successful owner or pet change is recorded in an audit log.

## Requirements

- XAMPP with Apache, MySQL/MariaDB, and PHP 7.4 or newer.
- The default local connection in `db.php` is `localhost`, database `pet_grooming_db`, user `root`, and an empty password. Change these values only if your XAMPP MySQL credentials differ.

## Fresh installation

1. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open `http://localhost/phpmyadmin/`.
3. Choose **Import** from the phpMyAdmin home page, select `Database/schema.sql`, and run it. This creates the database and all tables for a fresh install.
4. Put the project folder under `C:\xampp\htdocs\APPDEVOCT19-main` (or adjust the URL to match your folder).
5. Visit `http://localhost/APPDEVOCT19-main/register.php`, create the first account, and then log in.

## Upgrade an existing installation

1. Start Apache and MySQL in XAMPP and open phpMyAdmin.
2. Select the existing `pet_grooming_db` database and use **Export** to save a backup before changing its schema.
3. Select `pet_grooming_db`, choose **Import**, and run `Database/migrations/001_user_auth_audit.sql` once.
4. The migration creates `users` and `audit_logs` and adds nullable audit columns and foreign keys to the existing owner and pet tables. It does not delete or rewrite existing owners or pets; old attribution remains unknown.
5. Do not run this one-time migration again after it succeeds. Do not use `Database/schema.sql` as an upgrade script: `CREATE TABLE IF NOT EXISTS` does not add columns to existing tables.
6. Register an account at `http://localhost/APPDEVOCT19-main/register.php` and log in.

## Using and checking the application

1. Open `http://localhost/APPDEVOCT19-main/` in a browser. A guest is redirected to `login.php`.
2. Register with the required personal details, a unique username and email, and a password. Age is optional. Passwords are stored as PHP password hashes.
3. Log in with either the username or email and password.
4. Use **Manage Pet Owners** to add an owner, edit it, and delete it. Each successful action also appears in **Audit Logs**.
5. Use **Manage Pets** to add a pet linked to an existing owner, edit it, and delete it. Each successful action also appears in **Audit Logs**.
6. Check the Added By / Added At and Last Updated By / Last Updated columns. For an update, the original creator is retained and the acting user is shown as updater. New owner and pet rows created before account tracking was installed display unknown historical attribution.
7. Delete an owner that has pets and check Audit Logs for a DELETE event for the owner and each cascaded pet.
8. **View Owners & Pets** keeps owners with no pets visible and shows attribution for both entity types.
9. Use **Log out**. Directly opening `index.php`, `owners.php`, `pets.php`, `view.php`, or `audit_logs.php` should return you to login.

## Security and database notes

- Registration, login, and logout use CSRF tokens. Logout is a POST action.
- Restricted pages check that the session belongs to an active database user.
- CRUD queries use prepared statements; creator/updater identities come from the authenticated session, never a form field.
- Audit snapshots contain owner/pet data only and omit secret-like fields. Audit rows do not reference business rows, so deletion history remains after a record is removed.
- The owner-to-pets relationship remains one-to-many with `ON DELETE CASCADE`; the application writes pet deletion events before deleting an owner.
