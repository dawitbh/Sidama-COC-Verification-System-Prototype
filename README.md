# Sidama COC Verification System

This is an upgraded front-end + back-end skeleton for your Sidama COC Certificate Verification System.

- Modern Bootstrap 5 UI
- Centralised PDO database connection
- Basic role system (`roles` table + `manage-users.php`)
- Certificate activation / expiry dates and status badges
- CSV-based bulk upload with multiple upload types (append/update/replace/test)
- Public verification page (`find-result.php` + `result.php`)

## Setup

1. Import your existing `sidamacvs.sql` and then apply the migration SQL described in your planning notes:
   - add `activation_date`, `expiry_date`, `status` to `tblstudents`
   - create `roles` table and link it from `admin`.-- in COC agency -- superadmin is the only one who creates or assign the roles and give permission. 

2. Update `includes/config.php` with your database credentials.-- i put the database configration separately so it makes the confugration very easy

3. Ensure your web server points to this folder

4. Log in with an admin account from the `admin` table and assign roles using `manage-users.php`.
