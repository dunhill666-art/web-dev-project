# AeroGlide — Airline Booking & Travel Platform
### Full Symfony 7.4 Enterprise Stack & Production Docker Environment

> **Academic Finals 2026–2027 Edition**: Complete integration of the official Symfony 7.4 WebApp architecture, Doctrine ORM 3.x, Symfony Console CLI, and Web Debug Profiler, orchestrated via Docker Compose for zero-configuration evaluation on Windows, macOS, or Linux, with instant **Ngrok Public Tunneling** for seamless cross-device professor review.

---

## 🚀 Quick Start for Evaluators & Professors

### Prerequisites
- **Docker Desktop** (Windows / macOS) or **Docker Engine + Docker Compose v2** (Linux). Ensure Docker Desktop is active.
- *(Optional for public tunneling)* **Ngrok CLI** installed on the host, or run the dockerized tunnel service.

### 1-Step Startup Command

From the root project folder (`c:\wamp64\www\website`), execute:

```bash
docker compose up --build -d
```

Docker will automatically:
1. Compile the **PHP 8.3 Apache** image with `pdo_mysql`, `intl`, `zip`, `opcache`, `mod_rewrite`, `mod_remoteip`, and Composer CLI.
2. Spin up the isolated **MariaDB 10.11** database engine and initialize all relational tables and initial data via `database/init.sql`.
3. Auto-seed the system with **2 Administrators**, **2 Staff Accounts**, **1 Demo Traveler**, and dynamically generate forward-looking flight schedules across Philippine routes.
4. Launch the standalone **phpMyAdmin** visual management console on port `8081`.
5. Boot the **Symfony 7.4 MicroKernel** and compile all routes, Twig extensions, and Doctrine entity mappings.

---

## 🌐 Exposing Live via Ngrok (Professor Review Across Any Device)

To allow professors or evaluators to test the system live on their mobile phones, tablets, or remote laptops without being on the same local Wi-Fi:

### Option A: Host Ngrok CLI (Recommended — 1 Second Startup)
Open a new terminal window in the project root and run:
```bash
# Using the pre-built launcher script:
.\scripts\start-ngrok.ps1
# Or in Windows CMD / double-click:
scripts\start-ngrok.bat
# Or directly via command:
ngrok http 8080
```
Ngrok will display a public forwarding URL:
```text
Forwarding  https://xxxx-xx-xxx-xx.ngrok-free.app -> http://localhost:8080
```
Simply copy and share the `https://xxxx.ngrok-free.app` URL with your professor.

### Option B: Dockerized Ngrok Service
If you prefer running everything inside Docker, add your free auth token to `.env` (`NGROK_AUTHTOKEN=your_token`) and run:
```bash
docker compose --profile tunnel up -d
```
Visit the local Ngrok web dashboard at **http://localhost:4040** to see your live public HTTPS link and monitor incoming requests.

### 🛡️ Why Ngrok Works Seamlessly Without Setup Errors
When exposing through reverse proxies or Ngrok tunnels, PHP applications often break due to untrusted host headers, mixed-content HTTP/HTTPS redirects, or IP filtering. AeroGlide resolves these automatically:
1. **Symfony Trusted Proxies & Headers:** `public/index.php` and `docker-compose.yml` configure `Request::setTrustedProxies()` to trust private subnets and reverse proxy forward headers (`X-Forwarded-Proto`, `X-Forwarded-Host`, `X-Forwarded-Port`, `X-Forwarded-For`).
2. **Dynamic HTTPS Detection:** Apache's `mod_setenvif` automatically intercepts `X-Forwarded-Proto: https` and sets `HTTPS=on`. All links, cookies, and session redirects preserve secure HTTPS without loops.
3. **Flexible Trusted Hosts:** `TRUSTED_HOSTS` permits any `.ngrok-free.app`, `.ngrok.io`, or custom test domain without throwing Symfony `UntrustedHostException`.
4. **Domain-Agnostic URLs:** Core navigation and assets utilize `ag_base_url()` relative paths (`/destinations/...`, `/asset/...`), ensuring every asset loads cleanly regardless of host domain.

---

## 🌐 Application Endpoints

