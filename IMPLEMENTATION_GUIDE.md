# Implementation Guide: User Entity and Accountability

## Question 1: How did you implement the User Entity?

### Step 1: Creating the User Entity and updating the database schema

The `users` table in `Database/schema.sql` stores each user's first name, last name, unique username and email, phone, address, optional age, password hash, account status, and creation/update timestamps. `Database/migrations/001_user_auth_audit.sql` adds this table to an existing installation and adds nullable `added_by`, `added_at`, `last_updated_by`, and `last_updated` columns to both `pet_owners` and `pets`. The user attribution columns reference `users.user_id`; they are nullable so records created before the migration retain unknown historical attribution. The migration also creates `audit_logs`, which keeps deletion history after an owner or pet row is removed.

### Step 2: Implementing registration, login, logout, sessions, and page protection

`register.php` validates required profile fields and matching passwords, checks username/email uniqueness, and stores only a `password_hash()` result. `login.php` accepts a username or email, checks the account status, verifies credentials using `password_verify()`, regenerates the session ID, and stores the user ID and display name in the session. `auth.php` provides the shared guard and verifies that the session user still exists and is active. `index.php`, `owners.php`, `pets.php`, `view.php`, and `audit_logs.php` use that guard. `logout.php` accepts a CSRF-protected POST, clears the session and cookie, and returns to the login page. `db.php` supplies the PDO connection, output escaping, session initialization, and CSRF helpers.

### Step 3: Integrating user accountability into CRUD operations and audit logging

`functions/add.php`, `functions/edit.php`, and `functions/delete.php` use the authenticated session's user ID for creator, updater, and action attribution. Inserts, updates, and deletes each run with their audit event inside a database transaction. Updates preserve original creator fields and set the last updater and timestamp. Deleting an owner captures its pets and writes a DELETE event for each pet before the database cascade removes them. `functions/audit.php` contains reusable logging, snapshot, and audit retrieval helpers. `functions/view.php` joins user names for the owner, pet, and combined relationship pages, while `audit_logs.php` presents audit events and their old/new record snapshots.

## Question 2: User Entity SQL schema

This is the `users` table definition used by the fresh-install schema and upgrade migration:

```sql
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(254) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    address VARCHAR(255) NOT NULL,
    age TINYINT UNSIGNED NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Question 3: Screenshots checklist

Capture each item yourself from the running application. Keep the entire desktop screen visible, including enough browser content to show the page and the relevant result. Do not crop or fabricate evidence. For update evidence, use two registered accounts so the creator and a different updater are both visible. Never expose or capture a plaintext password.

### User registration — minimum 3 screenshots

1. Registration form filled with the profile fields visible; leave password fields obscured.
2. Login page showing the successful-registration message after submitting the form.
3. Registration attempt with an existing username or email, showing the friendly duplicate-account message.

### User logging in — minimum 3 screenshots

1. Login page with the username or email entered and the password field masked.
2. Successful login on the home page showing the signed-in user's name and navigation.
3. Login attempt with an incorrect password showing the generic invalid-credentials message.

### Parent entity insertion — minimum 3 screenshots

1. Pet Owners page with the new owner's form filled before submission.
2. Pet Owners list after submission, showing the new row, Added By, and Added At.
3. Audit Logs with that owner's INSERT event expanded to show the new values.

### Parent entity updating — minimum 3 screenshots

1. Owner row before the update, showing User A as Added By.
2. Edit Owner form with the changed information visible before submission, while logged in as User B.
3. Updated owner row and its UPDATE audit event showing User A retained as creator, User B as updater, and old/new values.

### Parent entity deletion — minimum 2 screenshots

1. Owner deletion confirmation shown after selecting Delete for an owner that has pets.
2. Audit Logs after confirming, showing DELETE events for the owner and each cascaded pet with the responsible user's name.

### Child entity insertion — minimum 3 screenshots

1. Pets page with pet details and an existing owner selected before submission.
2. Pet list after submission, showing the linked owner, Added By, and Added At.
3. Audit Logs with the pet's INSERT event expanded to show its new values.

### Child entity updating — minimum 3 screenshots

1. Pet row before editing, showing User A as Added By.
2. Edit Pet form with changed pet data visible before submission, while logged in as User B.
3. Updated pet row and its UPDATE event showing User A retained as creator and User B as updater, with old/new values.

### Child entity deletion — minimum 2 screenshots

1. Pet deletion confirmation shown after selecting Delete.
2. Audit Logs after confirming, showing the pet DELETE event, record ID, timestamp, and responsible user.

### Logging out — minimum 2 screenshots

1. An authenticated application page showing the signed-in user's name and the Log out control.
2. Login page after logout, with a protected URL such as `index.php` entered or attempted and the redirect to login visible.

Also include the SQL schema evidence required by your instructor and the GitHub repository link in the final submission.
