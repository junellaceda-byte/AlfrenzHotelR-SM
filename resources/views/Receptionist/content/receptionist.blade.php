@php
/**
 * @var \Illuminate\Database\Eloquent\Collection|\App\Models\Room[] $rooms
 * @var \Illuminate\Database\Eloquent\Collection|\App\Models\Booking[] $dueGuests
 * @var \App\Models\Room $room
 * @var \App\Models\Booking $dueGuest
 */
@endphp

@extends('Receptionist.layouts.dashboard')

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
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm border-0 text-white" style="background-color: #2BAB2B !important; background: #2BAB2B !important;">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2 text-white" style="color: #ffffff; opacity: 0.95;">Available</div>
                            <div class="kpi-card-number text-white fw-bold" id="kpi-available" style="color: #ffffff;">{{ $availableRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2 border-white text-white" style="background-color: rgba(255, 255, 255, 0.18); border-color: #ffffff !important;">
                            <img src="{{ asset('icons/check.svg') }}" alt="Available" width="28" height="28" style="filter: brightness(0) invert(1);">
                        </div>
                    </div>
                </div>

                <!-- Occupied -->
                <div class="col-sm-6">
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm border-0 text-white" style="background-color: #E87B2C !important; background: #E87B2C !important;">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2 text-white" style="color: #ffffff; opacity: 0.95;">Occupied</div>
                            <div class="kpi-card-number text-white fw-bold" id="kpi-occupied" style="color: #ffffff;">{{ $occupiedRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2 border-white text-white" style="background-color: rgba(255, 255, 255, 0.18); border-color: #ffffff !important;">
                            <img src="{{ asset('icons/person.svg') }}" alt="Occupied" width="28" height="28" style="filter: brightness(0) invert(1);">
                        </div>
                    </div>
                </div>

                <!-- Reserved -->
                <div class="col-sm-6">
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm border-0" style="background-color: #A8E036 !important; background: #809fe2 !important; color: #1c2a08;">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2 fw-bold" style="color: #ffffff; opacity: 0.9;">Reserved</div>
                            <div class="kpi-card-number fw-bold" id="kpi-reserved" style="color: #ffffff;">{{ $reservedRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2" style="background-color: rgba(28, 42, 8, 0.12); border-color: #1c2a08 !important;">
                            <img src="{{ asset('icons/bookmark.svg') }}" alt="Reserved" width="24" height="24" style="filter: brightness(0) saturate(100%) invert(13%) sepia(28%) saturate(1478%) hue-rotate(42deg) brightness(96%) contrast(97%);">
                        </div>
                    </div>
                </div>

                <!-- Under Maintenance -->
                <div class="col-sm-6">
                    <div class="kpi-card p-3 d-flex justify-content-between align-items-center h-100 shadow-sm border-0 text-white" style="background-color: #A34665 !important; background: #A34665 !important;">
                        <div>
                            <div class="kpi-card-title text-uppercase mb-2 text-white" style="color: #ffffff; opacity: 0.95;">Under Maintenance</div>
                            <div class="kpi-card-number text-white fw-bold" id="kpi-maintenance" style="color: #ffffff;">{{ $underMaintenanceRooms }}</div>
                        </div>
                        <div class="kpi-icon-box rounded-circle border border-2 border-white text-white" style="background-color: rgba(255, 255, 255, 0.18); border-color: #ffffff !important;">
                            <img src="{{ asset('icons/cone.svg') }}" alt="Under Maintenance" width="26" height="26" style="filter: brightness(0) invert(1);">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Panel (Right) -->
        <div class="col-12 col-xl-5 col-xxl-4">
            <div class="notifications-panel p-0 h-100 shadow-sm justify-content-between overflow-hidden" style="background-color: #F5F7DF; border: 1px solid rgba(68, 54, 39, 0.2);">
                <div>
                    <!-- Notification Header -->
                    <div class="d-flex justify-content-between align-items-center p-3" style="background-color: #443627; color: #ffffff;">
                        <h6 class="text-uppercase fw-bold text-white m-0 d-flex align-items-center gap-2" style="font-size: 0.9rem; letter-spacing: 0.04em;">
                            NOTIFICATIONS
                            <span id="notificationCountBadge" class="badge notification-count-badge {{ $dueGuests->count() > 0 ? '' : 'd-none' }}">{{ $dueGuests->count() }}</span>
                        </h6>
                        <small class="text-white-50 fst-italic" style="font-size: 0.72rem; font-style: italic;">(30 minute alert due of guests check-out time)</small>
                    </div>
                    <!-- Notification List -->
                    <div class="p-3" id="notificationList" style="background-color: #F2F6D0;">
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
                            <p class="text-muted small mb-0 py-2 fst-italic" style="font-style: italic;">No rooms due within the next 30 minutes.</p>
                        @endforelse
                    </div>
                </div>

                <div class="text-end p-2 px-3" style="background-color: #443627;">
                    <a href="#" class="see-more-link fw-bold text-decoration-none" style="color: #F5F7DF;" data-bs-toggle="modal" data-bs-target="#allDueRoomsModal">
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
            <button type="button" class="btn btn-filter" data-filter="reserved">RESERVED</button>
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
                $isIdle = ($room->status === 'available' || ($room->status !== 'under_maintenance' && $inTime === '00:00' && $outTime === '23:59'));
                $roomNumberStr = str_pad($room->room_number ?? $room->id, 2, '0', STR_PAD_LEFT);
                $isOverdue = !empty($room->is_overdue);
                $checkoutDatetime = $room->checkout_datetime ?? '';
                $reservationArrival = $room->reservation_arrival ?? '';
            @endphp
            
            <div class="hotel-room-card {{ $room->status === 'occupied' ? 'is-occupied' : '' }} {{ $isOverdue ? 'is-overdue' : '' }}" 
                 data-status="{{ $room->status === 'maintenance' ? 'under_maintenance' : $room->status }}" 
                 data-room-id="{{ $room->id }}"
                 data-checkout="{{ $checkoutDatetime }}"
                 data-reservation-arrival="{{ $reservationArrival }}">
                <!-- Room Image with Status Overlay -->
                <div class="room-image-wrapper">
                    <img src="{{ asset('images/room.png') }}" alt="Room {{ $roomNumberStr }}">
                    @if($room->status == 'occupied')
                        @if($isOverdue)
                            <div class="position-absolute top-0 start-0 w-100 h-100 overlay-overdue">OVERDUE</div>
                        @else
                            <div class="position-absolute top-0 start-0 w-100 h-100 overlay-occupied">OCCUPIED</div>
                        @endif
                    @elseif($room->status == 'reserved')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-reserved">RESERVED</div>
                    @elseif($room->status == 'maintenance' || $room->status == 'under_maintenance')
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-maintenance">UNDER MAINTENANCE</div>
                    @else
                        <div class="position-absolute top-0 start-0 w-100 h-100 overlay-available">AVAILABLE</div>
                    @endif

                    <!-- Hover Action Button: Only on Occupied, Non-Overdue Rooms -->
                    @if($room->status === 'occupied' && !$isOverdue)
                        <div class="card-hover-action">
                            <button type="button" class="btn btn-card-amenities text-uppercase btn-trigger-amenity" 
                                    data-room-id="{{ $room->id }}" 
                                    data-room-number="{{ $roomNumberStr }}"
                                    data-guest-name="{{ $room->guest_name ?? '' }}"
                                    data-booking-id="{{ $room->booking_id ?? '' }}">
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
                                <span class="room-status-badge fw-bold" style="color: #E87B2C;">Occupied</span>
                            @elseif($room->status === 'available')
                                <span class="room-status-badge fw-bold" style="color: #2BAB2B;">Available</span>
                            @elseif($room->status === 'reserved')
                                <span class="room-status-badge fw-bold" style="color: #A8E036;">Reserved</span>
                            @elseif($room->status === 'under_maintenance' || $room->status === 'maintenance')
                                <span class="room-status-badge fw-bold" style="color: #A34665;">Under Maintenance</span>
                            @else
                                <span class="room-status-badge text-secondary">{{ ucwords(str_replace('_', ' ', $room->status)) }}</span>
                            @endif
                        </div>

                        @if($room->status === 'under_maintenance' || $room->status === 'maintenance')
                            @if(!empty($room->blocking_period))
                                <div class="room-times-text text-end room-blocking-period">
                                    {{ $room->blocking_period }}
                                </div>
                            @endif
                        @elseif($room->status === 'occupied' && !$isIdle && !empty($inTime) && !empty($outTime))
                            <div class="room-times-text text-end">
                                <div><span class="text-muted">IN:</span> {{ $inTime }}</div>
                                <div><span class="text-muted">OUT:</span> {{ $outTime }}</div>
                                @if(!empty($checkoutDatetime))
                                    <div class="room-overdue-counter text-danger fw-bold mt-1 {{ $isOverdue ? '' : 'd-none' }}" 
                                         data-checkout="{{ $checkoutDatetime }}">
                                        <span class="overdue-label">Overdue:</span> <span class="overdue-timer">{{ $room->overdue_formatted ?? '00:00:00' }}</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($room->status === 'reserved' && !empty($reservationArrival))
                            <div class="room-times-text text-end room-reservation-arrival">
                                <div><span class="text-muted">Arrival:</span></div>
                                <div>{{ $reservationArrival }}</div>
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
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-0 pb-2 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="allDueRoomsModalLabel">Upcoming & Due Checkouts</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Notification List -->
            <div class="modal-body p-4" style="background-color: #F5F7DF;">
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
                        <p class="mb-0 fw-semibold fst-italic" style="font-style: italic;">No rooms due within the next 30 minutes.</p>
                        <small class="text-muted d-block mt-1">Guests nearing their check-out time appear here automatically.</small>
                    </div>
                @endforelse
            </div>
            <div class="modal-footer border-0 pt-2 pb-2" style="background-color: #443627;">
                <button type="button" class="btn btn-link text-decoration-none p-0 fw-semibold" style="color: #F5F7DF;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Modal -->
@include('Receptionist.modals.quickresmodal')

@endsection
