# 🏛️ VenuePro – Event & Catering Management System

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Environment](https://img.shields.io/badge/Stack-XAMPP%20%7C%20LAMP%20%7C%20WAMP-FB7A24?logo=xampp&logoColor=white)](https://www.apachefriends.org/)
[![Frontend](https://img.shields.io/badge/Frontend-HTML5%20%7C%20CSS3%20%7C%20Vanilla%20JS-E34F26?logo=html5&logoColor=white)](https://developer.mozilla.org/)
[![Security](https://img.shields.io/badge/Security-PDO%20%7C%20Bcrypt%20%7C%20RBAC-success)](https://owasp.org/)
[![Automated Tests](https://img.shields.io/badge/Tests-36%2F36%20Passed-brightgreen)](tests/)

> **VenuePro** is an enterprise-grade, full-stack web application for end-to-end venue reservation, catering selection, staffing management, and event operations. Built with pure HTML5/CSS3, vanilla asynchronous JavaScript (Fetch API), and a secure PHP (PDO) & MySQL backend.

---

## 📌 Table of Contents

- [Overview](#-overview)
- [Key Features by Role](#-key-features-by-role)
  - [🧑 Customer Portal](#-customer-portal)
  - [👨‍🍳 Caterer Portal](#-caterer-portal)
  - [👷 Staff Portal](#-staff-portal)
  - [🛡️ Admin Portal](#-admin-portal)
- [Technology Stack](#-technology-stack)
- [Database Architecture](#-database-architecture)
- [How to Run the Project (Step-by-Step)](#-how-to-run-the-project-step-by-step)
  - [Option A: Running with XAMPP (Standard)](#option-a-running-with-xampp-standard)
  - [Option B: Running with Built-in PHP Server](#option-b-running-with-built-in-php-server)
- [Demo Credentials](#-demo-credentials)
- [Security Features](#-security-features)
- [Testing & Quality Assurance](#-testing--quality-assurance)
- [Project Directory Structure](#-project-directory-structure)
- [License & Acknowledgments](#-license--acknowledgments)

---

## 📖 Overview

VenuePro eliminates manual coordination in event planning by unifying four key stakeholders (**Customers**, **Caterers**, **Event Staff**, and **Administrators**) onto a single real-time platform.

Whether booking a corporate symposium or a grand wedding gala, VenuePro handles venue availability verification, automated time-slot conflict detection, customizable dining packages, singular à-la-carte culinary add-ons, invoice generation, and live operational status tracking.

---

## 🌟 Key Features by Role

### 🧑 Customer Portal
* **Venue Discovery & Filtering:** Live search and multi-parameter filtering by price range, guest capacity, and venue category (Ballroom, Rooftop, Loft, etc.).
* **Smart Time-Slot Availability Check:** Automated overlap verification (`start_time < req_end AND end_time > req_start`) preventing double bookings on the exact same date and time.
* **Catering Tier Selection:** Choose from curated packages (Gold, Platinum, Signature) or custom menus.
* **Singular Add-On Customization:** Add individual items (Coke, Gourmet Desserts, Tiger Shrimp, etc.) with real-time quantity adjustments and dynamic total cost summation.
* **Instant Invoicing:** Automated itemized invoice creation with tax (VAT 8%) and service fee (10%) calculations.
* **Live Progress Tracking:** Monitor real-time status progression (`Submitted` ➔ `Under Review` ➔ `Confirmed` ➔ `In Progress` ➔ `Completed`).
* **Self-Service Cancellation:** Cancel pending or confirmed bookings directly from the dashboard with conflict-safe status rollback.
* **Staff Contact:** Direct chat interface strictly restricted to the assigned event staff.

### 👨‍🍳 Caterer Portal
* **Active Kitchen Queue:** Live operational dashboard showing orders due, package types, and guest covers.
* **Granular Order Approval:** Review booking food requirements with the ability to **Accept or Reject** the full package or individual singular add-on items.
* **Food Package Library:** Create, edit, and publish bespoke catering packages.
* **Singular Menu Item Management:** Full CRUD interface for à-la-carte menu items (image, price, availability status, description).

### 👷 Staff Portal
* **Shift & Assignment Dashboard:** View assigned events, dates, shift times, and lead coordinator roles.
* **Event Setup Checklist:** Interactive task management (AV testing, seating arrangement, safety briefings) with database-backed state persistence.
* **Customer Support Chat:** Direct communication channel with customers for day-of coordination.

### 🛡️ Admin Portal
* **Real-time Analytics Dashboard:** Live MTD Revenue, Today's Bookings count, Pending Approvals, and Property Utilization rates.
* **Booking Approval Engine:** Granular inspect, approve, or decline controls for all customer reservations.
* **Venue Management:** Add, edit, and update venue profiles, capacities, base pricing, and amenities.
* **Staff & Caterer Management:** Onboard and manage staff accounts and verify caterer applications.
* **Policy Governance:** Centralized editor for legal policies (Privacy Policy, Terms of Service, Support Information) updated across the platform in real time.

---

## 🛠️ Technology Stack

| Layer | Technologies Used | Description |
| :--- | :--- | :--- |
| **Frontend** | HTML5, CSS3, Modern Responsive Layouts | Custom corporate design system, CSS Grid/Flexbox, no bulky external CSS dependencies. |
| **Client-Side JS** | Vanilla JavaScript (ES6+) | `Fetch API`, DOM manipulation, dynamic total price calculators, modal managers. |
| **Backend** | PHP 8.x (Procedural / Modular MVC) | Prepared statements (PDO), session-based RBAC, input sanitization, JSON RESTful APIs. |
| **Database** | MySQL / MariaDB (InnoDB) | 15 relational tables with foreign keys, ON DELETE constraints, and optimized indexes. |
| **Environment** | XAMPP (Apache + MySQL) / PHP CLI | Cross-platform compatibility (Windows, macOS, Linux). |

---

## 🗄️ Database Architecture

The schema contains **15 normalized relational tables** in `database/venuepro.sql`:

1. `users` — Authentication credentials, roles (`customer`, `caterer`, `staff`, `admin`), profile details.
2. `caterer_profiles` — Business details, kitchen address, specialization, verification status.
3. `staff_profiles` — Staff code, department, active status.
4. `venues` — Venue names, descriptions, district, capacity, base daily rate, rating, images.
5. `catering_packages` — Preset dining tiers (Gold, Platinum, Signature, Custom) and pricing.
6. `package_menu_items` — Itemized course dishes per package.
7. `singular_menu_items` — Standalone culinary items (drinks, sides, appetizers, desserts).
8. `bookings` — Core reservations linking customer, venue, package, event date, start/end time, status.
9. `booking_singular_items` — Junction table for singular items added to a specific booking.
10. `caterer_orders` — Dispatched kitchen preparation orders linked to caterers.
11. `staff_assignments` — Staff-to-event coordinator shifts and persistent setup checklists.
12. `chat_messages` — Communication records restricted between customers and event staff.
13. `notifications` — Role-based notifications (booking updates, invoices, system alerts).
14. `site_policies` — Editable legal documents (Privacy Policy, Terms of Service, Support).
15. `invoices` — Financial receipts, billing codes, itemized costs, tax and fees.

---

## 🚀 How to Run the Project (Step-by-Step)

### Option A: Running with XAMPP (Standard)

#### Step 1: Clone or Copy the Repository
Clone the project into your XAMPP web root folder (`htdocs`):
```bash
# Windows default path: C:\xampp\htdocs\
cd C:\xampp\htdocs
git clone https://github.com/your-username/venuepro.git
```
*(Ensure the folder name is `venuepro`).*

#### Step 2: Start Apache & MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

#### Step 3: Import the Database
1. Open your browser and go to: **[http://localhost/phpmyadmin](http://localhost/phpmyadmin)**
2. Click **New** on the left sidebar to create a database.
3. Enter database name: **`venuepro`** with collation **`utf8mb4_unicode_ci`**, then click **Create**.
4. Select the `venuepro` database, then click the **Import** tab at the top.
5. Click **Choose File** and select:
   ```
   C:\xampp\htdocs\venuepro\database\venuepro.sql
   ```
6. Click **Import** (or **Go**) at the bottom.

> **Command Line Alternative:**
> ```bash
> mysql -u root venuepro < C:\xampp\htdocs\venuepro\database\venuepro.sql
> ```

#### Step 4: Open VenuePro
Open your browser and navigate to:
👉 **`http://localhost/venuepro/`**

---

### Option B: Running with Built-in PHP Server

If you prefer running without Apache:

1. Ensure MySQL is running on port `3306` with database `venuepro` imported.
2. Open terminal in the project directory:
   ```bash
   cd D:\Web-Development\venuepro
   php -S 127.0.0.1:8088
   ```
3. Open your browser and visit:
   👉 **`http://127.0.0.1:8088/`**

---

## 👥 Demo Credentials

All seed accounts use the demo password: **`password123`**

| Role | Email | Password | Primary Dashboard |
| :--- | :--- | :--- | :--- |
| **Customer** | `customer@venuepro.com` | `password123` | [`customer/customer-dashboard.php`](customer/customer-dashboard.php) |
| **Caterer** | `caterer@venuepro.com` | `password123` | [`caterer/caterer-dashboard.php`](caterer/caterer-dashboard.php) |
| **Staff** | `staff@venuepro.com` | `password123` | [`staff/staff-dashboard.php`](staff/staff-dashboard.php) |
| **Admin** | `admin@venuepro.com` | `password123` | [`admin/admin-dashboard.php`](admin/admin-dashboard.php) |

> 🔑 **Admin Signup Passcode:** `VENUEPRO2026` *(Required to register new Administrator accounts)*

---

## 🔒 Security Features

* **Prepared Statements (PDO):** 100% of database interactions utilize parameterized queries, eliminating SQL Injection vulnerabilities.
* **Secure Password Hashing:** Uses `password_hash()` with `PASSWORD_BCRYPT` and constant-time verification via `password_verify()`.
* **Output Sanitization (XSS):** All dynamic browser outputs pass through an `e()` helper function wrapping `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
* **Role-Based Access Control (RBAC):** Backend session guards (`requireRole()`) prevent privilege escalation and unauthorized endpoint access.
* **Isolated Communication:** Customer and Staff chat is restricted exclusively to counterpart roles.
* **Fail-Safe Conflict Validation:** Server-side date and time range verification guarantees no double-booking can occur regardless of client-side bypass attempts.

---

## 🧪 Testing & Quality Assurance

The system includes automated and manual end-to-end test suites covering **36 distinct test criteria**:

```
============================================================
TEST SUMMARY
============================================================
[PASS] DB connection via venues API
[PASS] New customer registration & validation
[PASS] Duplicate email collision prevention
[PASS] Authentication, password verification, & session tracking
[PASS] Role authorization barrier (401/403 guards)
[PASS] Multi-criteria venue search & filtering
[PASS] Catering packages & singular menu item listings
[PASS] Booking creation & calculation engine
[PASS] Time-slot overlap collision prevention
[PASS] Booking cancellation & double-cancel prevention
[PASS] SQL Injection immunity (search & ID parameters)
[PASS] XSS mitigation across all HTML templates
[PASS] Admin approval, metrics, and policy modifications
[PASS] Cross-role messaging and notification dispatch
============================================================
Passed: 36/36 (100% Passing)
============================================================
```

---

## 📂 Project Directory Structure

```
venuepro/
├── config/
│   ├── database.php             # PDO connection singleton & credentials
│   ├── auth.php                 # Session management & RBAC route protectors
│   └── helpers.php              # XSS sanitization e(), jsonResponse(), input parsers
├── api/
│   ├── auth.php                 # Authentication endpoints (login, register, logout, session)
│   ├── venues.php               # Venue retrieval, searching, and filtering
│   ├── packages.php             # Catering package listings
│   ├── singular-items.php       # Singular culinary items CRUD
│   ├── bookings.php             # Reservation creation, time-slot check, cancellation
│   ├── caterer-orders.php       # Kitchen queue and order item accept/reject
│   ├── staff.php                # Event assignments & checklist updates
│   ├── chat.php                 # Role-guarded chat messaging
│   ├── notifications.php        # Notification retrieval, badge counts, mark read
│   ├── policies.php             # Site policy reader and admin update handler
│   └── reports.php              # Analytics and administrative KPI metrics
├── database/
│   └── venuepro.sql             # Complete schema, triggers, and seed dataset
├── js/
│   └── app.js                   # Unified AJAX/Fetch controller and UI interactions
├── css/
│   └── style.css                # Enterprise design system stylesheet
├── assets/                      # Brand assets, logos, and venue photography
├── customer/                    # Customer portal views & workflows
├── caterer/                     # Caterer portal views & menu management
├── staff/                       # Staff portal views, checklists, and chat
├── admin/                       # Admin portal views, analytics, and approvals
├── index.php                    # Public landing page
├── login-role.php               # Role selector for sign-in
├── signup-role.php              # Role selector for sign-up
└── README.md                    # Project documentation
```

---

## 📄 License & Credits

Developed as an academic Full-Stack Web Development project.  
Created with standard web technologies (PHP, MySQL, JavaScript, HTML5, CSS3) designed for native execution on XAMPP.

*Built for modern browsers with zero external frameworks or heavy dependencies.*
