<!-- Reservation Form -->
<div class="modal fade" id="res-modal" tabindex="-1" aria-labelledby="modal-title" aria-hidden="true">
    <div class="modal-dialog">
        
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title"> Create New Reservations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <!-- For the errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{route('reservation.store')}}" method="POST">
                @csrf

                <div class="modal-body">
                    
                    <div class="col-12">
                        <!-- Guest's First Name -->
                        <div class="mb-3">
                            <label for="firstName" class="form-label">Guest's First Name: <small class="text-muted">(Optional)</small></label>
                            <input type="text" class="form-control" id="fname" name="fname" placeholder="e.g Juan">
                        </div>
                        <!-- Guest's Last Name -->
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Guest's Last Name: <small class="text-muted">(Optional)</small></label>
                            <input type="text" class="form-control" id="lname" name="lname" placeholder="e.g De La Cruz">
                        </div>
                        <!-- Phone Number -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Guest's Phone Number <small class="text-muted">(Optional)</small></label>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g 09123456789">
                        </div>

                        <!-- Check-in (Calendar) -->
                        <!-- <div class="mb-3">
                            <label for="res_checkin" class="form-label">Check-in Date</label>
                            <input type="datetime-local" class="form-control" id="res_checkin" name="check_in" required>
                        </div> -->
                        
                        <!-- Check-out (Calendar) -->
                        <!-- <div class="mb-3">
                            <label for="res_checkout" class="form-label">Check-out Date</label>
                            <input type="datetime-local" class="form-control" id="res_checkout" name="check_out" required>
                        </div> -->
                         <!-- Hidden inputs for check-in and check-out dates, which will be set by JavaScript based on the arrival date and number of nights selected by the user. -->
                        <input type="hidden" id="res_checkin" name="check_in">
                        <input type="hidden" id="res_checkout" name="check_out">

                        <!-- Arrival date + nights -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="res_date" class="form-label">Arrival Date</label>
                                <input type="date" class="form-control" id="res_date" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="res_nights" class="form-label">Nights</label>
                                <input type="number" class="form-control" id="res_nights" value="1" min="1" max="30" required>
                            </div>
                            <div class="col-12">
                                <small class="text-muted" id="res_summary"></small>
                            </div>
                        </div>

                        <!-- Number of Guests -->
                        <div class="mb-3">
                            <label for="guests" class="form-label">Number of Guests</label>
                            <input type="number" class="form-control" id="guests" name="guests" value="1" min="1" max="20" required>
                        </div>
                        <div class="mb-3">
                            <label for="res_room_mode" class="form-label">How many rooms?</label>
                            <select class="form-select" id="res_room_mode">
                                <option value="one">One room</option>
                                <option value="multiple">Multiple rooms</option>
                            </select>
                        </div>
                        <!-- Room Selection (Available Rooms Only) -->
                        <div class="mb-3">
                            <label for="res_room_ids" class="form-label">Select Room(s)</label>
                            <select class="form-select" id="res_room_ids" name="room_ids[]" multiple required>
                                <option value="" disabled>Select dates first...</option>
                            </select>
                            <small class="text-muted">Hold CTRL (Windows) or CMD (Mac) to select multiple rooms.</small>
                        </div>

                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                
            </form> 
        </div>
    </div>
</div>

<!-- Direct Check-In Modal -->
<div class="modal fade" id="avail-modal" tabindex="-1" aria-labelledby="availModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="availModalLabel"> Guest Check-In</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- This is for the booking modal success -->
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <!-- This is for the booking modal errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('booking.store') }}" method="POST">
                @csrf
                
                <!-- Automatically forces action_type to check_in -->
                <input type="hidden" name="action_type" value="check_in">

                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Optional First Name -->
                        <div class="col-md-6">
                            <label for="checkin_fname" class="form-label">First Name <small class="text-muted">(Optional)</small></label>
                            <input type="text" class="form-control" id="checkin_fname" name="fname" placeholder="e.g. Juan">
                        </div>

                        <!-- Optional Last Name -->
                        <div class="col-md-6">
                            <label for="checkin_lname" class="form-label">Last Name <small class="text-muted">(Optional)</small></label>
                            <input type="text" class="form-control" id="checkin_lname" name="lname" placeholder="e.g. De La Cruz">
                        </div>

                        <!-- Stay Type -->
                        <div class="col-12">
                            <label for="checkin_stay_type" class="form-label">Stay Type</label>
                            <select class="form-select" id="checkin_stay_type" name="stay_type" required>
                                <option value="" selected disabled>Select Stay Type</option>
                                <option value="short_stay">Short Stay</option>
                                <option value="overnight">Overnight Stay</option>
                            </select>
                        </div>
                        <!-- Check-in Date -->
                        <div class="col-md-6">
                            <label for="check_in_date" class="form-label">Check-in Date</label>
                            <input type="datetime-local" class="form-control" id="check_in_date" name="check_in" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>

                        <!-- Check-out Date -->
                        <div class="col-md-6">
                            <label for="check_out_date" class="form-label">Check-out Date</label>
                            <input type="datetime-local" class="form-control" id="check_out_date" name="check_out" required>
                        </div>
                        
                        <!-- Number of Guests -->
                        <label for="checkin_guests" class="form-label">Number of Guests</label>
                        <input type="number" class="form-control" id="checkin_guests" name="guests" value="1" min="1" max="20" required>
                        
                        <div class="mb-3">
                            <label for="checkin_room_mode" class="form-label">How many rooms?</label>
                            <select class="form-select" id="checkin_room_mode">
                                <option value="one">One room</option>
                                <option value="multiple">Multiple rooms</option>
                            </select>
                        </div>
                        <!-- Room Selection (Available Rooms Only) -->
                        <div class="col-12">
                            <label for="checkin_room_ids" class="form-label">Select Room(s)</label>
                            <select class="form-select" id="checkin_room_ids" name="room_ids[]" multiple required>
                                <option value="" disabled>Select dates first...</option>
                            </select>
                            <small class="text-muted">Hold CTRL (Windows) or CMD (Mac) to select multiple rooms.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Check-In Guest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Reservation Modal                                      -->
