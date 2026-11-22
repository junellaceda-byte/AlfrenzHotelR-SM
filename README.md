# Hotel Reservation & Sales Management System (SalesMS)

## 1. Executive Summary & Purpose

The **Hotel Reservation & Sales Management System (SalesMS)** is a robust web application designed to streamline hotel operations. Built on the Laravel framework, this system provides a comprehensive solution for managing guest records, room availability, bookings, and billing operations.

Its core business logic revolves around simplifying the receptionist's workflow, ensuring accurate tracking of room statuses (e.g., available, occupied, reserved), and providing an efficient checkout and invoicing process.

## 2. Architecture Overview

The application strictly adheres to the Laravel **Model-View-Controller (MVC)** architectural pattern, ensuring a clean separation of concerns:

<!-- Bazinga!!! -->
<!-- ============ FOR THE FRONT-END DEVELOPER ============ -->

1. Color Palette & UI Theme
   The application uses a Vintage Brown visual theme based on the palette #443627, #D98324, #EFDCAB, and #F2F6D0.

### Color Variables

color-primary-brown : #443627;/_ Sidebar & Topbar Background _/
color-accent-gold : #D98324;/_ Borders, Dividers & Highlights _/
color-warm-beige : #EFDCAB;/_ Cards, Dropdowns & Secondary Backgrounds _/
color-cream-light: #F2F6D0;/_ Main Content Area Background / Light Accents _/

<!-- Tommy Innit! -->
<!-- Happy Halloween, Folks! -->
<!-- Updated -->
<!-- October 31 2026 -->

## Receptionist Module

### Features

- Overview: live room status (available, occupied, reserved, under maintenance), room grid with filters, notifications 30 minutes before check-out
- Book Now / Today and Reserve in one modal
- Reservation list with Check in and Cancel
- Booking History with full guest details, View and Add amenities
- Billing: payment at check-out

### Business rules

- Short stay: 3 hours 600, 6 hours 1,000, 12 hours 1,400
- 24-hour stay: 1,800 without breakfast, 2,200 with breakfast. Check-out is exactly 24 hours after check-in.
- Extra bed: 500 without breakfast, 700 with breakfast (added as an amenity). Extra hour: 150.
- Senior Citizen and PWD: 20% discount on the whole subtotal. The hotel is non-VAT (no tax).
- 20 standard rooms. Prices are in config/hotel.php.

### Setup

1. composer install
2. Copy .env.example to .env and set the database (DB_DATABASE, DB_USERNAME, DB_PASSWORD)
3. php artisan key:generate
4. php artisan migrate --seed
5. php artisan serve

### OR

## A BETTER METHOD FR FR

#### Rather than downloading or git clone that shh

### How to Inspect an Older Commit on a Specific Branch (By ME)

Follow these steps to safely view a previous version of the code without affecting your current work.

**1. Switch to the target branch**
Move to the branch that contains the history you want to search.
`bash
git switch <branch-name>
`

1. _Verification:_ Run `git status` to confirm the output says "On branch <branch-name>".

**2. Find the commit hash**
Pull up the branch's history to locate the exact commit you need.
`bash
2. git log --oneline
`
_Verification:_ You will see a list of recent commits with 7-character IDs (e.g., `a1b2c3d`). Note the ID of the commit you want to view, then press `q` to exit the log.

**3. View the specific commit**
Jump back in time to the exact state of that commit.
`bash
3. git checkout <commit-hash>
`
_Verification:_ Your files will immediately update to match that older version.

> **Note (Detached HEAD state):** Git will warn you that you are in a "detached HEAD" state. This is normal and safe. It simply means you are looking at a past snapshot disconnected from the active timeline. You can run the code and inspect files, but do not make new commits here.

**4. Return to the current code**
When you are finished looking around, return to the latest version of your branch.
`bash
4. git switch <branch-name>
`
_Verification:_ Your files will revert back to the newest version, and `git status` will confirm you are back on the active branch.

### Status

See TODO.md for what is done and what is planned.

---

<!-- Bazinga Page 92 -->

# Project Implementation & Development Documentation

> **Target Audience:** Documentarians & Front-End Developers  
> **Updated:** November 2026  
> **Framework:** Laravel 12 (PHP 8.3) & Bootstrap 5.3.8

This section details all completed features, UI layouts, visual tokens, routing conventions, API contracts, and business rules implemented across the **Hotel Reservation & Sales Management System (SalesMS)**.

---

## 1. System Architecture & Portal Separation

