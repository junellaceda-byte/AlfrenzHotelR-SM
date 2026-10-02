# Hotel Reservation & Sales Management System (SalesMS)

## 1. Executive Summary & Purpose
The **Hotel Reservation & Sales Management System (SalesMS)** is a robust web application designed to streamline hotel operations. Built on the Laravel framework, this system provides a comprehensive solution for managing guest records, room availability, bookings, and billing operations. 

Its core business logic revolves around simplifying the receptionist's workflow, ensuring accurate tracking of room statuses (e.g., available, occupied, reserved), and providing an efficient checkout and invoicing process.

## 2. Architecture Overview
The application strictly adheres to the Laravel **Model-View-Controller (MVC)** architectural pattern, ensuring a clean separation of concerns:

### Controllers (`app/Http/Controllers`)
- **`DashboardController`**: Handles the primary dashboard view and aggregates data for the receptionist overview.
- **`ReservationController`**: Manages advance reservations and dynamic room availability logic.
- **`BookingController`**: Manages the reservation lifecycle, including creating new bookings and updating booking statuses (e.g., check-in, check-out, cancel).
- **`BillingController`**: Responsible for financial operations, processing invoices, adding extra amenities to a booking, and handling payments.

### Models (`app/Models`)
Models provide an object-oriented interface for database interactions using the Eloquent ORM. Key models include:
`Guest`, `Room`, `Reservation`, `Booking`, `Invoice`, `AvailableAmenity`, and `PaymentLog`.

### Middlewares (`app/Http/Middleware`)
The application leverages standard Laravel middleware for HTTP request filtering, session management, and CSRF protection, ensuring secure and authenticated access to the dashboard and management routes.

## 3. Database & Schema Design
The SQLite database schema is defined in `@database/migrations` and is structured around the following primary tables and Eloquent relationships. Database seeders (`@database/seeders`) like `RoomSeeder`, `AvailableAmenitySeeder`, and `InvoiceSeeder` are also available for populating initial data via `DatabaseSeeder`.

- **`guests`**: Stores guest information (`first_name`, `last_name`, `phone`).
  - *Relationship*: One-to-Many with `bookings` and `reservations`.
- **`rooms`**: Manages room details and pricing (`room_number`, `short_stay_rate`, `overnight_rate`, `status`).
  - *Relationship*: One-to-Many with `bookings` and `reservations`.
- **`reservations`**: Tracks advance reservations (`guest_id`, `room_id`, `check_in_date`, `status`).
  - *Relationship*: Belongs to `Guest` and `Room`.
- **`bookings`**: The central pivot table connecting guests and rooms (`guest_id`, `room_id`, `stay_type`, `check_in_date`, `status`).
  - *Relationship*: Belongs to `Guest` and `Room`. Has One/Many `Invoice`, `AvailableAmenity`, and `PaymentLog`.
- **`invoices`**: Tracks financial records per booking (`booking_id`, `room_charges`, `grand_total`, `status`).
- **`available_amenities`**: Logs extra items or services available for request during a stay (`name`, `price`, `stock`).
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
  - `reservation.blade.php`
  - `booking.blade.php`
  - `guestlist.blade.php`
  - `billing.blade.php`
- **Partials & Modals**: Modular Blade components are used for reusable UI elements (e.g., forms, confirmation modals, `quickresmodal.blade.php`).

## 5. Routing & API Endpoints
The web routes are defined in `@routes/web.php` and map directly to the system's controllers:

| Method | Endpoint | Controller Action | Description |
|--------|----------|-------------------|-------------|
| `GET` | `/` | `Closure` | Redirects to the dashboard |
| `GET` | `/login` | `Closure` | Displays the login view |
| `GET` | `/dashboard` | `DashboardController@index`| Renders the main dashboard |
| `GET` | `/reservation` | `ReservationController@index` | Lists reservation records |
| `POST` | `/reservation` | `ReservationController@store` | Creates a new reservation |
| `PUT` | `/reservation/{id}` | `ReservationController@update`| Updates a reservation |
| `DELETE` | `/reservation/{id}` | `ReservationController@destroy`| Deletes a reservation |
| `GET` | `/api/available-rooms`| `ReservationController@getAvailableRooms`| Gets available rooms |
| `GET` | `/bookings` | `BookingController@index` | Lists booking records |
| `POST` | `/bookings` | `BookingController@store` | Creates a new booking |
| `PUT` | `/bookings/{id}/status` | `BookingController@updateStatus`| Updates a booking's status |
| `PUT` | `/bookings/{id}` | `BookingController@update`| Updates a booking |
| `DELETE` | `/bookings/{id}` | `BookingController@destroy`| Deletes a booking |
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


<!-- Bazinga!!! -->
<!-- ============ FOR THE FRONT-END DEVELOPER ============ --> 
1. Color Palette & UI Theme
The application uses a Vintage Brown visual theme based on the palette #443627, #D98324, #EFDCAB, and #F2F6D0.

### Color Variables

  color-primary-brown : #443627;/* Sidebar & Topbar Background */
  color-accent-gold :   #D98324;/* Borders, Dividers & Highlights */
  color-warm-beige :    #EFDCAB;/* Cards, Dropdowns & Secondary Backgrounds */
  color-cream-light:   #F2F6D0;/* Main Content Area Background / Light Accents */

### Styling Guidelines
Sidebar & Topbar: Set background to --color-primary-brown (#443627).

Borders & Accent Lines: Set borders, active state indicators, and line dividers on the sidebar and topbar to --color-accent-gold (#D98324).

2. Component Specifications
### 2.1 Topbar Notification Bell (Due Guests)
Functionality: Indicates guests whose reservations/stay dates are due for checkout.

Interaction: Clicking the notification bell icon toggles a dropdown listing all due guests.

State: Badge count updates dynamically when due guests exist.

### 2.2 Dashboard Occupancy Trends & Script Organization
Component Location: Main data panel on dashboard.blade.php.

Script Management:

Write all scipt for charts inside resources/views/scripts.blade.php.

Do not inline heavy JS scripts inside content views.

Include the script partial using Blade:

2.3 Room Status Grid (4x5 Layout)
Grid Structure: Fixed 4 columns × 5 rows layout displaying all 20 rooms.

### Visual States & Overlays:

#### Room Status	
Visual Display / Overlay Effect
Available	Standard room image card without overlay.
Occupied	Semi-transparent gray overlay (rgba(100, 100, 100, 0.6)) over the image.
Maintenance / Reserved	Diagonal red stripe overlay (repeating-linear-gradient or red tint) over the image.


### 2.4 Data Table Pagination Fix
Align and style bottom pagination controls across all listed data tables (Reservations, Bookings, Billing, etc.).

Ensure pagination elements use proper Bootstrap/Tailwind pagination classes, centered or right-aligned at the bottom of each table card footer.

### 3. Front-End Deliverables Checklist
[ ] Apply Vintage Brown theme (#443627) and Golden accent lines (#D98324) to Sidebar and Topbar.

[ ] Connect Due Guest alert popover to the topbar notification bell.

[ ] Move chart scripts into scripts.blade.php and @include them on the Dashboard page.

[ ] Build the 4x5 Room Status Grid with Gray (Occupied) and Red Striped (Maintenance) overlays.

[ ] Standardize and fix bottom table pagination components across all data views.
