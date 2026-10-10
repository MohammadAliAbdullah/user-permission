# Running the module on Laragon (Windows)

Database name used everywhere: **`bnsis_permission`** (MySQL/MariaDB via Laragon).

## 1. Copy the project into Laragon's www folder
```
C:\laragon\www\user-permission\
├── app\  config\  database\  docs\  public\   ← copy repo contents here
```

## 2. Create the database and import data
Open **Laragon Terminal** (or MySQL console; default user `root`, empty password):

```bat
mysql -u root -e "CREATE DATABASE IF NOT EXISTS bnsis_permission DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Optionally load the legacy/seed dump that ships with the repo (`db_permission` file — it now creates `bnsis_permission` directly):
```bat
mysql -u root < db_permission
```

Then apply the RBAC migrations (idempotent, additive only):
```bat
mysql -u root < database\migrations\001_auth_rbac_core.sql
mysql -u root < database\migrations\002_admin_panel_links.sql
```

Verify:
```bat
mysql -u root -e "USE bnsis_permission; SHOW TABLES; SELECT COUNT(*) FROM role_permissions;"
```
You should see 19 tables and a non-zero grant count.

## 3. Point Apache at `public/` (recommended)
Create `C:\laragon\etc\apache2\sites-enabled\00_default.user-permission.conf`
(or edit Laragon's virtual host) so the DocumentRoot is the **public** folder:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/laragon/www/user-permission/public"
    ServerName user-permission.test
    <Directory "C:/laragon/www/user-permission/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
Laragon auto-adds `.test` pretty URLs — restart Laragon services after saving.

**Alternative (subfolder URL `http://localhost/user-permission/`):** keep files in
`www\user-permission` and make sure the root `.htaccess` forwards to `public/`, then set the
base URL so links/assets resolve correctly. Edit `config/settings.php` line:
```php
define('BASE_URL', rtrim(getenv('BASE_URL') ?: '/user-permission', '/'));
```

## 4. Database credentials
`config/database.php` defaults already match Laragon:
host `127.0.0.1`, port `3306`, user `root`, password `` (empty), db `bnsis_permission`.
If your MySQL root has a password, either set it in that file or use env vars
(`DB_PASS`, `DB_NAME`, …). Note: on Windows the default socket path in the config is ignored by PDO when using TCP host `127.0.0.1`.

## 5. Open the app
- **http://user-permission.test/** (pretty domain) or **http://localhost/user-permission/** 
- Login: `admin / admin123` (also seeded: `officer / 123456`, `entry / 123456`)
- Schema documentation page: **http://user-permission.test/schema.html**

## Troubleshooting
- **500 Internal Server Error** → check `C:\laragon\logs\apache\error.log` and run
  `php -l public\index.php`. Common cause: serving from repo root without `AllowOverride All`
  (mod_rewrite/.htaccess fails). Use the VirtualHost above instead.
- **"Access denied for user 'root'"** → fix credentials in `config/database.php`.
- **Unknown column 'ORG_ID' on user save** → pull latest code; `sync_user_roles()` now writes
  `DEFAULT_FG` instead of a non-existent `ORG_ID` column in `user_roles`.
