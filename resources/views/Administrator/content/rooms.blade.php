<link href="{{ asset('css/room-grid.css') }}" rel="stylesheet">
<link href="{{ asset('css/Administrator/rooms.css') }}" rel="stylesheet">

<div class="card p-3 border rounded">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Manage Rooms</h5>
        <button type="button" class="btn btn-outline-secondary" id="selectRoomsBtn">Select</button>
    </div>

    <!-- Room Grid -->
    <div class="hotel-room-grid" id="hotelRoomGrid">
        @for ($i = 1; $i <= 20; $i++)
            @php
                $roomNumberStr = str_pad($i, 2, '0', STR_PAD_LEFT);
            @endphp

            <div class="hotel-room-card" data-room-id="{{ $i }}">
                <!-- Room Image -->
                <div class="room-image-wrapper">
                    <img src="{{ asset('images/room.png') }}" alt="Room {{ $roomNumberStr }}">
                </div>

                <!-- Room Details -->
                <div class="room-card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <h6 class="room-number-heading">Room {{ $roomNumberStr }}</h6>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <!-- Footer Actions -->
    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn btn-primary" id="addRoomBtn">Add</button>
        <button type="button" class="btn btn-danger" id="removeRoomBtn">Remove</button>
    </div>
</div>