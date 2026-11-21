<!-- Topbar -->
<header class="bg-white border-bottom shadow-sm px-4 py-3 d-flex justify-content-between align-items-center">
    <div>
        <!-- Welcoming Greeting -->
        <h4 class="mb-0 fw-bold text-dark" style="font-size: clamp(1.1rem, 1.3vw, 1.45rem); letter-spacing: -0.01em;">
            <!-- Good day, {{ Auth::check() ? Auth::user()->name : 'Name' }}! -->
             Good day, Name!
        </h4>
    </div>
    
    <!-- Header Action Buttons -->
    <div class="d-flex align-items-center gap-2 gap-sm-3">
        <!-- Date and Time (live, Manila time) -->
        <div class="border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary small">
            <span id="current-date" data-format="{{ $dateFormat ?? 'M d, Y' }}">{{ \App\Services\SystemFormatService::formatDate(now()) }}</span>
        </div>
        <div class="border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary small">
            <span id="current-time" data-format="{{ $timeFormat ?? 'h:i A' }}">{{ \App\Services\SystemFormatService::formatTime(now()) }}</span>
        </div>
        
        <!-- Notifications bell -->
        <!--Optional if our System Adviser prefers -->
        <div class="dropdown">
            <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 22rem;">
                <li class="dropdown-header">Due within 30 minutes</li>
                @forelse($dueGuests as $guest)
                    <li class="dropdown-item-text small border-bottom py-2">
                        <span class="fw-semibold">RM {{ $guest->room_number }}</span> - {{ $guest->name }}<br>
                        @if($guest->is_overdue)
                            <span class="text-danger">Overdue since {{ $guest->checkout_time }}</span>
                        @else
                            Due at {{ $guest->checkout_time }} ({{ $guest->minutes }} min left)
                        @endif
                    </li>
                @empty
                    <li class="dropdown-item-text small text-muted">No rooms due right now.</li>
                @endforelse
            </ul>
        </div>

        <!-- Book Button: Opens Book Now Modal -->
        <button type="button" class="btn btn-book-now fw-semibold px-3 py-2 text-uppercase text-white" style="font-size: 0.85rem; letter-spacing: 0.03em; background-color: #7b78da; border-color: #7b78da;" data-bs-toggle="modal" data-bs-target="#bookNowModal">
            BOOK NOW
        </button>

        <!-- Add Amenities Button: Opens Amenities Modal -->
        <button type="button" class="btn btn-dark fw-semibold px-3 py-2 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.03em; background-color: #6A4435; border-color: #6A4435;" data-bs-toggle="modal" data-bs-target="#amenitiesModal">
            Add Amenities
        </button>
    </div>

</header>

