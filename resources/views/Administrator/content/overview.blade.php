<!-- Link to your external CSS file for the overview -->
<link rel="stylesheet" href="{{ asset('CSS/Administrator/overview.css') }}">

<div class="row g-2 mb-3">
    <!-- Left Side: 2x2 Grid for Cards -->
    <div class="col-12 col-lg-8">
        <div class="row g-2 h-100">
            <!-- Card 1: Today's Occupancy (Pink, Static, Flat) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div class="overflow-hidden">
                            <h4 class="text-dark mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Today's Occupancy</h4>
                            <div class="fs-4 fs-sm-3 fw-bold text-dark"><span
                                    id="admin-occupancy-count">{{ $occupancyToday }}</span> <small
                                    class="fs-6 fw-normal text-dark d-block d-sm-inline">of <span
                                        id="admin-total-rooms">{{ $totalRooms }}</span> rooms</small></div>
                        </div>
                        <img src="{{ asset('images/occupancy.png') }}" alt="Occupancy" class="ms-2 flex-shrink-0"
                            style="width: 55px; height: 55px; object-fit: contain;">
                    </div>
                </div>
            </div>

            <!-- Card 2: Revenue Today (Pink, Static, Flat) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div class="overflow-hidden">
                            <h4 class="text-dark mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Revenue Today</h4>
                            <div class="fs-4 fs-sm-3 fw-bold text-dark" id="admin-revenue-today">
                                ₱{{ number_format($revenueToday, 2) }}</div>
                        </div>
                        <img src="{{ asset('images/revenue.png') }}" alt="Revenue" class="ms-2 flex-shrink-0"
                            style="width: 55px; height: 55px; object-fit: contain;">
                    </div>
                </div>
            </div>

            <!-- Card 3: Check-ins Today (Blue, 3D Button) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-blue-3d clickable" data-bs-toggle="modal"
                    data-bs-target="#checkinsModal" role="button">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div class="overflow-hidden">
                            <h4 class="text-white mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Check-ins Today</h4>
                            <div class="fs-4 fs-sm-3 fw-bold text-white" id="admin-checkins-today">{{ $checkInsToday }}
                            </div>
                        </div>
                        <img src="{{ asset('images/check-in.png') }}" alt="Check-ins" class="ms-2 flex-shrink-0"
                            style="width: 55px; height: 55px; object-fit: contain;">
                    </div>
                </div>
            </div>

            <!-- Card 4: Check-outs Today (Blue, 3D Button) -->
            <div class="col-6">
                <div class="card h-100 rounded p-2 p-sm-3 highlight-blue-3d clickable" data-bs-toggle="modal"
                    data-bs-target="#checkoutsModal" role="button">
                    <div class="d-flex justify-content-between align-items-center h-100">
                        <div class="overflow-hidden">
                            <h4 class="text-white mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Check-outs Today</h4>
                            <div class="fs-4 fs-sm-3 fw-bold text-white" id="admin-checkouts-today">
                                {{ $checkOutsToday }}</div>
                        </div>
                        <img src="{{ asset('images/check-out.png') }}" alt="Check-outs" class="ms-2 flex-shrink-0"
                            style="width: 55px; height: 55px; object-fit: contain;">
                    </div>
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
                <a href="#" class="text-decoration-none fw-bold" style="color: #443627; font-size: 0.85rem;"
                    data-bs-toggle="modal" data-bs-target="#adminNotificationsModal">
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
                <select id="revenueFilter" class="form-select border-dark fw-bold shadow-sm"
                    style="width: auto; min-width: 120px; cursor: pointer;">
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

    <!-- Right Side: Booking Stay Type (Takes up 4 columns to match Notifications above) -->
    <div class="col-12 col-lg-4">
        <div class="card border rounded p-3 p-sm-4 h-100 d-flex flex-column">
            <!-- Header with Title and Timeframe Filter Options -->
            <div class="d-flex flex-column gap-2 mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="fw-bold m-0 fs-6 text-dark">Booking Stay Type</h5>
                    <!-- Timeframe Pill Buttons: Weekly, Monthly, Yearly -->
                    <div class="btn-group btn-group-sm" role="group" aria-label="Booking Stay Type Timeframe"
                        id="stayTypeTimeframeButtons">
                        <button type="button" class="btn btn-outline-secondary stay-tf-btn"
                            data-tf="weekly">Weekly</button>
                        <button type="button" class="btn stay-tf-btn active" data-tf="monthly"
                            style="background-color: #6A4435; border-color: #6A4435; color: #ffffff;">Monthly</button>
                        <button type="button" class="btn btn-outline-secondary stay-tf-btn"
                            data-tf="yearly">Yearly</button>
                    </div>
                </div>

                <!-- Calendar and Select Controls -->
                <div class="d-flex align-items-center gap-1.5 flex-wrap pt-1" id="stayTypeFilterControls">
                    <!-- Calendar Date Filter (Select Specific Day) -->
                    <div class="input-group input-group-sm flex-nowrap" style="max-width: 165px;"
                        id="stayTypeCalendarWrapper">
                        <span class="input-group-text bg-white px-1.5 text-muted" title="Select specific day">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor"
                                viewBox="0 0 16 16">
                                <path
                                    d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z" />
                            </svg>
                        </span>
                        <input type="date" class="form-control form-control-sm px-1" id="stayTypeDatePicker" value=""
                            aria-label="Select specific day" title="Select a specific day">
                        <button type="button" class="btn btn-sm btn-outline-secondary px-1.5 d-none"
                            id="clearStayTypeDateBtn" title="Clear date filter and restore timeframe"
                            style="font-size: 0.9rem; line-height: 1;">&times;</button>
                    </div>

                    <!-- Month Select (Jan - Dec) -->
                    <select id="stayTypeMonthSelect" class="form-select form-select-sm"
                        style="width: auto; min-width: 95px;" aria-label="Select month">
                        <option value="all">All Months</option>
                        @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $mIdx => $mName)
                            <option value="{{ $mIdx + 1 }}" {{ (now()->setTimezone('Asia/Manila')->format('n') == ($mIdx + 1)) ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>

                    <!-- Week Select (Week 1 - Week 5) for Weekly mode -->
                    <select id="stayTypeWeekSelect" class="form-select form-select-sm d-none"
                        style="width: auto; min-width: 90px;" aria-label="Select week">
                        <option value="all" selected>All Weeks</option>
                        <option value="1">Week 1</option>
                        <option value="2">Week 2</option>
                        <option value="3">Week 3</option>
                        <option value="4">Week 4</option>
                        <option value="5">Week 5</option>
                    </select>

                    <!-- Year Select (2022 - 2026) -->
                    <select id="stayTypeYearSelect" class="form-select form-select-sm"
                        style="width: auto; min-width: 80px;" aria-label="Select year">
                        @for($y = 2026; $y >= 2022; $y--)
                            <option value="{{ $y }}" {{ (now()->setTimezone('Asia/Manila')->format('Y') == $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Active Date/Timeframe Indicator -->
                <div class="d-flex align-items-center justify-content-between text-muted mt-1 px-0.5"
                    style="font-size: 0.72rem;" id="stayTypeFilterStatusRow">
                    <span id="stayTypeScopeLabel" class="fw-semibold text-dark">Timeframe: Monthly</span>
                    <span id="stayTypeExactDateBadge"
                        class="badge bg-light text-secondary border fw-normal d-none"></span>
                </div>
            </div>

            <!-- Flex-grow forces the canvas wrapper to fill the available space evenly -->
            <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center"
                style="position: relative; min-height: 220px; width: 100%;">
                <canvas id="bookingSourceChart"></canvas>
            </div>

            <!-- Stay Type Legend & Percentage Breakdown -->
            <div class="mt-3 pt-2 border-top" id="stayTypeBreakdownLegend">
                <div class="row g-1.5 small" id="stayTypeLegendRows">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </div>
    </div>
</div>

@include('Administrator.modals.inout')
@include('Administrator.modals.notifications')