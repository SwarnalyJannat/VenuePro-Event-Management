# VenuePro – Full-Stack Event & Catering Management System

VenuePro is an enterprise-grade Event and Catering Management platform built with a pure responsive HTML5/CSS3 frontend, vanilla JavaScript (Fetch/AJAX), and a secure PHP (PDO) & MySQL backend designed for XAMPP.

---

## 🛠️ Technology Stack

- **Frontend:** HTML5, CSS3 (Enterprise Design System, responsive CSS grid/flexbox, zero external CSS bloat)
- **Client Scripting:** Modern JavaScript (ES6+, Fetch API, asynchronous DOM state management)
- **Backend:** PHP 8.x (Modular MVC/REST architecture, PDO, Session Security, CSRF/XSS protection)
- **Database:** MySQL / MariaDB (Full relational schema with Foreign Keys, Cascades, and Indexes)
- **Local Environment:** XAMPP (Apache + MariaDB)

---

## 👥 Default Demo Credentials

All test accounts use the password: **`password123`**

| Role | Email | Password | Primary Portal |
| :--- | :--- | :--- | :--- |
| **Customer** | `customer@venuepro.com` | `password123` | [`customer/customer-dashboard.php`](customer/customer-dashboard.php) |
| **Caterer** | `caterer@venuepro.com` | `password123` | [`caterer/caterer-dashboard.php`](caterer/caterer-dashboard.php) |
| **Staff** | `staff@venuepro.com` | `password123` | [`staff/staff-dashboard.php`](staff/staff-dashboard.php) |
| **Admin** | `admin@venuepro.com` | `password123` | [`admin/admin-dashboard.php`](admin/admin-dashboard.php) |

> **Admin Signup Access Code:** `VENUEPRO2026`

---

## 📁 Project Structure

```
venuepro/
├── config/
│   ├── database.php             # PDO database connection & error handling
│   ├── auth.php                 # Session management, authentication & RBAC guards
│   └── helpers.php              # XSS sanitization, JSON responses, formatting
├── api/
│   ├── auth.php                 # Login, register, logout, session verification
│   ├── venues.php               # Venue listings, search, multi-criteria filtering
│   ├── packages.php             # Catering package listings & course specifications
│   ├── singular-items.php       # Add-on singular items CRUD (Shrimp, Coke, Yogurt, etc.)
│   ├── bookings.php             # Booking creation, date conflict checks, invoices
│   ├── caterer-orders.php       # Kitchen queue, package & singular item approvals
│   ├── staff.php                # Staff event assignments & setup checklist sync
│   ├── chat.php                 # Role-restricted direct messaging (Customer <-> Staff)
│   ├── notifications.php        # User notifications & read states
│   ├── policies.php             # Legal policies (Privacy, Terms, Support)
│   └── reports.php              # Administrative metrics & revenue analytics
├── database/
│   └── venuepro.sql             # Complete schema, constraints, and seed data
├── js/
│   └── app.js                   # Unified AJAX client handler
├── css/
│   └── style.css                # Master enterprise stylesheet
├── assets/                      # Logos, icons, and venue/dish photography
├── customer/                    # Customer role pages (.php & .html)
├── caterer/                     # Caterer role pages (.php & .html)
├── staff/                       # Staff role pages (.php & .html)
├── admin/                       # Admin role pages (.php & .html)
├── index.php                    # Marketing landing page
├── login-role.php               # Role-based login gateway
└── signup-role.php              # Role-based registration gateway
```

---

## 🚀 Step-by-Step XAMPP Setup Guide

### 1. Launch XAMPP
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache** and **MySQL**.

### 2. Import the Database
1. Open your web browser and navigate to **[http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)**.
2. Click the **Databases** tab and create a new database named **`venuepro`** (collation `utf8mb4_unicode_ci`).
3. Click the **Import** tab.
4. Select the file: **`database/venuepro.sql`** located inside your VenuePro project folder.
5. Click **Import** (or **Go**).
   *(Alternatively, run from terminal: `mysql -u root venuepro < database/venuepro.sql`)*

### 3. Open VenuePro in Your Browser
- Open your browser and navigate to:
  **`http://localhost/venuepro/`**

---

## 🔐 Core Workflows & Features

1. **Role-Based Authentication & Guarding:**
   - Multi-role gateway (`customer`, `caterer`, `staff`, `admin`).
   - Passwords hashed using standard `PASSWORD_BCRYPT`.
   - Protected routes automatically verify active session and permissions; unauthorized requests are redirected with status-appropriate handling.

2. **Customer Booking & Add-On Customization Workflow:**
   - Browse venues with dynamic search and real-time filtering by capacity, price, and category.
   - Date selection with automated conflict detection preventing double-bookings.
   - Choice of multi-course Catering Package (Gold, Platinum, Signature).
   - Optional Add-On Singular Items card (Grilled Tiger Shrimp, Coca-Cola, Greek Yogurt Parfait, Vanilla Panna Cotta, etc.) with real-time quantity controls and live dynamic subtotal summation.
   - Booking confirmation, automatic order dispatch to caterer, and printable invoice generation.

3. **Caterer Kitchen Queue & Approval System:**
   - Active kitchen preparation queue (`caterer-order-details.php`).
   - Decision controls to **Accept or Reject** the full package order.
   - Individual item decision controls to **Accept or Reject each singular add-on item** independently based on kitchen stock.
   - Menu inventory management (`caterer-menu-items.php`) with full CRUD and low-stock indicators.

4. **Staff Event Setup Checklist:**
   - Lead Event Coordinator assignments for booked venues.
   - Interactive setup checklist (Banquet tables, AV testing, kitchen briefing, security).
   - Real-time checklist state synchronization with MySQL database via Fetch API.

5. **Customer & Staff Direct Chat:**
   - Strict communication isolation: Customers can chat only with their assigned Venue Staff coordinators and vice-versa.
   - Conversation history saved in `chat_messages` table.

6. **Admin Policy & Governance:**
   - Live legal document editor (`admin-policy-management.php`).
   - Updates to Privacy Policy, Terms of Service, and Contact Support reflect live across customer footers.
