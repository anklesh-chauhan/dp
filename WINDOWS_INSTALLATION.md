# QualiGxP — Windows PC installation notes

Use this checklist to install QualiGxP on a **new Windows 10/11 PC**. Follow the steps in order. Do not skip Docker if you need PDF printing, document preview conversion, or issuance PDFs.

The current development machine uses:

| Item | Value |
| --- | --- |
| PHP | 8.4 (Herd) |
| Laravel | 13 |
| Filament | 5 |
| Database | PostgreSQL 16+ on port `5432` |
| Site URL | `http://docupharma.test` (Herd uses the **folder name**) |
| PDF engine | Gotenberg `8.34.0` in Docker on port `3000` |
| Node | 25 (see `.nvmrc`) |
| Queue | database driver (`php artisan queue:listen`) |

Copy the **source code**, not `vendor`, `node_modules`, or `.env`. Those are rebuilt on the new PC.

---

## 1. Windows prerequisites

1. Sign in as a user who can install software (Administrator for the first run).
2. Install Windows updates.
3. Enable virtualization in BIOS/UEFI (required for Docker Desktop / WSL2):
   - Intel: VT-x
   - AMD: SVM
4. Install **Git for Windows**: https://git-scm.com/download/win  
   During setup, keep “Git from the command line and also from 3rd-party software”.
5. Install the latest **Microsoft Visual C++ Redistributable** (x64):  
   https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist
6. Open **PowerShell** (not Command Prompt) for the rest of this guide.

Check Git:

```powershell
git --version
```

---

## 2. Install Docker Desktop (Gotenberg / PDF)

QualiGxP renders controlled-document PDFs and converts uploaded Office files through **Gotenberg**. That service runs in Docker. The project file is `compose.yaml`.

1. Install **WSL 2** if Windows asks for it:

```powershell
wsl --install
```

Reboot when Windows asks.

2. Download **Docker Desktop for Windows**: https://www.docker.com/products/docker-desktop/
3. Install it. Choose **WSL 2** as the backend.
4. Start Docker Desktop and wait until it says **Docker Desktop is running**.
5. Confirm:

```powershell
docker version
docker compose version
```

If either command fails, Docker is not running or not on `PATH`. Start Docker Desktop and open a **new** PowerShell window.

---

## 3. Install Laravel Herd

Herd provides PHP, nginx, Composer, and `*.test` sites. Do **not** run `php artisan serve` for this app on a Herd machine.

1. Download Herd for Windows: https://herd.laravel.com/windows
2. Install and launch Herd.
3. Confirm it is on `PATH` (new PowerShell window):

```powershell
herd --version
php -v
composer -V
```

4. Install **PHP 8.4** in Herd (required):

```powershell
herd php:list
herd isolate 8.4
php -v
```

`php -v` must show **8.4.x**.

5. Enable these PHP extensions in Herd (Herd UI → PHP → 8.4 → extensions, or `herd ini`):

| Required | Why |
| --- | --- |
| `pdo_pgsql`, `pgsql` | PostgreSQL |
| `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl` | Laravel |
| `gd` or `imagick` | Images / Filament |
| `zip` | Composer, document import ZIP |
| `intl` | Localization |
| `bcmath` | Numeric work |
| `exif` | Image metadata |

Restart Herd after changing extensions.

6. Confirm PostgreSQL drivers:

```powershell
php -m | Select-String pgsql
```

You must see `pgsql` and `pdo_pgsql`.

---

## 4. Install Node.js

The project `.nvmrc` is **25**. Herd can install Node, or use https://nodejs.org.

```powershell
node -v
npm -v
```

`node -v` should be **v22 or newer**. Prefer **v25** to match this repo.

---

## 5. Install PostgreSQL

This application’s working setup is **PostgreSQL**, not SQLite.

### Option A — PostgreSQL installer (matches the current PC)

1. Download PostgreSQL 16 or 17 for Windows: https://www.enterprisedb.com/downloads/postgres-postgresql-downloads
2. Install with:
   - Port: **5432**
   - Superuser: **postgres**
   - Password: **postgres** (or pick another and use the same value in `.env`)
   - Locale: default
3. Leave **pgAdmin** installed if you want a GUI.
4. Add `psql` to `PATH` if the installer offers it (`C:\Program Files\PostgreSQL\16\bin`).

Create databases:

```powershell
psql -U postgres -c "CREATE DATABASE docupharma;"
psql -U postgres -c "CREATE DATABASE docupharma_testing;"
```

When prompted, enter the postgres password.

