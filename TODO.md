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
- [ ] Amenities list from the admin side (replace the temporary list in config/hotel.php)
- [ ] Billing page lists the amenity lines
- [ ] Login, receptionist account and 15-minute timeout
- [ ] Discount and refund requests
- [ ] Settings and Extra pages
- [ ] Remove the old unused popups (res-modal, avail-modal) and their scripts

## Known issues

- Notifications refresh on page load only
- booking.updateStatus route has no method (nothing calls it)
- Billing still shows rooms without the leading zero (Room::formatList not added)

## Open questions for the client

- No-show: how long is a reservation held?
- Cancellation: is the deposit refunded?
- Payment methods at check-out?
- Can a short stay be upgraded to 24 hours?

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

## Unfinished tasks

## Backend development

## Settings: Finished (Nov 20, 2026)

- [x] Allow only the administrator to customize the date and time formats displayed across system timestamps in real-time.
- [x] Allow the administrator to update their login username and password securely using Laravel Hash::make().
- [x] Dynamic live clock ticking with configured formats on Administrator and Receptionist topbars.
- [x] Real-time JSON API endpoint (`/api/system/settings`) providing active timestamp formats and current server time.

## Activity Logs: not finished

In Activity Logs. This is where it shown the receptionist actively did in the receptionist dashboard. it will display;

- [] Payment filter for Confirmed Payments
- [] Cancellations filter for Cancellations
- [] Overdues filter for Overdue
- [] Login filter for LogIn of Receptionist only



## Log In credentials for the Administrator and Receptionist not finished
- [] There should be already a saved username and password in migration table for the Administrator (only able to change it when already inside the Administrator dashboard in Account Management section)
- [] Let the credentials in the Administrator -> Account Management section be used for Logging in to the Receptionist side
NOTE: Do not edit anything in the Account Management just let the credentials be used for logging in by Receptionist



## Settings Content not finished
- [] Transfer the input field of Administrator Credentials username and password editing in Account Management after the Receptionist credentials, make it work to be able to let the Administrator to replace its current saved username and password.
- [] Timestamp Display Format