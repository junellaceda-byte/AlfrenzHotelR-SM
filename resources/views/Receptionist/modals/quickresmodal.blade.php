@php
/**
 * @var \App\Models\Invoice $selectedInvoice
 * @var \Illuminate\Database\Eloquent\Collection|\App\Models\AvailableAmenity[] $availableAmenities
 * @var \Illuminate\Database\Eloquent\Collection|\App\Models\RoomBlock[] $activeBlocks
 */
@endphp

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

                        <!-- Hidden inputs for check-in and check-out dates -->
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
                        <!-- Room Selection (Available Rooms Only) -->
                        <div class="mb-3">
                            <label for="res_room_ids" class="form-label">Select Room(s)</label>
                            <select class="form-select" id="res_room_ids" name="room_ids[]" multiple required>
                                <option value="" disabled>Select dates first...</option>
                            </select>
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
                        
                        <!-- Room Selection (Available Rooms Only) -->
                        <div class="col-12">
                            <label for="checkin_room_ids" class="form-label">Select Room(s)</label>
                            <select class="form-select" id="checkin_room_ids" name="room_ids[]" multiple required>
                                <option value="" disabled>Select dates first...</option>
                            </select>
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

<!-- Edit Reservation Modal -->
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

<!-- Block Room (maintenance) -->
<div class="modal fade" id="blockRoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Block Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="blockRoomForm" method="POST" action="{{ route('room-block.store') }}"
                  data-url="{{ route('api.available-rooms') }}" novalidate>
                @csrf
                <div class="modal-body">
                    <p class="small text-muted mb-3">Choose the day, date and time the room is under maintenance. The free rooms for that time will show below.</p>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="bl_start" class="form-label">From (date and time)</label>
                            <input type="datetime-local" class="form-control" id="bl_start" name="start_date"
                                   min="{{ now()->setTimezone('Asia/Manila')->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-sm-6">
                            <label for="bl_end" class="form-label">Until (date and time)</label>
                            <input type="datetime-local" class="form-control" id="bl_end" name="end_date">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0">Room(s) to block</label>
                            <small class="text-muted" id="bl_room_hint">Choose the dates first.</small>
                        </div>
                        <div id="bl_room_buttons" class="d-flex flex-wrap gap-2"></div>
                        <div id="bl_room_inputs"></div>
                    </div>

                    <div class="mb-3">
                        <label for="bl_reason" class="form-label">Reason</label>
                        <input type="text" class="form-control" id="bl_reason" name="reason" maxlength="120" placeholder="e.g. Aircon repair">
                    </div>

                    <div id="bl_message" class="alert alert-danger py-2 small d-none" role="alert"></div>

                    <h6 class="fw-bold mt-4">Current blocks</h6>
                    <div style="max-height: 12rem; overflow-y: auto;">
                        @forelse($activeBlocks ?? [] as $block)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                                <div>
                                    <span class="fw-semibold">Room {{ str_pad($block->room_id, 2, '0', STR_PAD_LEFT) }}</span> - {{ $block->reason }}<br>
                                    <span class="text-muted">{{ \App\Services\SystemFormatService::formatDateTime($block->start_date) }} &rarr; {{ \App\Services\SystemFormatService::formatDateTime($block->end_date) }}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-remove-block"
                                        data-action="{{ route('room-block.destroy', $block->id) }}">Remove</button>
                            </div>
                        @empty
                            <div class="text-muted small py-2">No rooms are blocked.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-brown" id="bl_submit">Block room</button>
                </div>
            </form>
        </div>
    </div>
</div>  

