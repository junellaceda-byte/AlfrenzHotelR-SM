# Receptionist Dashboard TODO

## Dashboard
- [] Count of available, occupied, reserved, maintenance rooms
- [] Both rates shown (600 short stay, 2,200 overnight)
- [] Buttons: New Reservation, Check-In, Check-Out, Add Amenities
- [] Room grid with status filter
- [] Occupancy chart

## Reservations
- [] List with filters (arriving today, upcoming, cancelled, all)
- [] Create reservation
- [] Cancel with cancellation rule

## Bookings
- [] List of all stays
- [] Search
- [] Filters (in-house, upcoming, completed, cancelled)

## Billing
- [] Unpaid charges per room
- [] Pay by cash, card, or e-wallet
- [] Tax and change due
- [] Receipt
- [] Paid list
- [] Discount and refund requests


## Rule: never edit or delete old migrations, add new ones.

## Questions for the client
- How many hours is the 600 short stay?
- What happens when a short stay runs over (hourly fee, or becomes overnight)?
- Is 12% tax added, or are rates already tax-inclusive?
- Check-in and check-out times for overnight?
- Is a deposit required for reservations?
- Cancellation rule (free window, fee)?
- Do discounts and refunds exist?
- Payment methods (cash, GCash, card)?
- Can a short stay be reserved in advance, or walk-in only?
- Extra charges besides amenities (extra person, late check-out)?


## Found on Oct 16
- Greeting shows "Name" placeholder
- Phone shows "None"
- Future-dated booking should be a reservation
- Room 8 may stay occupied after checkout
- Date format inconsistent