The application is structured into two dedicated portals with strict role-based separation:

```
                          ┌───────────────────────────┐
                          │   Unified Login (/login)  │
                          └─────────────┬─────────────┘
                                        │
                 ┌──────────────────────┴──────────────────────┐
                 ▼                                             ▼
   ┌───────────────────────────┐                 ┌───────────────────────────┐
   │   Administrator Portal    │                 │    Receptionist Portal    │
   │    (/admindashboard)      │                 │  (/dashboardreceptionist) │
   ├───────────────────────────┤                 ├───────────────────────────┤
   │ • Overview & Real-Time KPI│                 │ • Live Room Status Grid   │
   │ • Room Management         │                 │ • Booking & Reservation   │
   │ • Amenities Catalog       │                 │ • Billing & Payments      │
   │ • Account Management      │                 │ • In-House Amenities      │
   │ • Activity Logs (Audit)   │                 │ • Synchronized Settings   │
   │ • Sales Reports & Export  │                 │ • Due Room Notifications  │
   │ • System Settings (Clock) │                 │                           │
   └───────────────────────────┘                 └───────────────────────────┘
```

### Authentication & Role Protection

- **Administrator Login**: Authenticates against the `users` database table using Laravel's secure password hashing (`Hash::make()` / `Hash::check()`).
- **Receptionist Login**: Authenticates against the `staff` database table using credentials created in Administrator &rarr; Account Management.
- **Middleware Guards**:
    - `auth.admin` (`AdminAuthMiddleware`): Protects `/admindashboard`, `/admin/*`, and administrative endpoints. Includes an automatic **15-minute inactivity timeout**.
    - `auth.receptionist` (`ReceptionistAuthMiddleware`): Protects `/dashboardreceptionist`, `/bookings`, `/billing`, `/reservation`, and `/receptionist/settings`. Includes an automatic **30-minute inactivity timeout**.
    - Unauthorized cross-portal navigation automatically redirects to `/login` with an informative validation alert.

---

## 2. Front-End Design System & Visual Hierarchy

The application employs a consistent **Vintage Brown** theme engineered for hospitality management.

### Color Tokens & Usage

| Token Name                | Hex Code  | Visual Application                                                                               |
| :------------------------ | :-------- | :----------------------------------------------------------------------------------------------- |
| **Primary Vintage Brown** | `#443627` | Sidebars, active nav links, primary action buttons (`.btn-vintage`), table headers, card borders |
| **Deep Mahogany**         | `#6A4435` | Receptionist sidebar accent, modal headers, badge highlights                                     |
| **Warm Gold / Ochre**     | `#D98324` | Section borders, alert accents, warning badges, notification highlights                          |
| **Warm Beige**            | `#EFDCAB` | Dropdown backgrounds, preview containers, subtle card highlights                                 |
| **Cream Light**           | `#F8F6F4` | Form inputs, live preview cards (`.preview-box`), light tables                                   |
| **Neutral Canvas**        | `#F8F9FA` | Main viewport canvas, layout background                                                          |

### Key CSS Helper Classes

- `.btn-vintage`: Standard brown action button (`background-color: #443627`, hover: `#5c4a36`).
- `.preview-box`: Highlights live-updating timestamps with a 4px left vintage border (`border-left: 4px solid #443627; background-color: #f8f6f4;`).
- `.section-heading`: Standardized section header with bottom border (`color: #443627; border-bottom: 1px solid #dee2e6;`).
- `.sidebar-nav-link`: Responsive sidebar navigation button with active state styling.

### Live Manila Clocks

Both portals feature real-time ticking clocks in the topbar (`#current-date` and `#current-time`). These clocks query `/api/system/settings` to dynamically synchronize with the administrator's selected timestamp format without requiring page reloads.

---

## 3. Implemented Modules & Features

### 3.1 Administrator Credentials & Account Management

- **Location**: `Administrator → Account Management` (`resources/views/Administrator/content/accman.blade.php`).
- **Positioning**: The **Administrator Credentials** card is placed immediately below the Receptionist staff credentials table (`#adminCredentialsSection`).
- **Capabilities**:
    - Displays currently saved Administrator username.
    - Allows editing the username and updating the password.
    - Password inputs feature interactive show/hide eye toggle buttons.
    - Validates minimum length (6 characters) and confirmation match (`password_confirmation`).
    - Passwords are encrypted using Laravel's Bcrypt hashing before database persistence.
    - Route: `PUT /admin/credentials` (`admin.credentials.update`).

