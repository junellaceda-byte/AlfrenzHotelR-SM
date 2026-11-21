<!-- Section 2: Activity Logs Content -->


<!-- Activity Logs Box Container -->
<div class="border rounded overflow-hidden">

    <!-- Name Header with Bottom Border -->
    <div class="p-4 border-bottom bg-light">
        <h5 class="fw-bold m-0">System Activity Logs</h5>
    </div>

    <!-- 4 Interactions / Filters -->
    <div class="p-4">
        <div class="row g-3">

            <!-- Filter 1: Who -->
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold text-muted small mb-1">Who</label>
                <select class="form-select">
                    <option>Everyone</option>
                    <option>Receptionist</option>
                    <option>Owner</option>
                </select>
            </div>

            <!-- Filter 2: Type -->
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold text-muted small mb-1">Type</label>
                <select class="form-select">
                    <option>All activity</option>
                    <option>Payment</option>
                    <option>Cancellations</option>
                    <option>Bookings</option>
                    <option>Overdues</option>
                    <option>Login</option>
                </select>
            </div>

            <!-- Filter 3: Date -->
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold text-muted small mb-1">Date</label>
                <input type="date" class="form-control">
            </div>

            <!-- Filter 4: Search Bar -->
            <div class="col-12 col-md-3">
                <label class="form-label fw-bold text-muted small mb-1">Search</label>
                <input type="text" class="form-control" placeholder="Room, Guest, Transaction">
            </div>

        </div>
    </div>

    <!-- Empty space for the actual logs to populate below the filters -->
    <div class="p-4 pt-0">
        <!-- Logs will go here -->
    </div>

</div>