<div class="modal fade" id="edit-res-modal" tabindex="-1" aria-labelledby="editResModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editResModalLabel">Edit Reservation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-res-form" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_res_fname" class="form-label">First Name <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="edit_res_fname" name="fname" placeholder="e.g Juan">
                    </div>
                    <div class="mb-3">
                        <label for="edit_res_lname" class="form-label">Last Name <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="edit_res_lname" name="lname" placeholder="e.g De La Cruz">
                    </div>
                    <div class="mb-3">
                        <label for="edit_res_phone" class="form-label">Phone Number <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="edit_res_phone" name="phone" placeholder="e.g 09123456789">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Booking Modal -->
<div class="modal fade" id="edit-booking-modal" tabindex="-1" aria-labelledby="editBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBookingModalLabel">Edit Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-booking-form" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_booking_fname" class="form-label">First Name <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="edit_booking_fname" name="fname" placeholder="e.g Juan">
                    </div>
                    <div class="mb-3">
                        <label for="edit_booking_lname" class="form-label">Last Name <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="edit_booking_lname" name="lname" placeholder="e.g De La Cruz">
                    </div>
                    <div class="mb-3">
                        <label for="edit_booking_phone" class="form-label">Phone Number <small class="text-muted">(Optional)</small></label>
                        <input type="text" class="form-control" id="edit_booking_phone" name="phone" placeholder="e.g 09123456789">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="delete-confirm-modal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this record? This action cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="delete-confirm-form" action="" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Unified Book Now Modal -->
<div class="modal fade" id="bookNowModal" tabindex="-1" aria-labelledby="bookNowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bookNowModalLabel">New Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bookNowForm" method="POST" action="{{ route('booking.store') }}">
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
                            <div class="col-sm-6">
                                <label class="form-label">Breakfast</label>
                                <div class="btn-group w-100" role="group" aria-label="Breakfast option">
                                    <input type="radio" class="btn-check" name="with_breakfast" id="bn_breakfast_no" value="0" autocomplete="off" checked>
                                    <label class="btn btn-outline-secondary" for="bn_breakfast_no">Without breakfast</label>

                                    <input type="radio" class="btn-check" name="with_breakfast" id="bn_breakfast_yes" value="1" autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="bn_breakfast_yes">With breakfast</label>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label for="bn_extra_beds" class="form-label">
                                    Extra Beds <span id="bn_bed_price_badge" class="badge bg-secondary fw-normal">₱500 / bed</span>
                                </label>
                                <input type="number" class="form-control" id="bn_extra_beds" name="extra_beds" value="0" min="0" max="3">
                            </div>
                        </div>
                    </div>

                    <!-- Check-in / Check-out -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="bn_check_in" class="form-label">Check-in Date</label>
                            <input type="datetime-local" class="form-control" id="bn_check_in" name="check_in" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="bn_check_out" class="form-label">Check-out Date</label>
                            <input type="datetime-local" class="form-control bg-light" id="bn_check_out" name="check_out" readonly required>
                        </div>
                    </div>

                    <!-- Room Selection (Standard Multi-Select) -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label for="bn_room_ids" class="form-label mb-0">Select Room(s)</label>
                            <select id="bn_room_mode" class="form-select form-select-sm w-auto">
                                <option value="one">One room</option>
                                <option value="multiple">Multiple rooms</option>
                            </select>
                        </div>
                        <select class="form-select" id="bn_room_ids" name="room_ids[]" multiple required>
                            <option value="" disabled>Select dates first...</option>
                        </select>
                        <small class="text-muted">Hold CTRL (Windows) or CMD (Mac) to select multiple rooms.</small>
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