### 3.2 System Settings & Timestamp Format Customization

- **Location**: `Administrator → Settings` (`resources/views/Administrator/content/settings.blade.php`).
- **Timestamp Display Format**:
    - Allows the Administrator to select between **12-Hour Format (AM/PM)** (e.g. `11:25 AM` or `11:25:00 AM`) and **24-Hour Format** (e.g. `11:25` or `11:25:00`).
    - Multiple date format options (`M d, Y`, `F d, Y`, `Y-m-d`, `m/d/Y`, `d/m/Y`).
    - Synchronous radio toggle (`12-hour` vs `24-hour`) linked with select dropdowns.
    - Live interactive preview box reflecting selections in real-time.
- **Database Persistence**: Formats are stored in the `system_settings` table via `SystemSetting::set('date_format')` and `SystemSetting::set('time_format')`.
- **System-Wide Formatting (`App\Services\SystemFormatService`)**:
    - Helper methods: `dateFormat()`, `timeFormat()`, `is24Hour()`, `timeFormatType()`, `formatDate()`, `formatTime()`, `formatDateTime()`.
    - Automatically shared with all Blade views via `View::composer('*')`.
    - Consistent across Booking records, Invoices, Confirmed Payments, Activity Logs, and due notifications without modifying underlying database datetimes.

### 3.3 Receptionist Settings Portal

- **Location**: Added `SETTINGS` navigation link to Receptionist sidebar (`resources/views/Receptionist/partials/sidebar.blade.php`).
- **Dedicated View**: `resources/views/Receptionist/content/settings.blade.php` (`GET /receptionist/settings`).
- **Design & Layout**: Replicates the Administrator Settings layout with Vintage Brown styling.
- **Content**:
    - **Receptionist Account Profile**: Shows logged-in staff name, username, email, and contact number.
    - **Timestamp Display Format**: Displays active 12h/24h format mode, date format, and time format.
    - **Live Synchronization**: Real-time ticking Manila clock and status badge indicating synchronization with Administrator settings.
    - **Security Separation**: Administrator credential management fields are completely excluded from the Receptionist view.

### 3.4 Activity Logs (Comprehensive Audit Trail)

- **Location**: `Administrator → Activity Logs` (`resources/views/Administrator/content/activities.blade.php`).
- **Automatic Event Recording (`App\Services\ActivityLogService`)**:
    1. **Payments**: Records confirmed check-out payments with payment method, amount, reference number, and responsible Receptionist.
    2. **Cancellations**: Records cancelled reservations and bookings with guest name, room numbers, and responsible Receptionist.
    3. **Overdues**: Automatically detects expired stays and logs them as **System Generated** (`staff_id: null`).
    4. **Login**: Exclusively logs Receptionist logins with username, date, time, IP address, and user agent.
    5. **Bookings**: Logs new immediate bookings and future reservations with stay type, room numbers, and responsible Receptionist.
- **Filters & Search**: Fast client-side and server-side filtering by user (All, System, Receptionist), activity type, date, and keyword search.

### 3.5 Sales Reports & Analytics

- **Location**: `Administrator → Sales Report` (`resources/views/Administrator/content/salerep.blade.php`).
- **Metrics**:
    - Total Revenue (PHP)
    - Average Daily Rate (ADR = Room Revenue / Total Room Nights)
    - Total Bookings & Check-outs
    - Monthly Target Progress Gauge (integrated with `monthly_targets` table)
    - Lost Revenue from Cancellations
    - Pending Payments & Due Invoices
- **CSV Export**: `GET /admin/sales-report/export` downloads an itemized, formatted CSV report with UTF-8 BOM encoding for seamless Microsoft Excel compatibility.

### 3.6 Room Management & Maintenance

- **Visual Grid**: Room cards color-coded by status (Green: Available, Red: Occupied, Blue: Reserved, Yellow: Under Maintenance).
- **Maintenance Blocking**: Create, schedule, and remove room blocks with start/end datetimes.
- **Overdue Clock**: Live counting red indicator on occupied rooms exceeding their scheduled check-out time.

---

## 4. REST & JSON API Endpoints Reference

