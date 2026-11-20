<!-- Link to your external CSS file for the overview -->
<link rel="stylesheet" href="{{ asset('CSS/Administrator/overview.css') }}">

<div class="row g-2 mb-3">
    <!-- Left Side: 2x2 Grid for Cards -->
    <div class="col-12 col-lg-8">
        <div class="row g-2 h-100">
            <!-- Card 1: Today's Occupancy (Pink, Static, Flat) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
                    <h4 class="text-dark mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Today's Occupancy</h4>
                    <div class="fs-4 fs-sm-3 fw-bold text-dark"><span id="admin-occupancy-count">{{ $occupancyToday }}</span> <small class="fs-6 fw-normal text-dark d-block d-sm-inline">of <span id="admin-total-rooms">{{ $totalRooms }}</span> rooms</small></div>
                </div>
            </div>

            <!-- Card 2: Revenue Today (Pink, Static, Flat) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
                    <h4 class="text-dark mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Revenue Today</h4>
                    <div class="fs-4 fs-sm-3 fw-bold text-dark" id="admin-revenue-today">₱{{ number_format($revenueToday, 2) }}</div>
                </div>
            </div>

            <!-- Card 3: Check-ins Today (Blue, 3D Button) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-blue-3d clickable" data-bs-toggle="modal" data-bs-target="#checkinsModal" role="button">
                    <h4 class="text-white mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Check-ins Today</h4>
                    <div class="fs-4 fs-sm-3 fw-bold text-white" id="admin-checkins-today">{{ $checkInsToday }}</div>
                </div>
            </div>

            <!-- Card 4: Check-outs Today (Blue, 3D Button) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-blue-3d clickable" data-bs-toggle="modal" data-bs-target="#checkoutsModal" role="button">
                    <h4 class="text-white mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Check-outs Today</h4>
                    <div class="fs-4 fs-sm-3 fw-bold text-white" id="admin-checkouts-today">{{ $checkOutsToday }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Notifications Box -->
    <div class="col-12 col-lg-4">
        <div class="card h-100 border rounded d-flex flex-column">
            <!-- Box Header -->
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-start p-2 p-sm-3">
                <h5 class="fw-bold m-0 fs-6 text-dark">NOTIFICATIONS</h5>
                <small class="text-muted text-end fw-bold" style="font-size: 0.7rem; max-width: 60%; line-height: 1.2;">
                    (30 minute alert due of guests check-out time)
                </small>
            </div>
            
            <!-- Box Body (Scrollable if notifications overflow) -->
            <div class="card-body flex-grow-1 overflow-auto p-3" style="min-height: 150px; max-height: 300px;">
                <!-- Populated dynamically via /api/admin/notifications -->
                <div id="admin-notification-list">
                    <div class="text-center text-muted mt-4">
                        <small>No recent notifications.</small>
                    </div>
                </div>
            </div>

            <!-- Box Footer -->
            <div class="card-footer bg-white border-top text-end p-2 px-3">
                <a href="#" class="text-decoration-none fw-bold" style="color: #443627; font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#adminNotificationsModal">
                    SEE MORE &raquo;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<!-- Added g-3 (gap) so there is space between the two charts on desktop -->
<div class="row g-3">
    <!-- Left Side: Revenue Statistics (Takes up 8 columns to match cards above) -->
    <div class="col-12 col-lg-8">
        <!-- Added h-100 so both cards stretch to equal height -->
        <div class="card border rounded p-3 p-sm-4 h-100">
            
            <div class="d-flex flex-row justify-content-between align-items-center mb-4 gap-3">
                <h5 class="fw-bold m-0">Revenue Statistics</h5>

                <!-- Responsive Dropdown Filter -->
                <select id="revenueFilter" class="form-select border-dark fw-bold shadow-sm" style="width: auto; min-width: 120px; cursor: pointer;">
                    <option value="today" selected>Today</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <div style="position: relative; height: 350px; width: 100%;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Right Side: Booking Sources (Takes up 4 columns to match Notifications above) -->
    <div class="col-12 col-lg-4">
        <div class="card border rounded p-3 p-sm-4 h-100 d-flex flex-column">
            <h5 class="fw-bold m-0 mb-4">Booking Sources</h5>
            
            <!-- Flex-grow forces the canvas wrapper to fill the available space evenly -->
            <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="position: relative; min-height: 250px; width: 100%;">
                <canvas id="bookingSourceChart"></canvas>
            </div>
        </div>
    </div>
</div>

@include('Administrator.modals.inout')
@include('Administrator.modals.notifications')