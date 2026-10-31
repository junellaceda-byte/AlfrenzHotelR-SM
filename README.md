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

  color-primary-brown : #443627;/* Sidebar & Topbar Background */
  color-accent-gold :   #D98324;/* Borders, Dividers & Highlights */
  color-warm-beige :    #EFDCAB;/* Cards, Dropdowns & Secondary Backgrounds */
  color-cream-light:   #F2F6D0;/* Main Content Area Background / Light Accents */

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
1. *Verification:* Run `git status` to confirm the output says "On branch <branch-name>".

**2. Find the commit hash**
Pull up the branch's history to locate the exact commit you need.
`bash
2. git log --oneline
`
*Verification:* You will see a list of recent commits with 7-character IDs (e.g., `a1b2c3d`). Note the ID of the commit you want to view, then press `q` to exit the log.

**3. View the specific commit**
Jump back in time to the exact state of that commit.
`bash
3. git checkout <commit-hash>
`
*Verification:* Your files will immediately update to match that older version. 

> **Note (Detached HEAD state):** Git will warn you that you are in a "detached HEAD" state. This is normal and safe. It simply means you are looking at a past snapshot disconnected from the active timeline. You can run the code and inspect files, but do not make new commits here.

**4. Return to the current code**
When you are finished looking around, return to the latest version of your branch.
`bash
4. git switch <branch-name>
`
*Verification:* Your files will revert back to the newest version, and `git status` will confirm you are back on the active branch.

### Status
See TODO.md for what is done and what is planned.