| Endpoint                         | Method |     Middleware      | Description                                                                      |
| :------------------------------- | :----: | :-----------------: | :------------------------------------------------------------------------------- |
| `/api/system/settings`           | `GET`  |       Public        | Returns active date format, time format, formatted strings, and ISO server time. |
| `/api/admin/activities`          | `GET`  |    `auth.admin`     | Paginated Activity Logs with filters (`who`, `type`, `date`, `search`).          |
| `/api/admin/overview-stats`      | `GET`  |    `auth.admin`     | Live KPI counters (occupancy, revenue, check-ins, check-outs).                   |
| `/api/admin/revenue-stats`       | `GET`  |    `auth.admin`     | Revenue trends grouped by today, weekly, monthly, or yearly.                     |
| `/api/admin/stay-type-stats`     | `GET`  |    `auth.admin`     | Short stay vs overnight stay distribution.                                       |
| `/api/admin/notifications`       | `GET`  |    `auth.admin`     | Administrative notifications (cancellations and due departures).                 |
| `/api/admin/monthly-target`      | `POST` |    `auth.admin`     | Stores or updates the monthly revenue target.                                    |
| `/api/admin/sales-report/export` | `GET`  |    `auth.admin`     | Generates and streams the Sales Report CSV download.                             |
| `/api/dashboard/notifications`   | `GET`  | `auth.receptionist` | Real-time overdue checks and due-guest notifications for Receptionist.           |
| `/api/available-rooms`           | `GET`  | `auth.receptionist` | Available room IDs for specified check-in and check-out dates.                   |
| `/api/amenities`                 | `GET`  |       Public        | Returns offered customizable hotel amenities.                                    |

---

## 5. Verification & Testing Reference

The system includes automated PHPUnit / Pest feature test suites in `tests/Feature/`:

- `LoginAuthenticationTest.php`: Administrator and Receptionist authentication, password hashing, role enforcement, and session timeouts (8 tests).
- `AdminSettingsTest.php`: Administrator credential editing in Account Management, 12h/24h timestamp format toggling, Receptionist Settings rendering and role isolation (9 tests).
- `ActivityLogsTest.php`: End-to-end activity logging across payments, cancellations, overdues, logins, and bookings (8 tests).

### Running Tests

To run the test suites:

```bash
php artisan test --filter="AdminSettingsTest|LoginAuthenticationTest|ActivityLogsTest"
```

To run code formatting with Laravel Pint:

```bash
vendor/bin/pint --dirty --format agent
```

---

# Project Implementation & Development Documentation
> **Target Audience:** Documentarians & Front-End Developers  
> **Updated:** November 2026  
> **Framework:** Laravel 12 (PHP 8.3) & Bootstrap 5.3.8

This section details all completed features, UI layouts, visual tokens, routing conventions, API contracts, and business rules implemented across the **Hotel Reservation & Sales Management System (SalesMS)**.

---

## 1. System Architecture & Portal Separation

The application is structured into two dedicated portals with strict role-based separation:

```
                          ┌───────────────────────────┐
                          │   Unified Login (/login)  │
                          └─────────────┬─────────────┘
                                        │
                 ┌──────────────────────┴──────────────────────┐
                 ▼                                             ▼
   ┌───────────────────────────┐                 ┌───────────────────────────┐
   │   Administrator Portal    │                 │    Receptionist Portal    │
   │    (/admindashboard)      │                 │  (/dashboardreceptionist) │
   ├───────────────────────────┤                 ├───────────────────────────┤
   │ • Overview & Real-Time KPI│                 │ • Live Room Status Grid   │
   │ • Room Management         │                 │ • Booking & Reservation   │
   │ • Amenities Catalog       │                 │ • Billing & Payments      │
   │ • Account Management      │                 │ • In-House Amenities      │
   │ • Activity Logs (Audit)   │                 │ • Synchronized Settings   │
   │ • Sales Reports & Export  │                 │ • Due Room Notifications  │
   │ • System Settings (Clock) │                 │                           │
   └───────────────────────────┘                 └───────────────────────────┘
```

### Authentication & Role Protection
- **Administrator Login**: Authenticates against the `users` database table using Laravel's secure password hashing (`Hash::make()` / `Hash::check()`).
- **Receptionist Login**: Authenticates against the `staff` database table using credentials created in Administrator &rarr; Account Management.
- **Middleware Guards**:
  - `auth.admin` (`AdminAuthMiddleware`): Protects `/admindashboard`, `/admin/*`, and administrative endpoints. Includes an automatic **15-minute inactivity timeout**.
  - `auth.receptionist` (`ReceptionistAuthMiddleware`): Protects `/dashboardreceptionist`, `/bookings`, `/billing`, `/reservation`, and `/receptionist/settings`. Includes an automatic **30-minute inactivity timeout**.
  - Unauthorized cross-portal navigation automatically redirects to `/login` with an informative validation alert.