<!-- resources/views/modals/quickresmodal.blade.php -->
<div class="modal fade" id="billingModal" tabindex="-1" aria-labelledby="billingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @isset($selectedInvoice)
                <div class="modal-header text-white" style="background-color: #443627;">
                    <h5 class="modal-title" id="billingModalLabel">
                        Billing Process - {{ $selectedInvoice->booking->guest->fname ?? 'Unknown' }} {{ $selectedInvoice->booking->guest->lname ?? '' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-lg-4" style="background-color: #EFEABB; color: #000000;">
                    <small class="mb-4 d-block fw-semibold" style="color: #000000;">Create a new bill, add charges, and process payment.</small>
                    
                    <div class="row g-4">
                        <!-- 1. Guest & Room Information -->
                        <div class="col-md-4">
                            <div class="card h-100 border rounded shadow-sm" style="background-color: #E7E6B9; border-color: rgba(68, 54, 39, 0.2) !important; color: #000000;">
                                <div class="card-body" style="color: #000000;">
                                    <h6 class="card-subtitle fw-bold mb-1" style="color: #000000;"><span class="badge bg-secondary rounded-circle me-2">1</span>Guest & Room Information</h6>
                                    <small class="d-block mb-4 ms-4" style="color: #000000;">Selected room and guest details.</small>
                                    
                                    <div class="mb-3 px-2">
                                        <label class="form-label small fw-bold mb-0" style="color: #000000;">Room Number</label>
                                        <div style="color: #000000;">
                                            @if(is_array($selectedInvoice->booking->booked_rooms))
                                                {{ implode(', ', $selectedInvoice->booking->booked_rooms) }}
                                            @else
                                                {{ $selectedInvoice->booking->booked_rooms }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mb-3 px-2">
                                        <label class="form-label small fw-bold mb-0" style="color: #000000;">Guest Name</label>
                                        <div style="color: #000000;">{{ $selectedInvoice->booking->guest->fname ?? 'Unknown' }} {{ $selectedInvoice->booking->guest->lname ?? '' }}</div>
                                    </div>
                                    <div class="mb-3 px-2">
                                        <label class="form-label small fw-bold mb-0" style="color: #000000;">Contact Number</label>
                                        <div style="color: #000000;">{{ $selectedInvoice->booking->guest->phone ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-3 px-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-0" style="color: #000000;">Check-in Date</label>
                                            <div style="color: #000000;">{{ $selectedInvoice->booking->check_in_datetime ? \App\Services\SystemFormatService::formatDate($selectedInvoice->booking->check_in_datetime) : 'N/A' }}</div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold mb-0" style="color: #000000;">Check-out Date</label>
                                            <div style="color: #000000;">{{ $selectedInvoice->booking->check_out_datetime ? \App\Services\SystemFormatService::formatDate($selectedInvoice->booking->check_out_datetime) : 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="mb-3 px-2">
                                        <label class="form-label small fw-bold mb-0" style="color: #000000;">Stay Type</label>
                                        <div style="color: #000000;">
                                            @if($selectedInvoice->booking)
                                                {{ $selectedInvoice->booking->stay_type_display }}
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Charges & Add-ons -->
                        <div class="col-md-4">
                            <div class="card h-100 border rounded shadow-sm" style="background-color: #E7E6B9; border-color: rgba(68, 54, 39, 0.2) !important; color: #000000;">
                                <div class="card-body" style="color: #000000;">
                                    <h6 class="card-subtitle fw-bold mb-1" style="color: #000000;"><span class="badge bg-secondary rounded-circle me-2">2</span>Charges & Add-ons</h6>
                                    <small class="d-block mb-4 ms-4" style="color: #000000;">View daily rate and add incidentals.</small>
                                    
                                    <div class="d-flex justify-content-between mb-3 px-2" style="color: #000000;">
                                        <span style="color: #000000;">Room Rate</span>
                                        <span class="fw-bold" style="color: #000000;">₱ {{ number_format($selectedInvoice->room_charges, 2) }}</span>
                                    </div>

                                    @php
                                        $advanceDeposit = (float) ($selectedInvoice->advance_deposit ?? $selectedInvoice->booking?->deposit ?? 0);
                                    @endphp
                                    @if($advanceDeposit > 0)
                                        <div class="d-flex justify-content-between mb-3 px-2" id="billing_advance_deposit_entry" style="color: #000000;">
                                            <span style="color: #000000;">Advance Deposit</span>
                                            <span class="fw-bold" style="color: #000000;">- ₱ {{ number_format($advanceDeposit, 2) }}</span>
                                        </div>
                                    @endif
                                    
                                    <!-- Display Added Amenities & Extra Charges -->
                                    @php
                                        $nonRoomCharges = ($selectedInvoice->booking && $selectedInvoice->booking->relationLoaded('charges'))
                                            ? $selectedInvoice->booking->charges->where('type', '!=', 'room')
                                            : collect();
                                        $displayItems = $selectedInvoice->amenities && $selectedInvoice->amenities->count() > 0
                                            ? $selectedInvoice->amenities->map(fn($a) => ['name' => $a->item_name, 'qty' => $a->quantity, 'amount' => $a->total_amount])
                                            : $nonRoomCharges->map(fn($c) => ['name' => $c->description, 'qty' => $c->quantity, 'amount' => $c->amount]);
                                    @endphp
                                    @if($displayItems->count() > 0)
                                        <div class="mt-3 mb-2 fw-bold small px-2" style="color: #000000;">Added Charges & Incidentals</div>
                                        <div class="px-2" style="color: #000000;">
                                        @foreach($displayItems as $item)
                                            <div class="d-flex justify-content-between mb-2 small" style="color: #000000;">
                                                <span style="color: #000000;">{{ $item['name'] }} (x{{ $item['qty'] }})</span>
                                                <span style="color: #000000;">₱ {{ number_format($item['amount'], 2) }}</span>
                                            </div>
                                        @endforeach
                                        </div>
                                    @endif
                                    
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold mb-4 px-2" style="color: #000000;">
                                        <span style="color: #000000;">Subtotal</span>
                                        <span style="color: #000000;">₱ {{ number_format($selectedInvoice->room_charges + $selectedInvoice->extra_charges_total, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Payment & Summary -->
                        <div class="col-md-4">
                            <div class="card h-100 border rounded shadow-sm" style="background-color: #E7E6B9; border-color: rgba(68, 54, 39, 0.2) !important; color: #000000;">
                                <div class="card-body d-flex flex-column" style="color: #000000;">
                                    <h6 class="card-subtitle fw-bold mb-1" style="color: #000000;"><span class="badge bg-secondary rounded-circle me-2">3</span>Payment & Summary</h6>
                                    <small class="d-block mb-4 ms-4" style="color: #000000;">Select payment method and finalize.</small>
                                    
                                    @php
                                        $advanceDeposit = (float) ($selectedInvoice->advance_deposit ?? $selectedInvoice->booking?->deposit ?? 0);
                                        $totalCharges = (float) $selectedInvoice->grand_total;
                                        $remainingBalance = max(0.0, round($totalCharges - $advanceDeposit, 2));
                                    @endphp
                                    <div class="mb-4 p-3 rounded billing-summary-box" style="background-color: #EFF5CD; border: 1px solid rgba(68, 54, 39, 0.15); color: #000000;">
                                        <div class="fw-bold small mb-3" style="color: #000000;">Summary</div>
                                        <div class="d-flex justify-content-between mb-2 small" style="color: #000000;">
                                            <span style="color: #000000;">Room Charge</span>
                                            <span style="color: #000000;">₱ {{ number_format($selectedInvoice->room_charges, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small" style="color: #000000;">
                                            <span style="color: #000000;">Incidentals</span>
                                            <span style="color: #000000;">₱ {{ number_format($selectedInvoice->extra_charges_total, 2) }}</span>
                                        </div>
                                        @if($selectedInvoice->discount_amount > 0)
                                            <div class="d-flex justify-content-between mb-2 small" style="color: #000000;">
                                                <span style="color: #000000;">Discount</span>
                                                <span style="color: #000000;">- ₱ {{ number_format($selectedInvoice->discount_amount, 2) }}</span>
                                            </div>
                                        @endif
                                        @if($advanceDeposit > 0)
                                            <div class="d-flex justify-content-between mb-2 small" style="color: #000000;">
                                                <span style="color: #000000;">Total Amount</span>
                                                <span style="color: #000000;">₱ {{ number_format($totalCharges, 2) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 small" style="color: #000000;">
                                                <span style="color: #000000;">Advance Deposit</span>
                                                <span class="fw-semibold" style="color: #000000;">- ₱ {{ number_format($advanceDeposit, 2) }}</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between fw-bold fs-6 mt-3" style="color: #000000;">
                                                <span style="color: #000000;">Remaining Balance Due</span>
                                                <span class="fw-bold" style="color: #000000;">₱ {{ number_format($remainingBalance, 2) }}</span>
                                            </div>
                                        @else
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between fw-bold fs-6 mt-3" style="color: #000000;">
                                                <span style="color: #000000;">Total Amount</span>
                                                <span style="color: #000000;">₱ {{ number_format($selectedInvoice->grand_total, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <form action="{{ route('billing.processPayment', $selectedInvoice->id) }}" method="POST" class="mt-auto">
                                        @csrf
                                        <div class="mb-4">
                                            <div class="fw-bold small mb-2" style="color: #000000;">Payment Method</div>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" autocomplete="off" checked>
                                                <label class="btn btn-outline-secondary" for="pay_cash">Cash</label>

                                                <input type="radio" class="btn-check" name="payment_method" id="pay_ewallet" value="ewallet" autocomplete="off">
                                                <label class="btn btn-outline-secondary" for="pay_ewallet">E-Wallet</label>
                                            </div>
                                        </div>

                                        <div class="mb-3 d-none" id="billing_ewallet_ref_wrap">
                                            <label for="billing_reference_number" class="form-label fw-bold small mb-1" style="color: #000000;">Reference Number</label>
                                            <input type="text" class="form-control form-control-sm" id="billing_reference_number" name="reference_number" placeholder="e.g. GCash/Maya transaction ID" style="color: #000000;">
                                        </div>
                                        
                                        <button type="submit" class="btn text-white w-100 py-2 fw-semibold" style="background-color: #6A4435; border-color: #6A4435;">Process Payment</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <style>
                    #billingModal .modal-body,
                    #billingModal .modal-body *:not(.btn):not(.btn *):not(.badge) {
                        color: #000000 !important;
                    }
                    #billingModal .btn-check:checked + .btn-outline-secondary {
                        background-color: #6A4435 !important;
                        border-color: #6A4435 !important;
                        color: #ffffff !important;
                    }
                </style>
            @else
                <div class="modal-header text-white" style="background-color: #6A4435;">
                    <h5 class="modal-title" id="billingModalLabel">Billing Process</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-lg-4 bg-light text-center" style="color: #000000;">
                    <p class="mb-0" style="color: #000000;">No invoice selected to process.</p>
                </div>
            @endisset
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #6A4435;">
                <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Guest & Room Info -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-bold fs-6" id="pd_guest_name">—</div>
                        <div class="text-muted small" id="pd_rooms">—</div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success">Paid</span>
                        <div class="text-muted small mt-1" id="pd_paid_at">—</div>
                    </div>
                </div>

                <hr>

                <!-- Itemized Breakdown -->
                <div class="fw-bold small mb-2 text-muted text-uppercase">Itemized Breakdown</div>
                <table class="table table-sm table-borderless mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="pd_charges_body">
                        <!-- populated by JS -->
                    </tbody>
                    <tfoot>
                        <tr class="border-top">
                            <th id="pd_total_label">Grand Total</th>
                            <th class="text-end" id="pd_grand_total">—</th>
                        </tr>
                        <tr id="pd_deposit_row" class="text-success d-none">
                            <th>Advance Deposit</th>
                            <th class="text-end" id="pd_advance_deposit">—</th>
                        </tr>
                        <tr class="border-top d-none" id="pd_remaining_row">
                            <th>Remaining Payment</th>
                            <th class="text-end fw-bold text-primary" id="pd_remaining_payment">—</th>
                        </tr>
                    </tfoot>
                </table>

                <hr>

                <!-- Payment Info -->
                <div class="row g-2 small">
                    <div class="col-6">
                        <div class="text-muted">Payment Method</div>
                        <div class="fw-semibold" id="pd_method">—</div>
                    </div>
                    <div class="col-6 d-none" id="pd_ref_wrap">
                        <div class="text-muted">Reference Number</div>
                        <div class="fw-semibold" id="pd_reference_number">—</div>
                    </div>
                    <div class="col-6 d-none" id="pd_deposit_info_col">
                        <div class="text-muted">Advance Deposit</div>
                        <div class="fw-semibold text-success" id="pd_deposit_info_val">—</div>
                    </div>
                    <div class="col-6 d-none" id="pd_remaining_info_col">
                        <div class="text-muted">Remaining Payment</div>
                        <div class="fw-semibold text-primary" id="pd_remaining_info_val">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>