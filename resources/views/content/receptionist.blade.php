@extends('layouts.dashboard')

@section('title','Receptionist Dashboard')

@section('receptionist-content')

<div class="container-fluid p-0">
    <!-- Main Content -->
    <!-- Top Section: Status Cards & Quick Actions -->
    <div class="row mb-4 g-4">
        <!-- Date and Time -->
        <div class="d-flex gap-2 justify-content-end">
            <div class="border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary">
                <span id="current-date">{{ date('M d, Y') }}</span>
            </div>
            <div class="border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary">
                <span id="current-time">{{ date('h:i A') }}</span>
            </div>
        </div>
        
        <!-- Status Cards Column -->
        <div class="col-xl-8">
            <div class="row g-3 h-100">
                <!-- Available -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-success shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3">Available</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rounded-circle border border-2 border-success text-success d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                    <img src="{{ asset('icons/check.svg') }}" alt="Available" width="45" height="45">
                                </div>
                                <span class="fs-1 fw-bold text-success">{{ $availableRooms }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Occupied -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-danger shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3">Occupied</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rounded-circle border border-2 border-danger text-danger d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                    <img src="{{ asset('icons/person.svg') }}" alt="Available" width="45" height="45">
                                </div>
                                <span class="fs-1 fw-bold text-danger">{{ $occupiedRooms }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Reserved -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-warning shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3">Reserved</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                    <img src="{{ asset('icons/bookmark.svg') }}" alt="Reserved" width="40" height="40">
                                <span class="fs-1 fw-bold text-warning">{{ $reservedRooms }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Under Maintenance -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-secondary shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3" style="font-size: 0.8rem;">Under Maintenance</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                    <img src="{{ asset('icons/cone.svg') }}" alt="Under Maintenance" width="50" height="50">
                                <span class="fs-1 fw-bold text-secondary">{{ $underMaintenanceRooms }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Column -->
        <div class="col-xl-4">
            <div class="card h-100 bg-white border shadow-sm">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-secondary fw-bold mb-3">QUICK ACTIONS</h6>
                    <p class="small text-muted mb-3">Short stay ₱{{ number_format($shortRate) }} · Overnight ₱{{ number_format($overnightRate) }} per night</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold" data-bs-toggle="modal" data-bs-target="#res-modal">NEW RESERVATION</button>
                            
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold" data-bs-toggle="modal" data-bs-target="#avail-modal">CHECK-IN</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">CHECK OUT</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">ADD AMENITIES</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Section: Two Large Panels -->
    <div class="row mb-5 g-4">
        <div class="col-md-6">
            <div class="card shadow-sm border" style="min-height: 250px;">
                    <!-- Header with Filter -->
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                        <h6 class="text-secondary fw-bold text-uppercase m-0">Occupancy Trends</h6>
                        <div class="dropdown">
                            <!-- ID changed to chartFilterBtn to avoid conflict with the Room Grid filter -->
                            <button id="chartFilterBtn" class="btn btn-sm btn-outline-secondary dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                                Today
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item chart-filter" href="#" data-filter="today">Today</a></li>
                                <li><a class="dropdown-item chart-filter" href="#" data-filter="weekly">Weekly</a></li>
                            </ul>
                        </div>
                    </div>
                    <!-- Body with Loader & Chart -->
                    <div class="card-body position-relative bg-white rounded d-flex flex-column justify-content-center">
                            <!-- Loading Spinner Overlay -->
                            <div id="chartLoader" class="position-absolute top-50 start-50 translate-middle" style="display: none; z-index: 10;">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <!-- Chart Canvas -->
                        <div style="height: 220px; width: 100%;">
                            <canvas id="occupancyChart"></canvas>
                        </div>
                    </div>
            </div>
        </div>
        <!-- Secondary Data Panel Section -->
        <div class="col-md-6">
            <div class="card shadow-sm border" style="min-height: 250px;">
                <div class="card-body bg-white rounded">
                    <h6 class="text-uppercase text-secondary fw-bold mb-3">NOTIFICATIONS</h6>
                    <p class="text-muted small m-0">No notifications yet. Approvals from the admin will show here.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Link the external roomgrid.css file -->
    <link href="{{ asset('css/room-grid.css') }}" rel="stylesheet">
    <!-- Link the overlay roomgrid.css file -->
    <link href="{{ asset('css/grid-overlay.css') }}" rel="stylesheet">


    <!-- Filter Rooms Section --> 
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-end">
            <div class="dropdown">
                <button id="filterDropdownBtn" class="btn btn-sm btn-outline-secondary dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                    All Rooms
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item room-filter" href="#" data-filter="all">All Rooms</a></li>
                    <li><a class="dropdown-item room-filter" href="#" data-filter="occupied">Occupied</a></li>
                    <li><a class="dropdown-item room-filter" href="#" data-filter="available">Available</a></li>
                    <li><a class="dropdown-item room-filter" href="#" data-filter="reserved">Reserved</a></li>
                    <li><a class="dropdown-item room-filter" href="#" data-filter="maintenance">Maintenance</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Room Status Grid -->    
    <div class="row">
        @foreach($rooms as $room)
        <div class="col-custom-5 room-card" data-status="{{ $room->status === 'under_maintenance' ? 'maintenance' : $room->status }}">
            <div class="card shadow-sm bg-white overflow-hidden border" style="width: 100%; height: 220px;">
                <div class="border-bottom position-relative" style="height: 140px;">
                    <!-- Room Image -->
                    <img src="{{ asset('images/room.png') }}" alt="Room Image" class="w-100 h-100 object-fit-cover">
                    @if($room->status == 'occupied')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-occupied"></div>
                    @elseif($room->status == 'reserved')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-reserved"></div>
                    @elseif($room->status == 'maintenance' || $room->status == 'under_maintenance')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-maintenance"></div>
                    @endif
                </div>
                <!-- Room Details -->
                <div class="card-body p-3">
                    <h6 class="card-title text-secondary fw-bold mb-2">ROOM {{ str_pad($room->room_number ?? $room->id, 2, '0', STR_PAD_LEFT) }}</h6>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        @if($room->status == 'occupied')
                            <span class="text-uppercase fw-bold text-danger d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-danger rounded-circle p-1 me-2 border border-light"></span> OCCUPIED
                            </span>
                        @elseif($room->status == 'available')
                            <span class="text-uppercase fw-bold text-success d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-success rounded-circle p-1 me-2 border border-light"></span> AVAILABLE
                            </span>
                        @elseif($room->status == 'reserved')
                            <span class="text-uppercase fw-bold text-warning d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-warning rounded-circle p-1 me-2 border border-light"></span> RESERVED
                            </span>
                        @elseif($room->status == 'maintenance' || $room->status == 'under_maintenance')
                            <span class="text-uppercase fw-bold text-danger d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-danger rounded-circle p-1 me-2 border border-light"></span> MAINTENANCE
                            </span>
                        @else
                            <span class="text-uppercase fw-bold text-secondary d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-secondary rounded-circle p-1 me-2 border border-light"></span> {{ strtoupper(str_replace('_', ' ', $room->status)) }}
                            </span>
                        @endif
                        <span class="text-muted fw-semibold" style="font-size: 0.75rem;">{{ $room->timeRange ?? '00:00-23:59' }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    
</div>
@include('modals.quickresmodal')

@push('scripts')
<!-- Dynamic Chart Data -->
<script>
    window.dynamicChartData = {!! json_encode($chartData) !!};
</script>
@endpush
@endsection