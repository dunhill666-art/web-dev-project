# AeroGlide — Airline Booking & Travel Platform

> **Zero-Configuration Dockerized Stack**: Designed for seamless evaluation on any machine (Windows, macOS, or Linux) with a single command. No local WAMP, Apache, or MySQL installation required.

---

## 🚀 Quick Start for Evaluators & Professors

### Prerequisites
- **Docker Desktop** (Windows / macOS) or **Docker Engine + Docker Compose v2** (Linux).
- Ensure Docker Desktop is running before executing commands.

### 1-Step Startup Command

Open your terminal or command prompt inside the project root folder and run:

```bash
docker compose up --build
```
*(To run in the background, append `-d`: `docker compose up --build -d`)*

Docker will automatically:
1. Pull and compile the PHP 8.3 Apache image with all required extensions (`pdo_mysql`, `intl`, `opcache`, `rewrite`).
2. Launch the isolated MariaDB database container and health-check the socket.
3. Automatically execute `database/init.sql` to generate all 5 core tables, the default admin account, and demo traveler records.
4. Dynamically seed 9,000+ forward-looking flight schedules spanning the next 90 days from the current date.
5. Spin up a standalone **phpMyAdmin** dashboard for visual database inspection.

---

## 🌐 Application Endpoints

Once the startup log outputs `AeroGlide Web Server Ready`, access the following services in your web browser:

| Service | URL | Notes |
| :--- | :--- | :--- |
| **Main Website** | [http://localhost:8080](http://localhost:8080) | Search flights, explore Philippine destinations, book travel |
| **Admin Portal** | [http://localhost:8080/admin/](http://localhost:8080/admin/) | Unified management dashboard, bookings, users, analytics |
| **phpMyAdmin GUI** | [http://localhost:8081](http://localhost:8081) | Visual DB management (auto-authenticates to MariaDB) |
| **Database Direct Port** | `localhost:3307` | For external DB clients like DBeaver, TablePlus, or VS Code |

---

## 🔑 Default Credentials

### Web Portals
| Account Type | Username | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin` | `admin2026!` | Full administrative dashboard, flight analytics, revenue & logs |
| **Traveler Demo** | `demo` | `password123` | Active customer profile, booking history, checkout flow |

### Database Credentials (phpMyAdmin / Direct SQL Client)
| Parameter | Value |
| :--- | :--- |
| **Server / Host** | `db` *(inside Docker)* or `127.0.0.1:3307` *(from host)* |
| **Database Name** | `aeroglide` |
| **Root User** | `root` / `root_secret` |
| **Application User** | `aeroglide` / `aeroglide_secret` |

---

## 📋 Evaluation & Testing Walkthrough

1. **Explore Flights & Booking**:
   - Navigate to [http://localhost:8080](http://localhost:8080).
   - Search for domestic flights (e.g., **Manila (MNL)** to **Caticlan / Boracay (MPH)** or **Cebu (CEB)** to **Siargao (IAO)**).
   - Select departure/return dates, pick a flight class (Economy, Premium, Business), and proceed to checkout.
2. **Review User Dashboard**:
   - Log in at [http://localhost:8080/user/login.php](http://localhost:8080/user/login.php) with `demo` / `password123`.
   - View active flight bookings and reservation references under **My Bookings**.
3. **Inspect Unified Admin Dashboard**:
   - Visit [http://localhost:8080/admin/](http://localhost:8080/admin/) and log in with `admin` / `admin2026!`.
   - Inspect live KPIs (Total Bookings, Active Passengers, Revenue metrics, Flight operations).
4. **Inspect Live Database Records**:
   - Open [http://localhost:8081](http://localhost:8081) to view the phpMyAdmin console.
   - Inspect the 5 core relational tables: `users`, `bookings`, `flights`, `password_tokens`, and `admin_logs`.

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
| **Open Shell in Web Container** | `docker compose exec web bash` |

---

## 🏗️ Architecture & Stack Details

- **Backend Runtime**: PHP 8.3 Apache with Apache `mod_rewrite` enabled.
- **MVC & Templating**: PHP with Symfony Components (`HttpFoundation`, `Yaml`) and Twig templating engine.
- **Relational Database**: MariaDB 10.11 LTS with persistent Docker named volume (`db_data`).
- **Networking**: Isolated bridge network (`aeroglide_net`) connecting `web`, `db`, and `phpmyadmin` via Docker internal DNS.
- **Backward Compatibility**: Dual-mode database configuration (`database/config.php`) automatically checks container environment variables first, while gracefully falling back to local WAMP/XAMPP defaults if run outside Docker.

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

### 3. Re-running on Local WAMP / XAMPP (Without Docker)
If you ever want to run the project on local WAMP again without Docker:
1. Ensure WAMP is running (Apache + MySQL).
2. The project reads `.env` with fallback to `127.0.0.1:3306`, user `root`, empty password.
3. Access via your local virtual host or `http://localhost/website/`.
