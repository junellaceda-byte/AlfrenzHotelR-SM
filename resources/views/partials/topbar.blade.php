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
        <!-- Book Button: Opens Book Now Modal -->
        <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.03em;" data-bs-toggle="modal" data-bs-target="#bookNowModal">
            BOOK NOW
        </button>

        <!-- Add Amenities Button: Opens Amenities Modal -->
        <button type="button" class="btn btn-dark fw-semibold px-3 py-2 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.03em; background-color: #6A4435; border-color: #6A4435;" data-bs-toggle="modal" data-bs-target="#amenitiesModal">
            Add Amenities
        </button>
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

</header>
<!-- End of Topbar -->