---

## 2. Front-End Design System & Visual Hierarchy

The application employs a consistent **Vintage Brown** theme engineered for hospitality management.

### Color Tokens & Usage

| Token Name | Hex Code | Visual Application |
| :--- | :--- | :--- |
| **Primary Vintage Brown** | `#443627` | Sidebars, active nav links, primary action buttons (`.btn-vintage`), table headers, card borders |
| **Deep Mahogany** | `#6A4435` | Receptionist sidebar accent, modal headers, badge highlights |
| **Warm Gold / Ochre** | `#D98324` | Section borders, alert accents, warning badges, notification highlights |
| **Warm Beige** | `#EFDCAB` | Dropdown backgrounds, preview containers, subtle card highlights |
| **Cream Light** | `#F8F6F4` | Form inputs, live preview cards (`.preview-box`), light tables |
| **Neutral Canvas** | `#F8F9FA` | Main viewport canvas, layout background |

### Key CSS Helper Classes
- `.btn-vintage`: Standard brown action button (`background-color: #443627`, hover: `#5c4a36`).
- `.preview-box`: Highlights live-updating timestamps with a 4px left vintage border (`border-left: 4px solid #443627; background-color: #f8f6f4;`).
- `.section-heading`: Standardized section header with bottom border (`color: #443627; border-bottom: 1px solid #dee2e6;`).
- `.sidebar-nav-link`: Responsive sidebar navigation button with active state styling.

### Live Manila Clocks
Both portals feature real-time ticking clocks in the topbar (`#current-date` and `#current-time`). These clocks query `/api/system/settings` to dynamically synchronize with the administrator's selected timestamp format without requiring page reloads.

---

## 3. Implemented Modules & Features

### 3.1 Administrator Credentials & Account Management
- **Location**: `Administrator → Account Management` (`resources/views/Administrator/content/accman.blade.php`).
- **Positioning**: The **Administrator Credentials** card is placed immediately below the Receptionist staff credentials table (`#adminCredentialsSection`).
- **Capabilities**:
  - Displays currently saved Administrator username.
  - Allows editing the username and updating the password.
  - Password inputs feature interactive show/hide eye toggle buttons.
  - Validates minimum length (6 characters) and confirmation match (`password_confirmation`).
  - Passwords are encrypted using Laravel's Bcrypt hashing before database persistence.
  - Route: `PUT /admin/credentials` (`admin.credentials.update`).

### 3.2 System Settings & Timestamp Format Customization
- **Location**: `Administrator → Settings` (`resources/views/Administrator/content/settings.blade.php`).
- **Timestamp Display Format**:
  - Allows the Administrator to select between **12-Hour Format (AM/PM)** (e.g. `11:25 AM` or `11:25:00 AM`) and **24-Hour Format** (e.g. `11:25` or `11:25:00`).
  - Multiple date format options (`M d, Y`, `F d, Y`, `Y-m-d`, `m/d/Y`, `d/m/Y`).
  - Synchronous radio toggle (`12-hour` vs `24-hour`) linked with select dropdowns.
  - Live interactive preview box reflecting selections in real-time.
- **Database Persistence**: Formats are stored in the `system_settings` table via `SystemSetting::set('date_format')` and `SystemSetting::set('time_format')`.
- **System-Wide Formatting (`App\Services\SystemFormatService`)**:
  - Helper methods: `dateFormat()`, `timeFormat()`, `is24Hour()`, `timeFormatType()`, `formatDate()`, `formatTime()`, `formatDateTime()`.
  - Automatically shared with all Blade views via `View::composer('*')`.
  - Consistent across Booking records, Invoices, Confirmed Payments, Activity Logs, and due notifications without modifying underlying database datetimes.

### 3.3 Receptionist Settings Portal
- **Location**: Added `SETTINGS` navigation link to Receptionist sidebar (`resources/views/Receptionist/partials/sidebar.blade.php`).
- **Dedicated View**: `resources/views/Receptionist/content/settings.blade.php` (`GET /receptionist/settings`).
- **Design & Layout**: Replicates the Administrator Settings layout with Vintage Brown styling.
- **Content**:
  - **Receptionist Account Profile**: Shows logged-in staff name, username, email, and contact number.
  - **Timestamp Display Format**: Displays active 12h/24h format mode, date format, and time format.
  - **Live Synchronization**: Real-time ticking Manila clock and status badge indicating synchronization with Administrator settings.
  - **Security Separation**: Administrator credential management fields are completely excluded from the Receptionist view.

