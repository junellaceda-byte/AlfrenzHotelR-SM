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
