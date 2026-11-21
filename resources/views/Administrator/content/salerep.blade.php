<!-- Reuse the overview CSS to keep the design consistent -->
<link rel="stylesheet" href="{{ asset('CSS/Administrator/overview.css') }}">

<!-- Filter Bar for Sales Reports (Date, Month, Year) -->
<div class="card border rounded p-3 mb-3 bg-white shadow-sm">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#443627" viewBox="0 0 16 16">
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
            </svg>
            <div>
                <h6 class="fw-bold m-0" style="color: #443627;">Sales Reports Filter</h6>
                <small id="salesFilterSubtitle" class="text-muted">Showing live sales data from Receptionist Billing</small>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Year Selector -->
            <div>
                <select id="salesFilterYear" class="form-select form-select-sm border-secondary fw-semibold" style="min-width: 95px; color: #443627;">
                    @php
                        $currentYear = (int) now()->setTimezone('Asia/Manila')->format('Y');
                    @endphp
                    @for($y = $currentYear + 1; $y >= $currentYear - 4; $y--)
                        <option value="{{ $y }}" {{ $y === $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <!-- Month Selector -->
            <div>
                <select id="salesFilterMonth" class="form-select form-select-sm border-secondary fw-semibold" style="min-width: 130px; color: #443627;">
                    @php
                        $currentMonth = (int) now()->setTimezone('Asia/Manila')->format('n');
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                    @endphp
                    <option value="all">All Months</option>
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $num === $currentMonth ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Optional Specific Date Picker -->
            <div class="d-flex align-items-center gap-1">
                <input type="date" id="salesFilterDate" class="form-control form-control-sm border-secondary" title="Filter by specific day" style="width: 140px; color: #443627;">
                <button type="button" id="salesFilterClearDateBtn" class="btn btn-sm btn-outline-secondary text-nowrap d-none" title="Clear specific day filter">
                    &times;
                </button>
            </div>

            <!-- Quick Filter Buttons -->
            <div class="btn-group" role="group">
                <button type="button" id="salesFilterThisMonthBtn" class="btn btn-sm text-white" style="background-color: #443627;">
                    This Month
                </button>
                <button type="button" id="salesFilterTodayBtn" class="btn btn-sm btn-outline-secondary">
                    Today
                </button>
            </div>

            <!-- Export to Excel Button -->
            <button type="button" id="salesExportExcelBtn" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" title="Export current report data to Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Export to Excel
            </button>
        </div>
    </div>
</div>

<!-- Step 1: Top Row Key Financial Metrics -->
<div class="row g-2 mb-3">
    <!-- Card 1: Total Revenue (Month/Filtered) -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
            <h4 id="salesTotalRevenueTitle" class="text-dark mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Total Revenue (Nov)</h4>
            <div id="salesTotalRevenueValue" class="fs-4 fs-sm-3 fw-bold text-dark">₱124,500</div>
        </div>
    </div>

    <!-- Card 2: Average Daily Rate (ADR) -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
            <h4 class="text-dark mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Avg. Daily Rate</h4>
            <div id="salesAdrValue" class="fs-4 fs-sm-3 fw-bold text-dark">₱1,850</div>
        </div>
    </div>

    <!-- Card 3: Total Bookings -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
            <h4 class="text-dark mt-0 mb-1 mb-sm-2 fs-6 text-truncate">Total Bookings</h4>
            <div id="salesTotalBookingsValue" class="fs-4 fs-sm-3 fw-bold text-dark">86</div>
        </div>
    </div>

    <!-- Card 4: Monthly Target Goal -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 rounded p-2 p-sm-3 highlight-pink-flat">
            <div class="d-flex justify-content-between align-items-center mb-1 mb-sm-2 gap-1">
                <h4 class="text-dark m-0 fs-6 text-truncate">Monthly Target</h4>
                <button type="button"
                    class="btn text-white text-nowrap d-inline-flex align-items-center justify-content-center"
                    data-bs-toggle="modal" data-bs-target="#setTargetModal"
                    style="background-color: #30AFFF; width: 105px; height: 30px; font-size: 0.875rem; padding: 0;">
                    Set Target
                </button>
            </div>
            <div id="monthlyTargetValue" class="fs-4 fs-sm-3 fw-bold text-dark">₱150,000</div>
        </div>
    </div>
</div>

<!-- Step 2: Middle Row Charts (Occupancy Trend & Revenue Target Gauge) -->
<div class="row g-3 mb-3">
    <!-- Left Side: Occupancy Trend Line Chart (Takes up 8 columns) -->
    <div class="col-12 col-lg-8">
        <div class="card border rounded p-3 p-sm-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4 gap-2">
                <h5 class="fw-bold m-0">Occupancy & Revenue Trend</h5>
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                    <div class="btn-group" role="group" aria-label="Occupancy trend period filter">
                        <button type="button" class="btn btn-sm fw-semibold occupancy-trend-btn active"
                            data-period="weekly"
                            style="background-color: #443627; border-color: #443627; color: #ffffff;">
                            Weekly
                        </button>
                        <button type="button" class="btn btn-sm fw-semibold occupancy-trend-btn" data-period="monthly"
                            style="background-color: transparent; border-color: #734F46; color: #734F46;">
                            Monthly
                        </button>
                        <button type="button" class="btn btn-sm fw-semibold occupancy-trend-btn" data-period="yearly"
                            style="background-color: transparent; border-color: #734F46; color: #734F46;">
                            Yearly
                        </button>
                    </div>
                </div>
            </div>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="occupancyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Right Side: Revenue Target Gauge Chart (Takes up 4 columns) -->
    <div class="col-12 col-lg-4">
        <div class="card border rounded p-3 p-sm-4 h-100 d-flex flex-column">
            <h5 class="fw-bold m-0 mb-3">Monthly Target Progress</h5>
            <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="min-height: 250px;">
                <!-- ApexCharts Gauge container -->
                <div id="revenueGaugeChart" style="width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Step 3: Cancellation & Pending Balances vs Revenue Breakdown -->
<div class="row g-3 mb-3">
    <!-- Left Side: Stacked Metric Cards (Takes up 4 columns) -->
    <div class="col-12 col-lg-4 d-flex flex-column gap-3">

        <!-- Top Card: Cancellations -->
        <div class="card rounded p-3 p-sm-4 highlight-pink-flat" style="background-color: #FFCED8;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h4 class="text-dark mt-0 fs-6 text-truncate">Lost Revenue (Cancellations)</h4>
                <span id="salesCancellationsBadge" class="badge bg-danger rounded-pill">5 Bookings</span>
            </div>
            <div id="salesCancellationsLost" class="fs-4 fs-sm-3 fw-bold text-danger">-₱12,500</div>
            <small class="text-dark mt-2 d-block" style="font-size: 0.8rem;">
                *Potential revenue lost from cancelled reservations in this period.
            </small>
        </div>

        <!-- Bottom Card: Pending / Unsettled Balances -->
        <!-- flex-grow-1 ensures it stretches slightly to match the exact height of the chart next to it -->
        <div class="card rounded p-3 p-sm-4 border flex-grow-1" style="border-left: 5px solid #443627 !important;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h4 class="mt-0 fs-6 text-truncate" style="color: #443627; font-weight: 600;">Pending Payments</h4>
                <span id="salesPendingGuestsBadge" class="badge rounded-pill" style="background-color: #443627; color: white;">3 Guests</span>
            </div>
            <div id="salesPendingAmount" class="fs-4 fs-sm-3 fw-bold" style="color: #443627;">₱8,400</div>
            <small class="text-muted mt-2 d-block" style="font-size: 0.8rem;">
                *Unsettled balances from Receptionist Billing pending payments table.
            </small>
        </div>

    </div>

    <!-- Right Side: Revenue Breakdown Stacked Bar Chart (8 Columns) -->
    <div class="col-12 col-lg-8">
        <div class="card border rounded p-3 p-sm-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-4 gap-2">
                <h5 class="fw-bold m-0">Revenue Breakdown: Room Rates vs Extra Charges</h5>
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                    <div class="btn-group" role="group" aria-label="Revenue breakdown period filter">
                        <button type="button" class="btn btn-sm fw-semibold revenue-breakdown-btn active"
                            data-period="weekly"
                            style="background-color: #443627; border-color: #443627; color: #ffffff;">
                            Weekly
                        </button>
                        <button type="button" class="btn btn-sm fw-semibold revenue-breakdown-btn" data-period="monthly"
                            style="background-color: transparent; border-color: #734F46; color: #734F46;">
                            Monthly
                        </button>
                    </div>
                </div>
            </div>
            <div style="position: relative; height: 320px; width: 100%;">
                <canvas id="revenueBreakdownChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Step 4: Amenities Comparison (Ranked Horizontal Bar Chart) -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card border rounded p-3 p-sm-4">
            <!-- Header: Title, Dynamic Date Subtitle, and Weekly/Monthly Filter Buttons -->
            <div class="d-flex flex-wrap justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
                <div>
                    <h5 class="fw-bold m-0" style="color: #443627;">Amenities Comparison</h5>
                    <small id="amenitiesDateSubtitle" class="text-muted">Nov 11 – Nov 17, 2026</small>
                </div>

                <div class="btn-group" role="group" aria-label="Amenities period filter">
                    <button type="button" class="btn btn-sm fw-semibold amenities-filter-btn active"
                        data-period="weekly" style="background-color: #443627; border-color: #443627; color: #ffffff;">
                        Weekly
                    </button>
                    <button type="button" class="btn btn-sm fw-semibold amenities-filter-btn" data-period="monthly"
                        style="background-color: transparent; border-color: #734F46; color: #734F46;">
                        Monthly
                    </button>
                </div>
            </div>

            <!-- Ranked Horizontal Bars Container -->
            <div id="amenitiesChartList" class="d-flex flex-column gap-3"></div>
        </div>
    </div>
</div>

@include('Administrator.modals.settarget')

<!-- Scripts for the charts -->
<!-- 1. Local Chart.js for the Line Chart -->
<script src="{{ asset('js/Administrator JS/chart.min.js') }}"></script>

<!-- 2. ApexCharts CDN for the Gauge Chart -->
<script src="{{ asset('js/Administrator JS/apexcharts.min.js') }}"></script>

<!-- Export to Excel button: reads current active filter and triggers CSV download -->
<script>
(function () {
    'use strict';

    document.getElementById('salesExportExcelBtn').addEventListener('click', function () {
        var year  = document.getElementById('salesFilterYear').value;
        var month = document.getElementById('salesFilterMonth').value;
        var date  = document.getElementById('salesFilterDate').value || '';

        var params = new URLSearchParams({ year: year, month: month });
        if (date) {
            params.set('date', date);
        }

        var url = '/admin/sales-report/export?' + params.toString();
        var link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}());
</script>
