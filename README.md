# Hotel Reservation & Sales Management System (SalesMS)

## 1. Executive Summary & Purpose
The **Hotel Reservation & Sales Management System (SalesMS)** is a robust web application designed to streamline hotel operations. Built on the Laravel framework, this system provides a comprehensive solution for managing guest records, room availability, bookings, and billing operations. 

Its core business logic revolves around simplifying the receptionist's workflow, ensuring accurate tracking of room statuses (e.g., available, occupied, reserved), and providing an efficient checkout and invoicing process.

## 2. Architecture Overview
The application strictly adheres to the Laravel **Model-View-Controller (MVC)** architectural pattern, ensuring a clean separation of concerns:

### Controllers (`app/Http/Controllers`)
- **`DashboardController`**: Handles the primary dashboard view and aggregates data for the receptionist overview.
- **`BookingController`**: Manages the reservation lifecycle, including creating new bookings and updating booking statuses (e.g., check-in, check-out, cancel).
- **`BillingController`**: Responsible for financial operations, processing invoices, adding extra amenities to a booking, and handling payments.

### Models (`app/Models`)
Models provide an object-oriented interface for database interactions using the Eloquent ORM. Key models include:
`Guest`, `Room`, `Booking`, `Invoices`, `Amenities`, and `Payment_logs`.

### Middlewares (`app/Http/Middleware`)
The application leverages standard Laravel middleware for HTTP request filtering, session management, and CSRF protection, ensuring secure and authenticated access to the dashboard and management routes.

## 3. Database & Schema Design
The SQLite database schema is defined in `@database/migrations` and is structured around the following primary tables and Eloquent relationships:

- **`guests`**: Stores guest information (`first_name`, `last_name`, `phone`).
  - *Relationship*: One-to-Many with `bookings`.
- **`rooms`**: Manages room details and pricing (`room_number`, `short_stay_rate`, `overnight_rate`, `status`).
  - *Relationship*: One-to-Many with `bookings`.
- **`bookings`**: The central pivot table connecting guests and rooms (`guest_id`, `room_id`, `stay_type`, `check_in_date`, `status`).
  - *Relationship*: Belongs to `Guest` and `Room`. Has One/Many `Invoices`, `Amenities`, and `PaymentLogs`.
- **`invoices`**: Tracks financial records per booking (`booking_id`, `room_charges`, `grand_total`, `status`).
- **`amenities`**: Logs extra items or services requested during a stay (`booking_id`, `item_name`, `unit_price`, `quantity`).
- **`payment_logs`**: Records individual payment transactions linked to a booking.

## 4. UI & Styling Architecture
The frontend architecture relies exclusively on **Bootstrap 5 utilities** for its styling footprint, ensuring a responsive, mobile-first design without the need for extensive custom CSS. 

The core layout templates and views reside in `@resources/views`:
- **Layouts (`resources/views/layouts/`)**: Contains the foundational structures that wrap the content:
  - `dashboard.blade.php`
  - `payment.blade.php`
  - `guest.blade.php`
  - `booked.blade.php`
- **Content (`resources/views/content/`)**: Holds the specific page views:
  - `receptionist.blade.php` (main interface)
  - `booking.blade.php`
  - `guestlist.blade.php`
  - `billing.blade.php`
- **Partials & Modals**: Modular Blade components are used for reusable UI elements (e.g., forms, confirmation modals).

## 5. Routing & API Endpoints
The web routes are defined in `@routes/web.php` and map directly to the system's controllers:

| Method | Endpoint | Controller Action | Description |
|--------|----------|-------------------|-------------|
| `GET` | `/` | `Closure` | Redirects to the dashboard |
| `GET` | `/login` | `Closure` | Displays the login view |
| `GET` | `/dashboard` | `DashboardController@index`| Renders the main dashboard |
| `GET` | `/bookings` | `BookingController@index` | Lists booking records |
| `POST` | `/bookings` | `BookingController@store` | Creates a new booking |
| `PUT` | `/bookings/{id}/status` | `BookingController@updateStatus`| Updates a booking's status |
| `GET` | `/guest` | `Closure` | Displays the guest list view |
| `GET` | `/billing` | `BillingController@index` | Renders the billing dashboard |
| `POST` | `/billing/{id}/add-amenity` | `BillingController@addAmenity`| Adds an amenity to an invoice |
| `POST` | `/billing/{id}/process-payment`| `BillingController@processPayment`| Processes an invoice payment |

## 6. Getting Started & Installation
For documentation writers and front-end developers, follow these steps to set up the project locally:

1. **Clone the repository and install dependencies:**
   ```bash
   git clone <repository-url>
   cd HotelReservation-SalesMS
   composer install
   npm install
   ```

2. **Set up the environment file:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure the Database (SQLite):**
   Ensure your `.env` file is set to use SQLite:
   ```env
   DB_CONNECTION=sqlite
   ```
   Then, create the database file and run the migrations:
   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

4. **Run the local development server:**
   ```bash
   php artisan serve
   ```
   *In a separate terminal, compile the frontend assets if necessary:*
   ```bash
   npm run dev
   ```

You can now access the application at `http://localhost:8000`.
