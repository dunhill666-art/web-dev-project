# AeroGlide — Airline Booking & Travel Platform
### Full Symfony 7.4 Enterprise Stack & Production Docker Environment

> **Academic Finals 2026–2027 Edition**: Complete integration of the official Symfony 7.4 WebApp architecture, Doctrine ORM 3.x, Symfony Console CLI, and Web Debug Profiler, orchestrated via Docker Compose for zero-configuration evaluation on Windows, macOS, or Linux.

---

## 🚀 Quick Start for Evaluators & Professors

### Prerequisites
- **Docker Desktop** (Windows / macOS) or **Docker Engine + Docker Compose v2** (Linux).
- Ensure Docker Desktop is active before running commands.

### 1-Step Startup Command

From the root project folder (`c:\wamp64\www\website`), execute:

```bash
docker compose up --build
```
*(To run in detached background mode, add `-d`: `docker compose up --build -d`)*

Docker will automatically:
1. Compile the PHP 8.3 Apache image loaded with `pdo_mysql`, `intl`, `opcache`, `mod_rewrite`, and Composer CLI.
2. Spin up the isolated MariaDB 10.11 database engine and initialize all 5 relational tables via `database/init.sql`.
3. Auto-seed the system with default administrator (`admin`), demo customer (`demo`), sample booking (`AG-DEMO2026`), and dynamically generate 9,000+ forward-looking flight schedules spanning 90 days.
4. Launch the standalone **phpMyAdmin** visual management console on port 8081.
5. Boot the **Symfony 7.4 MicroKernel** and compile all routes, Twig extensions, and Doctrine entity mappings.

---

## 🌐 Application Endpoints

| Service / Tool | URL | Description |
| :--- | :--- | :--- |
| **Main Website** | [http://localhost:8080](http://localhost:8080) | Search flights, explore Philippine destinations, book travel |
| **Admin Portal** | [http://localhost:8080/admin/](http://localhost:8080/admin/) | Unified Symfony 7.4 administrative dashboard & analytics |
| **Admin API Endpoint** | [http://localhost:8080/admin/api/stats](http://localhost:8080/admin/api/stats) | JSON live metrics endpoint powered by `DashboardController` |
| **Symfony Web Profiler** | [http://localhost:8080/_profiler/](http://localhost:8080/_profiler/) | Live Symfony developer toolbar, database queries, and routing inspector |
| **phpMyAdmin GUI** | [http://localhost:8081](http://localhost:8081) | Visual DB management console (auto-authenticated to MariaDB) |
| **MariaDB Direct Port** | `localhost:3307` | External DB port for DBeaver, TablePlus, DataGrip, or VS Code |

---

## 🔑 Default Credentials

### Web Portals
| Account Type | Username | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin` | `admin2026!` | Unified management dashboard, bookings, fleet stats, audit logs |
| **Traveler Demo** | `demo` | `password123` | Customer portal, flight search, reservation history, checkout |

### Database Credentials (phpMyAdmin / Direct SQL Client)
| Parameter | Value |
| :--- | :--- |
| **Host** | `db` *(inside Docker)* or `127.0.0.1:3307` *(from host machine)* |
| **Database Name** | `aeroglide` |
| **Root User** | `root` / `root_secret` |
| **App User** | `aeroglide` / `aeroglide_secret` |

---

## ⚡ Symfony 7.4 Console & Architecture Commands

The project now includes the full Symfony CLI console suite (`bin/console`). You can run these commands directly on your host (if PHP 8.2+ is installed) or inside the Docker web container (`docker compose exec web php bin/console <command>`):

```bash
# 1. Inspect registered routes (including /admin, /admin/dashboard, and /admin/api/stats)
php bin/console debug:router

# 2. Validate Doctrine ORM entity mappings
php bin/console doctrine:schema:validate --skip-sync

# 3. Inspect registered Twig extensions, filters, and functions
php bin/console debug:twig

# 4. Clear and rebuild Symfony cache
php bin/console cache:clear

# 5. Inspect autowired services and dependencies
php bin/console debug:autowiring
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

---

## 🛠️ Docker Management Commands

| Action | Command |
| :--- | :--- |
| **Stop Stack** | `docker compose stop` |
| **Resume Stack** | `docker compose start` |
| **Tear Down Stack** | `docker compose down` |
| **Full Reset (Wipe & Re-seed DB)** | `docker compose down -v && docker compose up --build` |
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

### 2. Docker Daemon connection error
If you see `failed to connect to docker API`, ensure the Docker Desktop application is launched and running in your taskbar/menu bar.

### 3. Running on Local WAMP / XAMPP (Outside Docker)
If running on local WAMP without Docker:
1. Ensure WAMP is active (Apache + MySQL).
2. The project reads `.env` with fallback to `127.0.0.1:3306`, user `root`, empty password.
3. Access via `http://localhost/website/` or your configured virtual host.
