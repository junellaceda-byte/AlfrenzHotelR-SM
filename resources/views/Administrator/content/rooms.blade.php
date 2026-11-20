<link href="{{ asset('css/room-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/grid-overlay.css') }}" rel="stylesheet">
<link href="{{ asset('css/Administrator/rooms.css') }}" rel="stylesheet">

<div class="card p-3 p-sm-4 border rounded shadow-sm">
    <!-- Header with Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold m-0 fs-5 fs-sm-4 text-dark">Room Management</h4>
            <small class="text-muted">Manage hotel room records and monitor real-time room availability</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-dark fw-bold btn-sm py-2 px-3" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                + Add Room
            </button>
            <button type="button" class="btn btn-brown text-uppercase fw-bold btn-sm py-2 px-3" data-bs-toggle="modal" data-bs-target="#adminBlockRoomModal">
                Block Room
            </button>
        </div>
    </div>

    <!-- 4 Status Summary Cards (Available, Occupied, Reserved, Under Maintenance) -->
    <div class="row g-3 mb-4">
        <!-- Available -->
        <div class="col-6 col-xl-3">
            <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                <div>
                    <div class="kpi-card-title text-uppercase mb-2">Available</div>
                    <div class="kpi-card-number text-success" id="admin-kpi-available">{{ $availableRooms ?? 0 }}</div>
                </div>
                <div class="kpi-icon-box rounded-circle border border-2 border-success text-success bg-light">
                    <img src="{{ asset('icons/check.svg') }}" alt="Available" width="28" height="28">
                </div>
            </div>
        </div>

        <!-- Occupied -->
        <div class="col-6 col-xl-3">
            <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                <div>
                    <div class="kpi-card-title text-uppercase mb-2">Occupied</div>
                    <div class="kpi-card-number text-danger" id="admin-kpi-occupied">{{ $occupiedRooms ?? 0 }}</div>
                </div>
                <div class="kpi-icon-box rounded-circle border border-2 border-danger text-danger bg-light">
                    <img src="{{ asset('icons/person.svg') }}" alt="Occupied" width="28" height="28">
                </div>
            </div>
        </div>

        <!-- Reserved -->
        <div class="col-6 col-xl-3">
            <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                <div>
                    <div class="kpi-card-title text-uppercase mb-2">Reserved</div>
                    <div class="kpi-card-number text-warning" id="admin-kpi-reserved">{{ $reservedRooms ?? 0 }}</div>
                </div>
                <div class="kpi-icon-box rounded-circle border border-2 border-warning text-warning bg-light">
                    <img src="{{ asset('icons/bookmark.svg') }}" alt="Reserved" width="24" height="24">
                </div>
            </div>
        </div>

        <!-- Under Maintenance -->
        <div class="col-6 col-xl-3">
            <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm">
                <div>
                    <div class="kpi-card-title text-uppercase mb-2">Under Maintenance</div>
                    <div class="kpi-card-number text-secondary" id="admin-kpi-maintenance">{{ $underMaintenanceRooms ?? 0 }}</div>
                </div>
                <div class="kpi-icon-box rounded-circle border border-2 border-secondary text-secondary bg-light">
                    <img src="{{ asset('icons/cone.svg') }}" alt="Under Maintenance" width="26" height="26">
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Buttons Bar (ALL, AVAILABLE, OCCUPIED, RESERVED, UNDER MAINTENANCE) -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2 filter-btn-group" id="adminRoomFilterGroup">
            <button type="button" class="btn btn-filter active" data-filter="all">ALL</button>
            <button type="button" class="btn btn-filter" data-filter="available">AVAILABLE</button>
            <button type="button" class="btn btn-filter" data-filter="occupied">OCCUPIED</button>
            <button type="button" class="btn btn-filter" data-filter="reserved">RESERVED</button>
            <button type="button" class="btn btn-filter" data-filter="under_maintenance">UNDER MAINTENANCE</button>
        </div>
        <div class="small text-muted fw-semibold">
            Total: <span id="admin-total-rooms-label">{{ count($rooms ?? []) }}</span> Rooms
        </div>
    </div>

    <!-- Room Status Grid -->
    <div class="hotel-room-grid" id="hotelRoomGrid">
        @forelse($rooms ?? [] as $room)
            @php
                $timeParts = explode('-', $room->timeRange ?? '00:00-23:59');
                $inTime = trim($timeParts[0] ?? '');
                $outTime = trim($timeParts[1] ?? '');
                $isIdle = ($room->status === 'available' || ($room->status !== 'under_maintenance' && $inTime === '00:00' && $outTime === '23:59'));
                $roomNumberStr = str_pad($room->room_number ?? $room->id, 2, '0', STR_PAD_LEFT);
                $isOverdue = !empty($room->is_overdue);
                $checkoutDatetime = $room->checkout_datetime ?? '';
                $reservationArrival = $room->reservation_arrival ?? '';
                $cardStatus = ($room->status === 'maintenance' || $room->status === 'under_maintenance') ? 'under_maintenance' : $room->status;
            @endphp

            <div class="hotel-room-card {{ $room->status === 'occupied' ? 'is-occupied' : '' }}" 
                 data-status="{{ $cardStatus }}" 
                 data-room-id="{{ $room->id }}"
                 data-checkout="{{ $checkoutDatetime }}"
                 data-reservation-arrival="{{ $reservationArrival }}">
                
                <!-- Room Image with Status Overlay & Admin Action Buttons -->
                <div class="room-image-wrapper position-relative">
                    <img src="{{ asset('images/room.png') }}" alt="Room {{ $roomNumberStr }}">
                    
                    @if($room->status == 'occupied')
                        @if($isOverdue)
                            <div class="position-absolute top-0 start-0 w-100 h-100 overlay-overdue">OVERDUE</div>
                        @else
                            <div class="position-absolute top-0 start-0 w-100 h-100 overlay-occupied"></div>
                        @endif
                    @elseif($room->status == 'reserved')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-reserved"></div>
                    @elseif($room->status == 'maintenance' || $room->status == 'under_maintenance')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-maintenance"></div>
                    @endif

                    <!-- Admin Action Buttons (Edit / Delete) -->
                    <div class="room-admin-actions">
                        <button type="button" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Edit Room" data-bs-toggle="modal" data-bs-target="#editRoomModal{{ $room->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                        </button>
                        @if($room->status !== 'occupied')
                            <button type="button" class="btn btn-sm btn-danger rounded-circle shadow-sm" title="Delete Room" data-bs-toggle="modal" data-bs-target="#deleteRoomModal{{ $room->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Room Details -->
                <div class="room-card-body p-3">
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <h6 class="room-number-heading fw-bold m-0 text-dark">Room {{ $roomNumberStr }}</h6>
                        <span class="small text-muted" style="font-size: 0.72rem;">ID: #{{ $room->id }}</span>
                    </div>

                    <!-- Room Rates -->
                    <div class="text-muted small mb-2" style="font-size: 0.74rem;">
                        <span>Short: <strong>₱{{ number_format((float) $room->short_stay_rate, 2) }}</strong></span> &bull; 
                        <span>Ovn: <strong>₱{{ number_format((float) $room->overnight_rate, 2) }}</strong></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <!-- Status text badge -->
                        <div>
                            @if($room->status === 'occupied')
                                <span class="badge bg-danger">Occupied</span>
                            @elseif($room->status === 'available')
                                <span class="badge bg-success">Available</span>
                            @elseif($room->status === 'reserved')
                                <span class="badge bg-warning text-dark">Reserved</span>
                            @elseif($room->status === 'under_maintenance' || $room->status === 'maintenance')
                                <span class="badge bg-secondary">Under Maintenance</span>
                            @else
                                <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $room->status)) }}</span>
                            @endif
                        </div>

                        <!-- Dynamic times / arrivals / blocking -->
                        @if($room->status === 'under_maintenance' || $room->status === 'maintenance')
                            @if(!empty($room->blocking_period))
                                <div class="room-times-text text-end room-blocking-period text-muted" style="font-size: 0.72rem;">
                                    {{ $room->blocking_period }}
                                </div>
                            @endif
                        @elseif($room->status === 'occupied' && !$isIdle && !empty($inTime) && !empty($outTime))
                            <div class="room-times-text text-end text-muted" style="font-size: 0.72rem;">
                                <div><span class="text-muted">IN:</span> {{ $inTime }}</div>
                                <div><span class="text-muted">OUT:</span> {{ $outTime }}</div>
                            </div>
                        @elseif($room->status === 'reserved' && !empty($reservationArrival))
                            <div class="room-times-text text-end room-reservation-arrival text-muted" style="font-size: 0.72rem;">
                                <div><span class="text-muted">Arrival:</span></div>
                                <div>{{ $reservationArrival }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Include Edit & Delete Modals for this room -->
            @include('Administrator.modals.editroom', ['room' => $room])
            @include('Administrator.modals.deleteroom', ['room' => $room])

        @empty
            <div class="col-12 text-center py-5 text-muted">
                <p class="mb-2 fw-semibold">No rooms found in the database.</p>
                <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    + Add Your First Room
                </button>
            </div>
        @endforelse
    </div>
</div>

<!-- Add Room & Block Room Modals -->
@include('Administrator.modals.addroom')
@include('Administrator.modals.blockroom')
