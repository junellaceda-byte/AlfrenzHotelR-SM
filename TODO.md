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
- Revenue Statistics in Administrator side should be connected to the Billing section in Receptionist side base on the Mont/Date/Year
- Notifications should display the Revenue loss like cancellations ehich is connected to the Reservation section in Receptionist side
- Activities Logs supposed to be connected to the Check-ins, Check-outs, and Payments from the Receptionist side.
- Sales Reports in Administrator side
- - Total Revenue should be connected to the Billing section from Receptionist side based on the current month only
- - Avg. Daily Rate______________
- - Total Bookings______________
- - Monthly Target should have own database for the readable Target on the monthly and its button should have a pop up modal for setting the Monthly Target
- - Monthly Target and Monthly Target Progress should be connecting to each other tracking the progress on the Monthly Target
- - Occupancy & Revenue Trend should be connected to the Receptionist side in the Billing section based on the Month, Date, and Year
- - Lost Revenue (Cancellations) should be connected to the Receptionist side and reading the cancelled reservations
- - Pending Payments should be connected to the Receptinist side in the Billing section in Pending Payments table
- - Revenue Breakdown: Room Rates vs Extra Charges should be connected to the Receptionist side in the Billing section on Confirmed Payments based on the Month and Date and the Extra Chrges and Addons is supposed to be the Amenities that has added to the Billing of the guest from Receptionist side
- - Amenities Comparison should be connected to the Billing section from Receptionist side on the Confirmed Payments based on the Month and Dates
- - 