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
- [ ] Block Room & Date (receptionist sets rooms under maintenance)

## Next phase
- [ ] Red overdue stopwatch on the room card (PHP 150 per extra hour, stops when paid)
- [ ] Top bar ADD AMENITIES saves to the guest's bill (Booking History already does)
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