### 3.4 Activity Logs (Comprehensive Audit Trail)
- **Location**: `Administrator → Activity Logs` (`resources/views/Administrator/content/activities.blade.php`).
- **Automatic Event Recording (`App\Services\ActivityLogService`)**:
  1. **Payments**: Records confirmed check-out payments with payment method, amount, reference number, and responsible Receptionist.
  2. **Cancellations**: Records cancelled reservations and bookings with guest name, room numbers, and responsible Receptionist.
  3. **Overdues**: Automatically detects expired stays and logs them as **System Generated** (`staff_id: null`).
  4. **Login**: Exclusively logs Receptionist logins with username, date, time, IP address, and user agent.
  5. **Bookings**: Logs new immediate bookings and future reservations with stay type, room numbers, and responsible Receptionist.
- **Filters & Search**: Fast client-side and server-side filtering by user (All, System, Receptionist), activity type, date, and keyword search.

### 3.5 Sales Reports & Analytics
- **Location**: `Administrator → Sales Report` (`resources/views/Administrator/content/salerep.blade.php`).
- **Metrics**:
  - Total Revenue (PHP)
  - Average Daily Rate (ADR = Room Revenue / Total Room Nights)
  - Total Bookings & Check-outs
  - Monthly Target Progress Gauge (integrated with `monthly_targets` table)
  - Lost Revenue from Cancellations
  - Pending Payments & Due Invoices
- **CSV Export**: `GET /admin/sales-report/export` downloads an itemized, formatted CSV report with UTF-8 BOM encoding for seamless Microsoft Excel compatibility.

### 3.6 Room Management & Maintenance
- **Visual Grid**: Room cards color-coded by status (Green: Available, Red: Occupied, Blue: Reserved, Yellow: Under Maintenance).
- **Maintenance Blocking**: Create, schedule, and remove room blocks with start/end datetimes.
- **Overdue Clock**: Live counting red indicator on occupied rooms exceeding their scheduled check-out time.

---

## 4. REST & JSON API Endpoints Reference

| Endpoint | Method | Middleware | Description |
| :--- | :---: | :---: | :--- |
| `/api/system/settings` | `GET` | Public | Returns active date format, time format, formatted strings, and ISO server time. |
| `/api/admin/activities` | `GET` | `auth.admin` | Paginated Activity Logs with filters (`who`, `type`, `date`, `search`). |
| `/api/admin/overview-stats` | `GET` | `auth.admin` | Live KPI counters (occupancy, revenue, check-ins, check-outs). |
| `/api/admin/revenue-stats` | `GET` | `auth.admin` | Revenue trends grouped by today, weekly, monthly, or yearly. |
| `/api/admin/stay-type-stats`| `GET` | `auth.admin` | Short stay vs overnight stay distribution. |
| `/api/admin/notifications` | `GET` | `auth.admin` | Administrative notifications (cancellations and due departures). |
| `/api/admin/monthly-target` | `POST` | `auth.admin` | Stores or updates the monthly revenue target. |
| `/api/admin/sales-report/export` | `GET` | `auth.admin` | Generates and streams the Sales Report CSV download. |
| `/api/dashboard/notifications` | `GET` | `auth.receptionist` | Real-time overdue checks and due-guest notifications for Receptionist. |
| `/api/available-rooms` | `GET` | `auth.receptionist` | Available room IDs for specified check-in and check-out dates. |
| `/api/amenities` | `GET` | Public | Returns offered customizable hotel amenities. |

---

## 5. Verification & Testing Reference

The system includes automated PHPUnit / Pest feature test suites in `tests/Feature/`:
- `LoginAuthenticationTest.php`: Administrator and Receptionist authentication, password hashing, role enforcement, and session timeouts (8 tests).
- `AdminSettingsTest.php`: Administrator credential editing in Account Management, 12h/24h timestamp format toggling, Receptionist Settings rendering and role isolation (9 tests).
- `ActivityLogsTest.php`: End-to-end activity logging across payments, cancellations, overdues, logins, and bookings (8 tests).

### Running Tests
To run the test suites:
```bash
php artisan test --filter="AdminSettingsTest|LoginAuthenticationTest|ActivityLogsTest"
```
To run code formatting with Laravel Pint:
```bash
vendor/bin/pint --dirty --format agent
```
