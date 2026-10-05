# 🏛️ VenuePro – Enterprise Venue & Catering Management Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Environment](https://img.shields.io/badge/Stack-XAMPP%20%7C%20Apache%20%7C%20MySQL-FB7A24?logo=xampp&logoColor=white)](https://www.apachefriends.org/)
[![Frontend](https://img.shields.io/badge/Frontend-HTML5%20%7C%20CSS3%20%7C%20Vanilla%20JS-E34F26?logo=html5&logoColor=white)](https://developer.mozilla.org/)
[![Security](https://img.shields.io/badge/Security-PDO%20%7C%20Bcrypt%20%7C%20RBAC%20%7C%20Multi--DB-success)](https://owasp.org/)

> **VenuePro** is an enterprise-grade web application for full-cycle venue reservation, catering logistics, staffing coordination, and event operations. Built with pure HTML5/CSS3, vanilla asynchronous JavaScript (Fetch API), and a secure PHP (PDO) & MySQL multi-database backend.

---

## 📌 Table of Contents

- [Overview](#-overview)
- [System Architecture & Multi-Database Engine](#-system-architecture--multi-database-engine)
- [How to Run the Project (Step-by-Step)](#-how-to-run-the-project-step-by-step)
  - [Prerequisites](#prerequisites)
  - [Method 1: Running with XAMPP (Apache + MySQL) – Recommended](#method-1-running-with-xampp-apache--mysql--recommended)
  - [Method 2: Running with Built-in PHP CLI Server](#method-2-running-with-built-in-php-cli-server)
- [Demo Credentials](#-demo-credentials)
- [Key Features by Role](#-key-features-by-role)
  - [🧑 Customer Portal](#-customer-portal)
  - [🛡️ Admin Portal](#-admin-portal)
  - [👨‍🍳 Caterer Portal](#-caterer-portal)
  - [👷 Staff Portal](#-staff-portal)
- [Project Directory Structure](#-project-directory-structure)
- [Security & Engineering Highlights](#-security--engineering-highlights)
- [Troubleshooting & FAQ](#-troubleshooting--faq)

---

## 📖 Overview

VenuePro eliminates manual coordination in event planning by unifying four primary stakeholders (**Customers**, **Administrators**, **Caterers**, and **Event Staff**) on a single real-time platform.

The system handles:
* Interactive venue catalog exploration and dynamic filtering.
* Smart time-slot collision detection (`start_time < req_end AND end_time > req_start`).
* Multi-tier catering packages and à-la-carte menu selection.
* Itemized invoice generation with dynamic tax and service fee calculations.
* Live status tracking and coordination with assigned staff.
* Isolated multi-database storage securing binary photo assets directly in MySQL.

---

## 🗄️ System Architecture & Multi-Database Engine

### 1. Primary Database (`venuepro`)
Manages business logic, authentication, reservations, catering menus, staff assignments, invoices, and system policies:
* `users` — Authentication credentials, roles (`customer`, `caterer`, `staff`, `admin`), profile details, and avatar styling.
* `venues` — Property details, capacities, base daily rates, hourly overtime fees, amenities, and cover photo endpoints.
* `bookings` — Reservations linking customers, venues, packages, dates, guest counts, and status progression.
* `catering_packages` & `singular_menu_items` — Curated dining tiers and individual culinary add-ons.
* `caterer_orders` — Dispatched kitchen preparation orders linked to caterers.
* `staff_assignments` — Staff-to-event coordinator shifts and operational checklists.
* `invoices` — Itemized billing statements with tax and service fee calculations.
* `chat_messages` & `notifications` — Role-scoped messaging and real-time alerts.

### 2. Dedicated Per-Venue Databases (`venuepro_venue_{id}`)
Each venue operates its own dedicated MySQL database (e.g. `venuepro_venue_1`, `venuepro_venue_2`):
* Photos are secured directly as binary `LONGBLOB` data in MySQL.
* Assets are streamed securely through `api/venue-photo.php?venue_id={id}&id={photo_id}` (or `cover=1`).
* Dedicated databases are auto-provisioned upon venue creation via `config/venue_db.php`.

---

## 🚀 How to Run the Project (Step-by-Step)

### Prerequisites

Before starting, ensure you have installed:
1. **XAMPP** (with **PHP 8.1+** and **MySQL / MariaDB**)  
   Download: [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. A modern web browser (Chrome, Edge, Firefox, etc.)

---

### Method 1: Running with XAMPP (Apache + MySQL) – Recommended

#### Step 1: Start XAMPP Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.
4. Ensure both services show green status indicators on ports `80`/`443` and `3306`.

#### Step 2: Import the Primary Database (`venuepro`)
1. Open your browser and navigate to:  
   👉 **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**
2. Click **New** on the left sidebar.
3. Enter database name: **`venuepro`** with collation **`utf8mb4_unicode_ci`**, then click **Create**.
4. Select the `venuepro` database in the left sidebar, then click the **Import** tab at the top.
5. Click **Choose File** (or **Browse**) and select:
   ```
   D:\Web-Development\venuepro\database\venuepro.sql
   ```
6. Click **Import** (or **Go**) at the bottom.

> **Command Line Alternative:**
> Open PowerShell or Command Prompt and run:
> ```powershell
> & "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS venuepro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
> & "C:\xampp\mysql\bin\mysql.exe" -u root venuepro < "D:\Web-Development\venuepro\database\venuepro.sql"
> ```

#### Step 3: Configure Apache DocumentRoot
If your project is located at `D:\Web-Development\venuepro`:
1. In XAMPP Control Panel, click **Config** next to Apache and choose **`httpd.conf`**.
2. Update lines ~252 to point to your project folder:
   ```apache
   DocumentRoot "D:/Web-Development/venuepro"
   <Directory "D:/Web-Development/venuepro">
       Options Indexes FollowSymLinks Includes ExecCGI
       AllowOverride All
       Require all granted
   </Directory>
   ```
3. Save the file and **Restart** Apache in the XAMPP Control Panel.

*(If you are running from the default `htdocs` directory instead, simply access `http://localhost/venuepro/`).*

#### Step 4: Open VenuePro
Open your browser and visit:
👉 **`http://localhost/venues.php`** (or **`http://localhost/`**)

---

### Method 2: Running with Built-in PHP CLI Server

If you prefer running without Apache:

1. Ensure **MySQL** is running in XAMPP (port 3306) and the `venuepro` database has been imported (Step 2 above).
2. Open PowerShell or Command Prompt in the project folder:
   ```powershell
   cd D:\Web-Development\venuepro
   php -S 127.0.0.1:8000
   ```
3. Open your browser and visit:  
   👉 **`http://127.0.0.1:8000/venues.php`**

---

## 👥 Demo Credentials

All pre-seeded demo accounts use the standard password: **`password123`**

| Role | Email Address | Password | Primary Dashboard |
| :--- | :--- | :--- | :--- |
| **Customer** | `customer@venuepro.com` | `password123` | [`customer/customer-dashboard.php`](customer/customer-dashboard.php) |
| **Admin** | `admin@venuepro.com` | `password123` | [`admin/admin-dashboard.php`](admin/admin-dashboard.php) |
| **Caterer** | `caterer@venuepro.com` | `password123` | [`caterer/caterer-dashboard.php`](caterer/caterer-dashboard.php) |
| **Staff** | `staff@venuepro.com` | `password123` | [`staff/staff-dashboard.php`](staff/staff-dashboard.php) |

> 🔑 **Admin Registration Passcode:** `VENUEPRO2026` *(Required when registering a new Administrator via `admin/admin-signup.php`)*

---

## 🌟 Key Features by Role

### 🧑 Customer Portal
* **Venue Catalog & Filtering:** Search venues by property type, maximum capacity, price range, and district location.
* **Photo Gallery Stream:** High-resolution venue photos streamed directly from dedicated MySQL databases.
* **Instant Booking Flow:** Date picker with automated collision prevention, guest count estimator, and duration selector.
* **Catering & Add-Ons:** Choose preset catering tiers (Gold, Platinum, Signature) or singular à-la-carte menu items.
* **Live Operational Progress:** Track real-time status changes (`Submitted` ➔ `Under Review` ➔ `Confirmed` ➔ `In Progress` ➔ `Completed`).
* **Profile Management:** Edit full name, email, password, and avatar color with instant database and session persistence (`customer/customer-profile.php`).

### 🛡️ Admin Portal
* **Live Governance Dashboard:** Real-time metrics for today's bookings, pending approvals, revenue, and venue occupancy.
* **Venue Catalog Management:** Add new venues and edit specifications, rates, amenities, and status (`admin/venue-management.php`).
* **Photo Gallery Studio:** Upload photos, set primary cover images, update captions, and delete images stored directly in the venue's dedicated database (`admin/admin-edit-venue.php`).
* **Reservation Approvals:** Granular inspection, approval, or rejection of customer bookings (`admin/admin-pending-bookings.php`).
* **User & Staff Governance:** View, edit, or remove admins, customers, and staff (`admin/admin-user-management.php`, `admin/admin-staff-management.php`).
* **Admin Profile Editor:** Self-service profile editing with live topbar and session synchronization (`admin/admin-profile.php`).

### 👨‍🍳 Caterer Portal
* **Active Kitchen Queue:** Live order board displaying preparation stages, package tiers, and due times.
* **Order Item Approvals:** Inspect booking culinary requirements and accept/reject individual package courses or singular add-ons.
* **Menu & Package Management:** Create, publish, and manage packages and singular culinary items (`caterer/caterer-food-packages.php`).

### 👷 Staff Portal
* **Shift Dashboard:** View assigned events, venues, schedules, and coordinator roles (`staff/staff-dashboard.php`).
* **Event Setup Checklist:** Interactive task management with real-time database state persistence.
* **Customer Chat:** Dedicated communication channel linked to assigned events.

---

## 📂 Project Directory Structure

```
D:\Web-Development\venuepro\
├── config/
│   ├── database.php             # PDO connection to primary database (venuepro)
│   ├── venue_db.php             # Dedicated per-venue database engine & LONGBLOB storage
│   ├── auth.php                 # Session verification, RBAC guards, and live profile sync
│   └── helpers.php              # XSS sanitization e(), jsonResponse(), input sanitizers
├── api/
│   ├── auth.php                 # Authentication endpoints (login, register, logout, session)
│   ├── venues.php               # Venue CRUD, multi-database photo gallery operations
│   ├── venue-photo.php          # Secure binary photo streaming controller
│   ├── profile.php              # Real-time profile editing API (customer & admin)
│   ├── users.php                # User administration and account deletion API
│   ├── packages.php             # Catering package listings and CRUD
│   ├── singular-items.php       # À-la-carte culinary items management
│   ├── bookings.php             # Reservation creation, time-slot check, cancellation
│   ├── caterer-orders.php       # Kitchen dispatch queue and item approval
│   ├── staff.php                # Staff directory and assignment management
│   ├── chat.php                 # Role-scoped messaging
│   └── reports.php              # Administrative analytics and KPIs
├── database/
│   └── venuepro.sql             # Primary database schema, seed dataset, and constraints
├── admin/                       # Administrator portal views and governance tools
├── customer/                    # Customer portal views, booking flow, and profile editor
├── caterer/                     # Caterer portal views and kitchen queue
├── staff/                       # Staff portal views and setup checklists
├── assets/                      # Brand logos and fallback photography assets
├── css/
│   └── style.css                # Enterprise design system stylesheet
├── js/
│   └── app.js                   # Client-side controllers and AJAX interaction logic
├── venues.php                   # Public venue listings and catalog page
├── venue-details.php            # Public venue details and photo gallery modal
├── index.php                    # Public landing page
├── login-role.php               # Role selector for sign-in
├── signup-role.php              # Role selector for registration
├── logout.php                   # Centralized session termination and cleanup
└── README.md                    # Project documentation
```

---

## 🔒 Security & Engineering Highlights

* **Multi-Database Isolation:** Venue photograph binaries are isolated in dedicated databases (`venuepro_venue_{id}`) rather than public web folders, streamed via controller with MIME-type and cache verification.
* **Parameterized PDO Queries:** 100% of SQL interactions utilize prepared statements, preventing SQL Injection.
* **Bcrypt Password Encryption:** Passwords hashed with `PASSWORD_BCRYPT` and verified with constant-time `password_verify()`.
* **Output Sanitization (XSS):** Dynamic HTML outputs pass through the `e()` helper wrapping `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
* **Role-Based Access Control (RBAC):** Backend route guards (`requireRole()`) verify user roles and active status before granting access.
* **Conflict-Free Scheduling:** Server-side date and time range verification guarantees no double-booking can occur regardless of client-side requests.
* **Synchronized Session Integrity:** `requireLogin()` continuously verifies active user existence in MySQL, synchronizing profile name, email, and avatar attributes across all pages.

---

## ❓ Troubleshooting & FAQ

**Q: Images do not load or show placeholders.**  
* **A:** Ensure MySQL is running on port 3306 with `root` privileges so the system can provision and query `venuepro_venue_{id}` databases. You can re-verify by visiting `http://localhost/api/venue-photo.php?venue_id=1&cover=1`.

**Q: Profile changes revert after navigating to another page.**  
* **A:** Profile updates are persisted directly to the MySQL `users` table and synchronized across sessions. If this occurs, verify that your browser session cookies are enabled and that `users` table record exists for your logged-in account.

**Q: Port 80 conflict in XAMPP (Apache won't start).**  
* **A:** If IIS, Skype, or another program uses port 80, either stop that service or use **Method 2 (PHP CLI Server)** by running `php -S 127.0.0.1:8000` inside `D:\Web-Development\venuepro`.

**Q: How do I create a new Administrator?**  
* **A:** Navigate to `admin/admin-signup.php`, enter your details, and use the administrator access code **`VENUEPRO2026`**.

---

## 📄 License & Credits

Developed as a Full-Stack Web Development project using native web technologies (PHP, MySQL, JavaScript, HTML5, CSS3) designed for execution on standard XAMPP environments.
