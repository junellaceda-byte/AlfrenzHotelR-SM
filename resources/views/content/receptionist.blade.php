@extends('layouts.dashboard')

@section('title', 'Receptionist Dashboard - Alfrenzo\'s Hotel')

@section('receptionist-content')

<link href="{{ asset('css/room-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/grid-overlay.css') }}" rel="stylesheet">
<!-- Main Content -->
<div class="container-fluid p-0">
    <!-- Top Section: 4 Status Summary Cards (Left) and Notifications Panel (Right) -->
    <div class="row g-3 mb-4">
        <!-- 4 Status Summary Cards (2x2 Grid) -->
        <div class="col-12 col-xl-7 col-xxl-8">
            <div class="row g-3">
                <!-- Available -->
                <div class="col-sm-6">
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2">Available</div>
                            <div class="kpi-card-number text-success">{{ $availableRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2 border-success text-success bg-light">
                            <img src="{{ asset('icons/check.svg') }}" alt="Available" width="28" height="28">
                        </div>
                    </div>
                </div>

                <!-- Occupied -->
                <div class="col-sm-6">
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2">Occupied</div>
                            <div class="kpi-card-number text-danger">{{ $occupiedRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2 border-danger text-danger bg-light">
                            <img src="{{ asset('icons/person.svg') }}" alt="Occupied" width="28" height="28">
                        </div>
                    </div>
                </div>

                <!-- Reserved -->
                <div class="col-sm-6">
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2">Reserved</div>
                            <div class="kpi-card-number text-warning">{{ $reservedRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2 border-warning text-warning bg-light">
                            <img src="{{ asset('icons/bookmark.svg') }}" alt="Reserved" width="24" height="24">
                        </div>
                    </div>
                </div>

                <!-- Under Maintenance -->
                <div class="col-sm-6">
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2">Under Maintenance</div>
                            <div class="kpi-card-number text-secondary">{{ $underMaintenanceRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2 border-secondary text-secondary bg-light">
                            <img src="{{ asset('icons/cone.svg') }}" alt="Under Maintenance" width="26" height="26">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Panel (Right) -->
        <div class="col-12 col-xl-5 col-xxl-4">
            <div class="notifications-panel p-3 h-100 shadow-sm justify-content-between">
                <div>
                    <!-- Notification Header -->
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <h6 class="text-uppercase fw-bold text-dark m-0" style="font-size: 0.9rem; letter-spacing: 0.04em;">NOTIFICATIONS</h6>
                        <small class="text-muted" style="font-size: 0.72rem;">(30 minute alert due of guests check-out time)</small>
                    </div>
                    <!-- Notification List -->
                    <div class="py-2" id="notificationList">
                        @forelse($dueGuests->take(2) as $guest)
                            <div class="d-flex align-items-start gap-2 py-2 border-bottom small">
                                <span class="fw-bold">&rarr;</span>
                                <div>
                                    <span class="fw-semibold">RM {{ $guest->room_number }}</span> - {{ $guest->name }} -
                                    @if($guest->is_overdue)
                                        <span class="text-danger fw-semibold">was due at {{ $guest->checkout_time }} (overdue)</span>
                                    @else
                                        {{ $guest->checkout_time }} is their due time to check out ({{ $guest->minutes }} min left)
                                    @endif
                                </div>
                            </div>
                        @empty
                            <!-- No due rooms -->
                            <p class="text-muted small mb-0 py-2">No rooms due within the next 30 minutes.</p>
                        @endforelse
                    </div>
                </div>

                <div class="text-end pt-2 border-top">
                    <a href="#" class="see-more-link" data-bs-toggle="modal" data-bs-target="#allDueRoomsModal">
                        SEE MORE &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

        <!-- Filter Buttons Bar (ALL, AVAILABLE, OCCUPIED, UNDER MAINTENANCE) & BLOCK ROOM -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2 filter-btn-group">
            <button type="button" class="btn btn-filter active" data-filter="all">ALL</button>
            <button type="button" class="btn btn-filter" data-filter="available">AVAILABLE</button>
            <button type="button" class="btn btn-filter" data-filter="occupied">OCCUPIED</button>
            <button type="button" class="btn btn-filter" data-filter="under_maintenance">UNDER MAINTENANCE</button>
        </div>
        <!-- Block Room Button -->
        <button type="button" class="btn btn-brown text-uppercase" data-bs-toggle="modal" data-bs-target="#blockRoomModal">
            BLOCK ROOM
        </button>
    </div>

    <!-- Room Status Grid -->
    <div class="hotel-room-grid" id="hotelRoomGrid">
        @foreach($rooms as $room)
            @php
                $timeParts = explode('-', $room->timeRange ?? '00:00-23:59');
                $inTime = trim($timeParts[0] ?? '');
                $outTime = trim($timeParts[1] ?? '');
                $isIdle = ($room->status === 'available' || ($inTime === '00:00' && $outTime === '23:59'));
                $roomNumberStr = str_pad($room->room_number ?? $room->id, 2, '0', STR_PAD_LEFT);
            @endphp
            
            <div class="hotel-room-card {{ $room->status === 'occupied' ? 'is-occupied' : '' }}" data-status="{{ $room->status === 'maintenance' ? 'under_maintenance' : $room->status }}">
                <!-- Room Image with Status Overlay -->
                <div class="room-image-wrapper">
                    <img src="{{ asset('images/room.png') }}" alt="Room {{ $roomNumberStr }}">
                    @if($room->status == 'occupied')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-occupied"></div>
                    @elseif($room->status == 'reserved')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-reserved"></div>
                    @elseif($room->status == 'maintenance' || $room->status == 'under_maintenance')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-maintenance"></div>
                    @endif

                    <!-- Hover Action Button: Only on Occupied Rooms -->
                    @if($room->status === 'occupied')
                        <div class="card-hover-action">
                            <button type="button" class="btn btn-card-amenities text-uppercase btn-trigger-amenity" data-room-id="{{ $room->id }}" data-room-number="{{ $roomNumberStr }}">
                                ADD AMENITIES
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Room Details -->
                <div class="room-card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <h6 class="room-number-heading">Room {{ $roomNumberStr }}</h6>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <!-- Status text -->
                        <div>
                            @if($room->status === 'occupied')
                                <span class="room-status-badge text-danger">Occupied</span>
                            @elseif($room->status === 'available')
                                <span class="room-status-badge text-success">Available</span>
                            @elseif($room->status === 'reserved')
                                <span class="room-status-badge text-warning">Reserved</span>
                            @elseif($room->status === 'under_maintenance' || $room->status === 'maintenance')
                                <span class="room-status-badge text-secondary">Under Maintenance</span>
                            @else
                                <span class="room-status-badge text-secondary">{{ ucwords(str_replace('_', ' ', $room->status)) }}</span>
                            @endif
                        </div>

                        <!-- IN / OUT Times: Hidden for idle rooms -->
                        @if(!$isIdle && !empty($inTime) && !empty($outTime))
                            <div class="room-times-text text-end">
                                <div><span class="text-muted">IN:</span> {{ $inTime }}</div>
                                <div><span class="text-muted">OUT:</span> {{ $outTime }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal 3: Due Checkout Rooms Modal -->
<div class="modal fade" id="allDueRoomsModal" tabindex="-1" aria-labelledby="allDueRoomsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="allDueRoomsModalLabel">Upcoming & Due Checkouts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Notification List -->
            <div class="modal-body p-4">
                @forelse($dueGuests as $guest)
                    <div class="d-flex align-items-start gap-2 py-2 border-bottom small">
                        <span class="fw-bold">&rarr;</span>
                        <div>
                            <span class="fw-semibold">RM {{ $guest->room_number }}</span> - {{ $guest->name }} -
                            @if($guest->is_overdue)
                                <span class="text-danger fw-semibold">was due at {{ $guest->checkout_time }} (overdue)</span>
                            @else
                                {{ $guest->checkout_time }} is their due time to check out ({{ $guest->minutes }} min left)
                            @endif
                        </div>
                    </div>
                @empty
                    <!-- No due rooms -->
                    <div class="text-center py-4 text-secondary">
                        <p class="mb-0 fw-semibold">No rooms due within the next 30 minutes.</p>
                        <small class="text-muted d-block mt-1">Guests nearing their check-out time appear here automatically.</small>
                    </div>
                @endforelse
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Modal -->
@include('modals.quickresmodal')
@include('partials.scripts')

@endsection