<!-- Modal 2: Add Amenities Modal -->
<div class="modal fade" id="amenitiesModal" tabindex="-1" aria-labelledby="amenitiesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="amenitiesModalLabel">Add Amenities to Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    @if($occupiedRoomsList->isEmpty())
                        <div class="alert alert-secondary py-3 mb-0 text-center small">
                            There are currently no occupied rooms.
                        </div>
                    @else
                        <!-- Room Selection & Guest Details -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="amenityRoomSelect" class="form-label fw-bold small text-secondary">SELECT OCCUPIED ROOM</label>
                                <select class="form-select" id="amenityRoomSelect">
                                    <option value="" disabled selected>-- Choose an occupied room --</option>
                                    @foreach($occupiedRoomsList as $occRoom)
                                        <option value="{{ $occRoom->id }}"
                                                data-room-id="{{ $occRoom->id }}"
                                                data-room-number="{{ str_pad($occRoom->room_number ?? $occRoom->id, 2, '0', STR_PAD_LEFT) }}"
                                                data-guest-name="{{ $occRoom->guest_name ?? 'Guest' }}"
                                                data-booking-id="{{ $occRoom->booking_id ?? '' }}">
                                            Room {{ str_pad($occRoom->room_number ?? $occRoom->id, 2, '0', STR_PAD_LEFT) }} - {{ $occRoom->guest_name ?? 'Occupied' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 bg-light rounded border h-100 d-flex flex-column justify-content-center" id="guestDetailsPlaceholder">
                                    <h6 class="fw-bold text-dark small text-uppercase mb-1" style="font-size: 0.75rem;">Current Guest Details</h6>
                                    <div class="small text-secondary d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                        <span><strong>Guest:</strong> <span id="placeholderGuestName">Juan Dela Cruz</span></span>
                                        <span><strong>Room:</strong> <span id="placeholderRoomNumber">Room --</span></span>
                                        <span class="badge bg-danger">Occupied</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities Form -->
                        <form id="amenitiesForm" method="POST" action="{{ route('booking.addAmenityToRoom') }}">
                            @csrf
                            <input type="hidden" name="room_id" id="amenitiesFormRoomId">
                            <input type="hidden" name="booking_id" id="amenitiesFormBookingId">
                            <input type="hidden" name="amenities_json" id="amenitiesJsonInput">
                            <div id="amenitiesHiddenInputsContainer"></div>

                            <div class="row g-3 mb-3">
                                <!-- Left Column: CHOOSE AMENITY & Input Controls -->
                                <div class="col-md-6 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="mb-3">
                                            <label for="amenityItemSelect" class="form-label fw-bold small text-secondary">CHOOSE AMENITY</label>
                                            <select class="form-select" id="amenityItemSelect" name="amenity_id">
                                                <option value="" disabled selected>-- Choose an amenity --</option>
                                                @php
                                                    $modalAmenities = isset($availableAmenities)
                                                        ? collect($availableAmenities)->filter(fn($a) => (bool) ($a->offered ?? $a->is_available ?? false))
                                                        : \App\Models\Amenity::where('offered', true)->get();
                                                @endphp
                                                @forelse($modalAmenities as $amenityItem)
                                                    <option value="{{ $amenityItem->id }}" data-price="{{ $amenityItem->price }}" data-name="{{ $amenityItem->name }}" data-charged="{{ $amenityItem->charged ?? 'item' }}">
                                                        {{ $amenityItem->name }} (₱{{ number_format($amenityItem->price, 2) }} per {{ $amenityItem->charged ?? 'item' }})
                                                    </option>
                                                @empty
                                                    <option value="" disabled>No amenities available</option>
                                                @endforelse
                                            </select>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label for="amenityQuantity" class="form-label fw-bold small text-secondary">QUANTITY (beds or hours)</label>
                                                <input type="number" class="form-control" id="amenityQuantity" name="quantity" min="1" max="20" value="1">
                                            </div>
                                            <div class="col-6">
                                                <label for="amenitySpecialReq" class="form-label fw-bold small text-secondary">NOTES / TIME</label>
                                                <input type="text" class="form-control" id="amenitySpecialReq" name="notes" placeholder="e.g. ASAP / 8 PM">
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <button type="button" class="btn btn-outline-secondary w-100 fw-semibold text-dark" id="btnAddAmenityToList" style="font-size: 0.85rem;">
                                                + Add to Amenities List
                                            </button>
                                        </div>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        Selecting an amenity automatically adds it to the list. You can select multiple amenities in this session before confirming.
                                    </div>
                                </div>

                                <!-- Right Column: Amenities List Beside CHOOSE AMENITY -->
                                <div class="col-md-6 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold small text-secondary mb-0">SELECTED AMENITIES LIST</label>
                                        <span class="badge bg-secondary rounded-pill" id="selectedAmenitiesBadge">0 items</span>
                                    </div>

                                    <div class="border rounded bg-light p-2 flex-grow-1 d-flex flex-column" style="min-height: 200px; max-height: 250px;">
                                        <div class="flex-grow-1 overflow-auto pe-1" id="selectedAmenitiesScrollArea" style="max-height: 155px;">
                                            <!-- Empty state notice -->
                                            <div id="emptyAmenitiesNotice" class="text-center text-muted py-4 small">
                                                <div class="fw-semibold text-secondary mb-1">No amenities selected yet</div>
                                                <span class="text-secondary small">Choose an amenity from the dropdown to add it here.</span>
                                            </div>

                                            <!-- Selected amenities list container -->
                                            <div class="list-group list-group-flush gap-1" id="selectedAmenitiesList"></div>
                                        </div>

                                        <!-- Total Charges Summary -->
                                        <div class="pt-2 mt-auto border-top d-flex justify-content-between align-items-center bg-light">
                                            <span class="small fw-bold text-secondary">TOTAL CHARGES:</span>
                                            <span class="fw-bold text-success fs-6" id="selectedAmenitiesTotal">₱0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-dark px-3 fw-bold" style="background-color: #6A4435; border-color: #6A4435;" id="btnSubmitAmenities">Add to Room</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
</div>

<!-- Unified Book Now Modal -->
<div class="modal fade" id="bookNowModal" tabindex="-1" aria-labelledby="bookNowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookNowModalLabel">New Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bookNowForm" method="POST" action="{{ route('booking.store') }}"
                data-prices='@json(config('hotel.prices'))'
                data-discounts='@json(config('hotel.discounts'))'
                data-route-booking="{{ route('booking.store') }}"
                data-route-reservation="{{ route('reservation.store') }}"
                data-route-available="{{ route('api.available-rooms') }}">
                @csrf
                <input type="hidden" name="action_type" id="bn_action_type" value="check_in">
                <input type="hidden" name="guests" id="bn_guests" value="1">

                <div class="modal-body">
                    <!-- Guest Details -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-5">
                            <label for="bn_fname" class="form-label">Guest Name or Alias <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bn_fname" name="fname" placeholder="e.g. Juan" required minlength="2" maxlength="30">
                        </div>
                        <div class="col-sm-4">
                            <label for="bn_lname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="bn_lname" name="lname" placeholder="e.g. De La Cruz" maxlength="30">
                        </div>
                        <div class="col-sm-3">
                            <label for="bn_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bn_phone" name="phone" placeholder="09xxxxxxxxx" required maxlength="15">
                        </div>
                    </div>

                    <!-- Booking Mode -->
                    <div class="mb-3">
                        <label class="form-label">Booking Mode</label>
                        <div class="btn-group w-100" role="group" aria-label="Booking mode">
                            <input type="radio" class="btn-check" name="mode" id="bn_mode_now" value="now" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="bn_mode_now">Book Now / Today</label>

                            <input type="radio" class="btn-check" name="mode" id="bn_mode_reserve" value="reserve" autocomplete="off">
                            <label class="btn btn-outline-secondary" for="bn_mode_reserve">Reserve</label>
                        </div>
                    </div>

                    <!-- Stay Type -->
                    <div class="mb-3">
                        <label class="form-label">Stay Type</label>
                        <div class="btn-group w-100" role="group" aria-label="Stay type">
                            <input type="radio" class="btn-check" name="stay_type" id="bn_stay_short" value="short_stay" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="bn_stay_short">Short Stay</label>

                            <input type="radio" class="btn-check" name="stay_type" id="bn_stay_overnight" value="overnight" autocomplete="off">
                            <label class="btn btn-outline-secondary" for="bn_stay_overnight">24 Hours Stay</label>
                        </div>
                    </div>

                    <!-- Short Stay: Duration -->
                    <div id="bn_short_hours_wrap" class="mb-3">
                        <label class="form-label">Duration</label>
                        <div class="btn-group w-100" role="group" aria-label="Short stay hours">
                            <input type="radio" class="btn-check" name="short_hours" id="bn_hours_3" value="3" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="bn_hours_3">3 Hours</label>

                            <input type="radio" class="btn-check" name="short_hours" id="bn_hours_6" value="6" autocomplete="off">
                            <label class="btn btn-outline-secondary" for="bn_hours_6">6 Hours</label>

                            <input type="radio" class="btn-check" name="short_hours" id="bn_hours_12" value="12" autocomplete="off">
                            <label class="btn btn-outline-secondary" for="bn_hours_12">12 Hours</label>
                        </div>
                    </div>

                    <!-- Overnight: Breakfast + Extra Beds -->
                    <div id="bn_overnight_wrap" class="mb-3" style="display:none;">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">Breakfast</label>
                                <div class="btn-group w-100" role="group" aria-label="Breakfast option">
                                    <input type="radio" class="btn-check" name="with_breakfast" id="bn_breakfast_no" value="0" autocomplete="off" checked>
                                    <label class="btn btn-outline-secondary" for="bn_breakfast_no">Without breakfast</label>

                                    <input type="radio" class="btn-check" name="with_breakfast" id="bn_breakfast_yes" value="1" autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="bn_breakfast_yes">With breakfast</label>
                                </div>
                            </div>
                           {{-- Extra beds moved to the Add Amenities modal; hidden so the price script keeps working --}}
                            <input type="hidden" id="bn_extra_beds" name="extra_beds" value="0">
                            <span id="bn_bed_price_badge" class="d-none"></span>
                        </div>
                    </div>

                    <!-- Check-in (Book Now) or Arrival (Reserve) -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6" id="bn_checkin_col">
                            <label for="bn_check_in" class="form-label" id="bn_checkin_label">Check-in Date</label>
                            <input type="datetime-local" class="form-control" id="bn_check_in" name="check_in" required>
                            <div class="form-text" id="bn_checkin_hint">Today only. You can change the time.</div>
                        </div>
                        <div class="col-sm-6" id="bn_checkout_wrap">
                            <label for="bn_check_out" class="form-label">Check-out Date</label>
                            <input type="datetime-local" class="form-control bg-light" id="bn_check_out" name="check_out" readonly required>
                        </div>
                    </div>

                    <!-- Room Selection (Direct Selection) -->
                    <div class="mb-3">
                        <label class="form-label mb-2">Select Room(s)</label>
                        <div id="bn_room_buttons" class="d-flex flex-wrap gap-2"></div>
                        <select class="d-none" id="bn_room_ids" name="room_ids[]" multiple></select>
                    </div>

                    <!-- Discount (mode "now" only) -->
                    <div id="bn_discount_wrap" class="mb-3">
                        <label class="form-label">Discount</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" id="bn_disc_none" value="" checked>
                                <label class="form-check-label" for="bn_disc_none">None</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" id="bn_disc_senior" value="senior">
                                <label class="form-check-label" for="bn_disc_senior">Senior Citizen (20%)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" id="bn_disc_pwd" value="pwd">
                                <label class="form-check-label" for="bn_disc_pwd">PWD (20%)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Deposit (mode "reserve" only) -->
                    <div id="bn_deposit_wrap" class="mb-3" style="display:none;">
                        <label for="bn_deposit" class="form-label">Advance Deposit (optional)</label>
                        <input type="number" class="form-control" id="bn_deposit" name="deposit" min="0" step="0.01" placeholder="0.00">
                    </div>

                    <!-- Payment Method Option -->
                    <div id="bn_payment_method_wrap" class="mb-3">
                        <label class="form-label fw-semibold" id="bn_payment_method_label">Payment Method</label>
                        <div class="btn-group w-100 mb-2" role="group" aria-label="Payment method">
                            <input type="radio" class="btn-check" name="payment_method" id="bn_pay_cash" value="cash" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary" for="bn_pay_cash">Cash</label>

                            <input type="radio" class="btn-check" name="payment_method" id="bn_pay_ewallet" value="ewallet" autocomplete="off">
                            <label class="btn btn-outline-secondary" for="bn_pay_ewallet">E-Wallet</label>
                        </div>

                        <!-- E-Wallet Details (shown when E-Wallet is selected) -->
                        <div id="bn_ewallet_fields" class="p-3 bg-light rounded border mt-2" style="display: none;">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label for="bn_ewallet_type" class="form-label small fw-semibold">E-Wallet Provider <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="bn_ewallet_type" name="ewallet_type">
                                        <option value="GCash">GCash</option>
                                        <option value="PayMaya">PayMaya</option>
                                        <option value="PayPal">PayPal</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="bn_reference_number" class="form-label small fw-semibold">Reference Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="bn_reference_number" name="reference_number" placeholder="e.g. 100234891234" maxlength="50">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="card bg-light border p-3 mb-2">
                        <h6 class="fw-bold text-uppercase small mb-2">Price Summary</h6>
                        <table class="w-100 small mb-0">
                            <tr>
                                <td class="text-secondary py-1">Subtotal</td>
                                <td class="text-end fw-semibold py-1" id="bn_preview_subtotal">₱0.00</td>
                            </tr>
                            <tr id="bn_row_discount">
                                <td class="text-secondary py-1">Discount</td>
                                <td class="text-end fw-semibold py-1 text-success" id="bn_preview_discount">- ₱0.00</td>
                            </tr>
                            <tr id="bn_row_deposit" style="display:none;">
                                <td class="text-secondary py-1">Deposit</td>
                                <td class="text-end fw-semibold py-1" id="bn_preview_deposit">₱0.00</td>
                            </tr>
                            <tr id="bn_row_balance" style="display:none;">
                                <td class="text-secondary py-1">Balance</td>
                                <td class="text-end fw-semibold py-1" id="bn_preview_balance">₱0.00</td>
                            </tr>
                            <tr class="border-top">
                                <td class="py-2 fw-bold">Estimated Total</td>
                                <td class="text-end py-2 fw-bold fs-6" id="bn_preview_total">₱0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bn_submit_btn">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End of Topbar -->