### Option B — PostgreSQL in Docker

If you prefer not to install PostgreSQL on Windows, run it in Docker **in addition to Gotenberg**. Example (only if you skip Option A):

```powershell
docker run -d --name qualigxp-postgres --restart unless-stopped -e POSTGRES_PASSWORD=postgres -e POSTGRES_USER=postgres -p 5432:5432 postgres:16
docker exec -it qualigxp-postgres psql -U postgres -c "CREATE DATABASE docupharma;"
docker exec -it qualigxp-postgres psql -U postgres -c "CREATE DATABASE docupharma_testing;"
```

Do **not** bind port 5432 twice (installer + Docker).

Confirm:

```powershell
psql -U postgres -c "\l"
```

You should see `docupharma` and `docupharma_testing`.

---

## 6. Copy or clone the project

Herd parks sites from a folder. The **folder name** becomes the `.test` host.

Recommended path (same as this machine):

```text
C:\Herd\docupharma
```

That serves **http://docupharma.test**.

### Clone from Git

```powershell
New-Item -ItemType Directory -Force -Path C:\Herd | Out-Null
cd C:\Herd
git clone <YOUR_GIT_URL> docupharma
cd C:\Herd\docupharma
```

### Copy from USB / shared drive

Copy the project folder to `C:\Herd\docupharma`.

**Do not copy:**

- `vendor\`
- `node_modules\`
- `.env` (contains machine-specific keys; create a new one)
- `public\hot`
- `public\build` (rebuild with npm)

**Do copy:** source, `composer.json`, `package.json`, `compose.yaml`, `database\`, `app\`, etc.

If Herd does not park `C:\Herd` automatically:

```powershell
cd C:\Herd\docupharma
herd link docupharma
herd park C:\Herd
herd sites
```

Open:

```text
http://docupharma.test
```

You should get a Laravel/QualiGxP page or a PHP error until `.env` and Composer are done. A 404 from nginx means the site is not linked.

---

## 7. Application `.env`

From the project folder:

```powershell
cd C:\Herd\docupharma
copy .env.example .env
```

Edit `.env` in a text editor. Use these **working local values** (not the sqlite defaults in `.env.example`):

```env
APP_NAME=QualiGxP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://docupharma.test

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=docupharma
DB_USERNAME=postgres
DB_PASSWORD=postgres

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log

LARAVEL_PDF_DRIVER=gotenberg
GOTENBERG_URL=http://localhost:3000
GOTENBERG_VERSION=8.34.0
CONTROLLED_DOCUMENT_PDF_LAYOUT_VERSION=6

QUALIGXP_ENTITLEMENT_SOURCE=environment
QUALIGXP_MODULES=dms,qms,ai
QUALIGXP_DATE_FORMAT=d/m/Y
QUALIGXP_DATETIME_FORMAT="d/m/Y H:i"
QUALIGXP_TIME_FORMAT=H:i
```

Module combinations:

```env
QUALIGXP_MODULES=dms
QUALIGXP_MODULES=dms,qms
QUALIGXP_MODULES=dms,ai
QUALIGXP_MODULES=dms,qms,ai
```

QMS and AI both require DMS.

### Optional AI keys (only if the AI module is enabled)

Leave these empty if you are not demoing AI. Do not copy keys from another PC into documentation.

```env
GEMINI_ENABLED=true
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash

OPENAI_ENABLED=false
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini

