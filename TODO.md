# Receptionist Dashboard TODO

Rule: never edit or delete old migrations, add new ones.

## Done (Oct 31)

- [x] Overview: status cards from live data, room grid with filters, notifications (30 minutes before check-out)
- [x] Top bar: live date and time, notification bell, BOOK NOW and ADD AMENITIES
- [x] Book Now modal: Book Now / Today and Reserve, short stay (3/6/12 hours) and 24-hour stay with or without breakfast, Senior and PWD 20% discount, room buttons
- [x] Reservation: filters (Arriving today, Cancelled, All), A to Z by first name, Check in and Cancel
- [x] Booking History: full details, filters (All, In house, Completed, Cancelled), View and Add amenities
- [x] Pricing in config/hotel.php, non-VAT (no tax)
- [x] Payment is taken at check-out (Billing page)

## In progress

- [x] Block Room & Date (receptionist sets rooms under maintenance)

## Next phase

- [x] Red overdue stopwatch on the room card (PHP 150 per extra hour, stops when paid)
- [x] Top bar ADD AMENITIES saves to the guest's bill (Booking History already does)
- [x] Amenities list from the admin side (replace the temporary list in config/hotel.php)
- [x] Billing page lists the amenity lines
- [x] Login, receptionist account and 15-minute timeout
- [x] Discount and refund requests
- [x] Settings and Extra pages
- [x] Remove the old unused popups (res-modal, avail-modal) and their scripts
- [x] System Format in Administrator side

## DO THIS

- [x] Revenue Statistics in Administrator side connected to the Billing section in Receptionist side based on Today, Weekly, Monthly, and Yearly
- [x] Notifications should display the Revenue loss like cancellations which is connected to the Reservation section in Receptionist side
- [x] Room Management in Administrator side: replicated design and functionality of Receptionist Room Management using migration-based database tables (Add, Edit, Delete, Block Room) synchronized with Receptionist availability and bookings

## Finished 11/19/2026

- [x] Sales Reports in Administrator side
- [x] Total Revenue connected to the Billing section from Receptionist side with Month/Date/Year filters
- [x] Avg. Daily Rate (ADR) calculated from room revenue divided by occupied room nights
- [x] Total Bookings count from confirmed billing and check-outs
- [x] Monthly Target database (`monthly_targets` table) and pop up modal for setting the Monthly Target
- [x] Monthly Target and Monthly Target Progress connected to each other tracking the progress on the ApexCharts Gauge
- [x] Occupancy & Revenue Trend connected to Receptionist Billing based on Month, Date, and Year (Weekly, Monthly, Yearly)
- [x] Lost Revenue (Cancellations) reading cancelled reservations and bookings
- [x] Pending Payments connected to Receptionist Billing Pending Payments table
- [x] Revenue Breakdown: Room Rates vs Extra Charges connected to Confirmed Payments based on Month, Date, and Year
- [x] Amenities Comparison connected to Confirmed Payments extra charges / amenities based on Month, Date, and Year

## TODO

## Nov 20, 2026

## Finished tasks

## Backend development

## Settings: Finished (Nov 20, 2026)

- [x] Allow only the administrator to customize the date and time formats displayed across system timestamps in real-time.
- [x] Allow the administrator to update their login username and password securely using Laravel Hash::make().
- [x] Dynamic live clock ticking with configured formats on Administrator and Receptionist topbars.
- [x] Real-time JSON API endpoint (`/api/system/settings`) providing active timestamp formats and current server time.

## Activity Logs: Finished (Nov 21, 2026)

In Activity Logs. This is where it shows what the receptionist actively did in the receptionist dashboard. It displays:

- [x] Payment filter for Confirmed Payments (including the Receptionist who processed each payment)
- [x] Cancellations filter for Cancellations (including the Receptionist who performed the cancellation)
- [x] Overdues filter for Overdue (including guest name, room number, relevant date and time, system-generated)
- [x] Login filter for LogIn of Receptionist only (including username, login date, and login time)
- [x] Bookings filter for newly created bookings (immediate occupancy and future reservations)
- [x] Automatic activity recording across all action types with receptionist user ID association
- [x] Separation of different receptionist accounts
- [x] Real-time JSON API endpoint (`/api/admin/activities`) for instant filtering

## Log In credentials for the Administrator and Receptionist: Finished (Nov 21, 2026)

- [x] Administrator login with saved username and password
- [x] Let the credentials in the Administrator -> Account Management section be used for Logging in to the Receptionist side
      NOTE: Do not edit anything in the Account Management just let the credentials be used for logging in by Receptionist

## Settings Content not finished

- [x] Transfer the input field of Administrator Credentials username and password editing in Account Management after the Receptionist credentials, make it work to be able to let the Administrator to replace its current saved username and password.
- [x] Timestamp Display Format

## Nov 22, 2026

## Problems

## Displaying Problem?

- [x] problem in modal when reserving or book, it doesn't register the deposit, which less the pay. Because the guest already advance deposit. When they are already check in, the guest can't get refund their payments.

## Displaying in Real-time problem, removing the quantity option?

- [x] the problem in amenities modal, the quantity does not function anything because it has in the right side a list of amenities that was added, and also when the admin edit a item from the amenities section, while the receptionist is adding amenities in the room the selected amenities list is not updating in real-time. Is it okay though?

## Nov 26, 2026

## Finished

- [x] Time Display format in Receptionist side in the Settings and also in the topbar.

## Unfinished task

- [x] Problems in contact number has letters and it should be digits 0-9, and allows 7-15 digits (the international standard for phone numbers). Either provide a separate country code dropdown (like +63) or allow users to type the + symbol at the beginning.

- [x] The Monthly Target wasn't working properly, it was suppose to update, save and set the target in the Sales Reports.

- [x] In the Receptionist side, the Billing section, when the reading the header called Room, it display digit 1-9, but it should display with leading zeros (01, 02, 03, 04, 05, 06, 07, 08, 09). As it will be misleading to the eye of the user.

## Unfinished tasks

- [] When Export the Sales reports charts and data in Administrator side into PDF, and/or Excel.

## Finished tasks (Nov 27, 2026)

- [x] Amenities (Admin): Interactive ON/OFF status toggle with circular handle in the Amenity Management table Status column (moved out of Edit Amenity modal, directly updates database status and controls visibility in Receptionist → Add Amenities to Room) and image upload Remove button to clear selected file and restore default state.
- [x] Account Management (Admin): Contact number validation restricting inputs to 7–15 digits (0–9) with international country code dropdown / optional `+` prefix support.
- [x] Monthly Sales Target (Admin): Fixed setting, updating, and saving monthly revenue targets in the database, reflected in Sales Reports and target progress calculations.
- [x] Billing (Receptionist): Room number formatting in the Room column to display single-digit room numbers with leading zeros (`01`–`09`) while keeping `10` and above unchanged.
- [x] Sales Reports (Admin) – Excel Export & Charts: Removed customizable theme option in favor of the vintage brown and beige palette, enabled immediate Excel download matching the selected date, month, or year filter, and automatically generated native Excel charts replicating the Sales Reports charts.
- [x] Room Management (Admin) – Room Status Grid: Removed the room ID label (e.g., `ID: #1`) beside the room number so only the room number is displayed.
