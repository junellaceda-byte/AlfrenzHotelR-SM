@extends('layouts.dashboard')

@section('title', 'Receptionist Dashboard - Alfrenzo\'s Hotel')

@section('receptionist-content')

<link href="{{ asset('css/room-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/grid-overlay.css') }}" rel="stylesheet">
<!-- Main Content -->
<div class="container-fluid p-0">
    <!-- Top Section: 4 KPI Cards (Left) and Notifications Panel (Right) -->
    <div class="row g-3 mb-4">
        <!-- 4 KPI Status Summary Cards (2x2 Grid) -->
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
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <h6 class="text-uppercase fw-bold text-dark m-0" style="font-size: 0.9rem; letter-spacing: 0.04em;">NOTIFICATIONS</h6>
                        <small class="text-muted" style="font-size: 0.72rem;">(1 hour alert due of guests check-out time)</small>
                    </div>
                    
                    <div class="py-2" id="notificationList">
                        <p class="text-muted small mb-0 py-2">No rooms due within the next hour.</p>
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

    <!-- Filter Buttons Bar (ALL, AVAILABLE, OCCUPIED, UNDER MAINTENANCE - Strict Rule: NO Reserved) -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 filter-btn-group">
        <button type="button" class="btn btn-filter active" data-filter="all">ALL</button>
        <button type="button" class="btn btn-filter" data-filter="available">AVAILABLE</button>
        <button type="button" class="btn btn-filter" data-filter="occupied">OCCUPIED</button>
        <button type="button" class="btn btn-filter" data-filter="under_maintenance">UNDER MAINTENANCE</button>
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

<!-- Modal 1: Book Choice Modal (Book Now / Today vs Reserve) -->
<div class="modal fade" id="bookChoiceModal" tabindex="-1" aria-labelledby="bookChoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-dark" id="bookChoiceModalLabel">Select Booking Action</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-secondary small mb-3">Choose whether to process a direct walk-in check-in or create a future reservation.</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-dark py-2 fw-bold text-uppercase" style="background-color: #6A4435; border-color: #6A4435;" id="btnChoiceBookNow">
                        Book Now / Today
                    </button>
                    <button type="button" class="btn btn-outline-dark py-2 fw-bold text-uppercase" id="btnChoiceReserve">
                        Reserve
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Add Amenities Modal -->
<div class="modal fade" id="amenitiesModal" tabindex="-1" aria-labelledby="amenitiesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="amenitiesModalLabel">Add Amenities to Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @php
                    $occupiedRoomsList = $rooms->where('status', 'occupied');
                @endphp
                
                @if($occupiedRoomsList->isEmpty())
                    <div class="alert alert-secondary py-3 mb-0 text-center small">
                        There are currently no occupied rooms.
                    </div>
                @else
                    <!-- Room Selection -->
                    <div class="mb-3">
                        <label for="amenityRoomSelect" class="form-label fw-bold small text-secondary">SELECT OCCUPIED ROOM</label>
                        <select class="form-select" id="amenityRoomSelect">
                            <option value="" disabled selected>-- Choose an occupied room --</option>
                            @foreach($occupiedRoomsList as $occRoom)
                                <option value="{{ $occRoom->id }}" data-room-number="{{ str_pad($occRoom->room_number ?? $occRoom->id, 2, '0', STR_PAD_LEFT) }}">
                                    Room {{ str_pad($occRoom->room_number ?? $occRoom->id, 2, '0', STR_PAD_LEFT) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Guest Details Placeholder -->
                    <div class="p-3 mb-3 bg-light rounded border" id="guestDetailsPlaceholder">
                        <h6 class="fw-bold text-dark small text-uppercase mb-2">Current Guest Details</h6>
                        <div class="small text-secondary">
                            <div><strong>Guest Name:</strong> <span id="placeholderGuestName">Juan Dela Cruz</span></div>
                            <div><strong>Assigned Room:</strong> <span id="placeholderRoomNumber">Room --</span></div>
                            <div><strong>Status:</strong> <span class="badge bg-danger">Occupied</span></div>
                        </div>
                    </div>

                    <!-- Amenities Form (UI only for now) -->
                    <form id="amenitiesForm">
                        <div class="mb-3">
                            <label for="amenityItemSelect" class="form-label fw-bold small text-secondary">CHOOSE AMENITY</label>
                            <select class="form-select" id="amenityItemSelect" required>
                                <option value="" disabled selected>Select amenity item...</option>
                                <option value="extra_bed">Extra Bed / Mattress (₱500.00)</option>
                                <option value="extra_pillow_towel">Towel & Pillow Set (₱150.00)</option>
                                <option value="toiletries_kit">Premium Toiletries Kit (₱120.00)</option>
                                <option value="mineral_water">Bottled Water Pack (₱60.00)</option>
                                <option value="breakfast">Breakfast Buffet Voucher (₱350.00)</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="amenityQuantity" class="form-label fw-bold small text-secondary">QUANTITY</label>
                                <input type="number" class="form-control" id="amenityQuantity" min="1" max="10" value="1" required>
                            </div>
                            <div class="col-6">
                                <label for="amenitySpecialReq" class="form-label fw-bold small text-secondary">NOTES / TIME</label>
                                <input type="text" class="form-control" id="amenitySpecialReq" placeholder="e.g. ASAP / 8 PM">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-dark px-3 fw-bold" style="background-color: #6A4435; border-color: #6A4435;" id="btnSubmitAmenities">Add to Room</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
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
            <div class="modal-body p-4 text-center">
                <div class="py-4 text-secondary">
                    <img src="{{ asset('icons/bell.svg') }}" width="36" height="36" class="mb-3 opacity-50" alt="Bell">
                    <p class="mb-0 fw-semibold">No rooms due within the next hour.</p>
                    <small class="text-muted d-block mt-1">Guests nearing their scheduled check-out time will appear here automatically.</small>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Keep Existing Modals for Direct Check-In (#avail-modal) and Reservations (#res-modal) -->
@include('modals.quickresmodal')

@endsection