OLLAMA_ENABLED=false
OLLAMA_URL=http://localhost:11434
OLLAMA_MODEL=qwen2.5:3b
```

Generate the app key:

```powershell
php artisan key:generate --no-interaction
```

---

## 8. PHP packages and frontend

Still in `C:\Herd\docupharma`:

```powershell
composer install --no-interaction
npm install
npm run build
```

If Composer runs the wrong PHP:

```powershell
herd composer install --no-interaction
```

---

## 9. Database migrate and seed

```powershell
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
php artisan storage:link --no-interaction
php artisan optimize:clear --no-interaction
```

Seeding creates lookup data, roles, permissions, templates, and local users. It only creates `admin@example.com` when `APP_ENV=local`.

If QMS menus are missing after enabling `qms` in `.env`:

```powershell
php artisan optimize:clear --no-interaction
php artisan db:seed --class=QmsModuleSeeder --no-interaction
```

Sign out and sign back in after changing modules or roles.

---

## 10. Start Gotenberg (PDF)

From the project folder, with Docker Desktop running:

```powershell
cd C:\Herd\docupharma
docker compose up -d
docker compose ps
```

This uses `compose.yaml`:

- Image: `gotenberg/gotenberg:8.34.0-chromium`
- Port: **3000**

Check:

```powershell
curl http://localhost:3000/health
```

A healthy response means PDF generation can work.

Equivalent one-off command (only if you are not using Compose):

```powershell
docker run --rm -d --name gotenberg -p 3000:3000 gotenberg/gotenberg:8.34.0-chromium
```

Prefer `docker compose up -d` so it restarts with Windows.

---

## 11. Queue worker (required for PDF and AI jobs)

Printing, original-file preview conversion, and AI template generation use the **database queue**.

Keep this PowerShell window open while using the app:

```powershell
cd C:\Herd\docupharma
php artisan queue:listen --tries=1
```

Without this, PDFs stay queued and AI jobs never finish.

---

## 12. Open the application

| URL | What |
| --- | --- |
| http://docupharma.test | Landing page |
| http://docupharma.test/admin | Filament admin (QualiGxP) |

### Local administrator

```text
Email:    admin@example.com
Password: password
```

Change this password before any non-local use. Confirm the user has the **sop administrator** role under **Core · Identity & Access → Users**.

### QA demo users (password `password` for all)

| Email | Role |
| --- | --- |
| Administrator@example.com | sop administrator |
| Maker@example.com | sop maker |
| Checker@example.com | sop checker |
| Approver@example.com | sop approver |
| DocumentController@example.com | document controller |
| RecordExecutor@example.com | gmp record executor |
| ProductionSupervisor@example.com | production supervisor |
| QaReviewer@example.com | qa reviewer |
| LogMaker@example.com | log maker |

---

## 13. Daily start on the new PC

1. Start **Docker Desktop** and wait until it is running.
2. Start **Herd**.
3. Confirm Gotenberg:

```powershell
cd C:\Herd\docupharma
docker compose up -d
```

4. Start the queue worker:

```powershell
php artisan queue:listen --tries=1
```

5. Open http://docupharma.test/admin

PostgreSQL and Herd usually start with Windows if you installed them as services.

After pulling new code:

```powershell
cd C:\Herd\docupharma
git pull
composer install --no-interaction
npm install
npm run build
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
php artisan optimize:clear --no-interaction
```

---

## 14. Optional: run tests

Tests use database `docupharma_testing` (see `phpunit.xml`), user `postgres` / `postgres`.

```powershell
php artisan test --compact
```

If tests fail with “database does not exist”, create `docupharma_testing` (step 5).

---

## 15. What must be running

| Service | How you know it is up |
| --- | --- |
| Herd / nginx / PHP 8.4 | http://docupharma.test loads |
| PostgreSQL | `psql -U postgres -c "SELECT 1"` |
| Docker Desktop | whale icon in the tray |
| Gotenberg | `curl http://localhost:3000/health` |
| Queue worker | `php artisan queue:listen` window is open |

---

## 16. Common problems

**Site does not open / DNS fails for `*.test`**  
Herd is not running, or the folder is not parked. Run `herd sites` and `herd link docupharma`.

**`could not find driver` / PostgreSQL**  
Enable `pdo_pgsql` and `pgsql` in Herd PHP 8.4, then restart Herd.

**`SQLSTATE[08006] connection refused`**  
PostgreSQL is not running, or port is not 5432. Check Windows Services for `postgresql-x64-16`.

**Landing CSS looks unstyled**  
Run `npm run build` (or `npm run dev` and leave it running).

**PDF / Print does nothing**  
Docker is off, Gotenberg is not on port 3000, or the queue worker is not running.

**AI draft assistant fails**  
`QUALIGXP_MODULES` must include `ai`, and a provider key must be set. Then `php artisan optimize:clear`.

**QMS menus missing**  
Set `QUALIGXP_MODULES=dms,qms` (or `dms,qms,ai`), seed `QmsModuleSeeder`, sign out and back in.

**Wrong PHP version**  
`php -v` must be 8.4. Use `herd isolate 8.4` inside the project folder.

**Port 3000 already in use**  
Another Gotenberg or Node process is bound. `netstat -ano | findstr :3000` then stop that process, or change both the Docker port and `GOTENBERG_URL`.

**Copied `vendor` from the old PC**  
Delete `vendor` and run `composer install` on the new PC.

---

## 17. Files you should not commit or copy blindly

- `.env` — secrets and machine URLs
- API keys (`GEMINI_API_KEY`, `OPENAI_API_KEY`, …)
- `vendor\`, `node_modules\`
- Database dumps that contain production data

The local admin password `password` is **development only**.
