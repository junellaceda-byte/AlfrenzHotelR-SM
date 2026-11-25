<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal{{ $room->id }}" tabindex="-1" aria-labelledby="editRoomModalLabel{{ $room->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-0 pb-2 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="editRoomModalLabel{{ $room->id }}">Edit Room {{ str_pad($room->room_number ?? $room->id, 2, '0', STR_PAD_LEFT) }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.rooms.update', $room->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="background-color: #F5F7DF;">
                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Room Number</label>
                        <input type="text" name="room_number" class="form-control" value="{{ $room->room_number }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Short Stay Rate (₱)</label>
                        <input type="number" step="0.01" name="short_stay_rate" class="form-control" value="{{ $room->short_stay_rate }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Overnight Rate (₱)</label>
                        <input type="number" step="0.01" name="overnight_rate" class="form-control" value="{{ $room->overnight_rate }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small mb-1" style="color: #443627;">Status Override</label>
                        <select name="status" class="form-select">
                            <option value="available" {{ $room->status === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="under_maintenance" {{ in_array($room->status, ['under_maintenance', 'maintenance']) ? 'selected' : '' }}>Under Maintenance</option>
                            @if($room->status === 'occupied')
                                <option value="occupied" selected>Occupied</option>
                            @endif
                            @if($room->status === 'reserved')
                                <option value="reserved" selected>Reserved</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background-color: #443627;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold px-3" style="background-color: #F5F7DF; color: #443627;">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>