| Service / Tool | Local URL | Description |
| :--- | :--- | :--- |
| **Main Website** | [http://localhost:8080](http://localhost:8080) | Search flights, explore Philippine destinations, book travel |
| **Admin Portal** | [http://localhost:8080/admin/](http://localhost:8080/admin/) | Unified Symfony 7.4 administrative dashboard & analytics |
| **Admin API Endpoint** | [http://localhost:8080/admin/api/stats](http://localhost:8080/admin/api/stats) | JSON live metrics endpoint powered by `DashboardController` |
| **Symfony Web Profiler** | [http://localhost:8080/_profiler/](http://localhost:8080/_profiler/) | Live Symfony developer toolbar, database queries, and routing inspector |
| **phpMyAdmin GUI** | [http://localhost:8081](http://localhost:8081) | Visual DB management console (auto-authenticated to MariaDB) |
| **MariaDB Direct Port** | `localhost:3307` | External DB port for DBeaver, TablePlus, DataGrip, or VS Code |
| **Ngrok Web Inspector** | [http://localhost:4040](http://localhost:4040) | Live HTTP request inspector when Ngrok tunnel is active |

*(When accessing over Ngrok, simply replace `http://localhost:8080` with your active `https://xxxx.ngrok-free.app` URL).*

---

## 🔑 Pre-Configured Test Accounts & Credentials

The system is pre-seeded with accounts across all role tiers (`ROLE_ADMIN`, `ROLE_STAFF`, `ROLE_USER`) for grading and evaluation. Users can log in using either their **Login Email** or **Username** at [http://localhost:8080/user/login.php](http://localhost:8080/user/login.php) (or `https://xxxx.ngrok-free.app/user/login.php`).

### Evaluation Accounts

| Role Level | Account Name | Login Email | Username | Password | Permissions & System Scope |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Administrator (1)** | AeroGlide Admin | `admin@aeroglide.com` | `admin` | `admin2026!` | **Full Administrator Privileges:** Unrestricted access to analytics dashboard (`/admin`), flight booking approvals/cancellations, permanent booking deletion, financial revenue reports (`admin/revenue.php`), user role management (`admin/users.php`), and system activity logs (`admin/logs.php`). |
| **Administrator (2)** | AeroGlide Admin 2 | `admin2@aeroglide.com` | `admin2` | `Admin@1234` | **Full Administrator Privileges:** Second full admin account for multi-admin testing; complete management access across all modules, revenue analytics, and system audit logs. |
| **Staff Member (1)** | Flight Staff 1 | `staff1@aeroglide.com` | `staff1` | `Staff@1234` | **Operations & Booking Management:** Dedicated flight management portal (`admin/bookings.php`). Review customer reservations, **Accept Flight** (locks to `Confirmed`), and **Cancel Flight** (finalizes to `Cancelled`). *Restricted:* Blocked from revenue reports, user accounts, system logs, booking deletion, and full dashboard. |
| **Staff Member (2)** | Flight Staff 2 | `staff2@aeroglide.com` | `staff2` | `Staff@1234` | **Operations & Booking Management:** Second staff account for concurrent staff workflow evaluation; identical operational capabilities and security restrictions as Staff 1. |
| **Traveler (Demo)** | Juan Dela Cruz | `juan@aeroglide.com` | `demo` | `password123` | **Customer Experience:** Search live flights and packages, complete checkout with default `Pending` status, view itinerary under *My Bookings*, and submit cancellation requests. |

### Database Credentials (phpMyAdmin / Direct SQL Client)
| Parameter | Value |
| :--- | :--- |
| **Host** | `db` *(inside Docker network)* or `127.0.0.1:3307` *(from host machine)* |
| **Database Name** | `aeroglide` |
| **Root User** | `root` / `root_secret` |
| **App User** | `aeroglide` / `aeroglide_secret` |

---

## 💾 Database Persistence & Automated Seeding Architecture

1. **Volume Persistence (`db_data`):**
   - The MariaDB container stores all table files in a named Docker volume (`db_data`). Data, user registrations, flight bookings, and audit records persist indefinitely across container restarts, stops, and system reboots.
2. **Automated Schema & Seed Initialization:**
   - On first startup or fresh container provisioning, MariaDB automatically executes `/docker-entrypoint-initdb.d/01-init.sql` (mounted from `database/init.sql`).
   - The container entrypoint script (`docker/entrypoint.sh`) verifies relational integrity and invokes `database/db_setup.php` if schedules are empty, populating dynamic flights for the next 30 days.
3. **Zero-Configuration Environment Isolation:**
   - Environment variables in `docker-compose.yml` (`DB_HOST=db`, `DB_PORT=3306`) decouple the application completely from local machine paths or hardcoded WAMP configuration.
4. **Complete Reset (Wipe & Re-seed):**
   - To completely reset the database to a pristine initial state at any time:
     ```bash
     docker compose down -v && docker compose up --build -d
     ```

---

## ⚡ Symfony 7.4 Console & Architecture Commands

The project includes the full Symfony CLI console suite (`bin/console`). You can run these commands directly inside the Docker web container:

```bash
# 1. Inspect registered routes (including /admin, /admin/dashboard, and /admin/api/stats)
docker compose exec web php bin/console debug:router

# 2. Validate Doctrine ORM entity mappings
docker compose exec web php bin/console doctrine:schema:validate --skip-sync

# 3. Inspect registered Twig extensions, filters, and functions
docker compose exec web php bin/console debug:twig

# 4. Clear and rebuild Symfony cache
docker compose exec web php bin/console cache:clear

# 5. Inspect autowired services and dependencies
docker compose exec web php bin/console debug:autowiring
```

---

## 🏗️ Architecture & Component Integration

| Layer | Component | Implementation |
| :--- | :--- | :--- |
| **Framework Kernel** | Symfony 7.4 LTS | `src/Kernel.php` utilizing `MicroKernelTrait`, `public/index.php` runtime entry point |
| **Database / ORM** | Doctrine ORM 3.x + DBAL | MariaDB 10.11 mapping 4 core entities: `User`, `Booking`, `Flight`, and `AdminLog` |
| **Admin Controller** | `DashboardController` | Extends `AbstractController`, mapped via `#[Route('/admin')]` and `#[Route('/admin/api/stats')]` |
| **Security Layer** | `symfony/security-bundle` | `App\Entity\User` implements `UserInterface` & `PasswordAuthenticatedUserInterface` |
| **Frontend Assets** | `symfony/asset-mapper` | AssetMapper + Hotwired Stimulus & Turbo (`importmap.php`, `assets/`) |
| **Templating Engine** | Twig 3.x | Custom extension `AeroGlideExtension` providing `peso`, `format_date`, and asset helpers |
| **Hybrid Routing** | Apache `.htaccess` | Serves physical assets/legacy pages directly, rewrites virtual routes to `public/index.php` |
| **Tunnel & Proxy** | RemoteIP & TrustedProxies | Forwarded header normalization for Ngrok tunnels, Cloudflare, and Docker bridge |

---

## 🛠️ Docker Management Commands

| Action | Command |
| :--- | :--- |
| **Start Stack (Background)** | `docker compose up -d` |
| **Rebuild & Start Stack** | `docker compose up --build -d` |
| **Start Stack with Ngrok Tunnel** | `docker compose --profile tunnel up -d` |
| **Stop Stack** | `docker compose stop` |
| **Resume Stack** | `docker compose start` |
| **Tear Down Stack** | `docker compose down` |
| **Full Reset (Wipe & Re-seed DB)** | `docker compose down -v && docker compose up --build -d` |
| **View Live Web Server Logs** | `docker compose logs -f web` |
| **View Database Logs** | `docker compose logs -f db` |
| **Open Bash Shell in Web Container** | `docker compose exec web bash` |

---

## 💡 Troubleshooting & FAQs

### 1. Port 8080, 8081, or 3307 is already in use
If another application on your host machine occupies any of these ports, adjust the host port mapping on the left side in `docker-compose.yml`:
```yaml
ports:
  - "8090:80"   # Changes web port to 8090
```

### 2. Ngrok "Invalid Host header" or "Untrusted Host"
AeroGlide automatically accepts all incoming host headers through `TRUSTED_HOSTS=^.*$` and normalizes HTTPS headers via `Request::setTrustedProxies()`. If your Ngrok session expires or drops, simply restart the tunnel (`ngrok http 8080`) to generate a new URL.

### 3. Docker Daemon connection error
If you see `failed to connect to docker API`, ensure Docker Desktop is open and running in your taskbar/system tray.
