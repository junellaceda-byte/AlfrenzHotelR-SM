<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal{{ $room->id }}" tabindex="-1" aria-labelledby="editRoomModalLabel{{ $room->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="editRoomModalLabel{{ $room->id }}">Edit Room {{ str_pad($room->room_number ?? $room->id, 2, '0', STR_PAD_LEFT) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.rooms.update', $room->id) }}">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Room Number</label>
                        <input type="text" name="room_number" class="form-control" value="{{ $room->room_number }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Short Stay Rate (₱)</label>
                        <input type="number" step="0.01" name="short_stay_rate" class="form-control" value="{{ $room->short_stay_rate }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Overnight Rate (₱)</label>
                        <input type="number" step="0.01" name="overnight_rate" class="form-control" value="{{ $room->overnight_rate }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Status Override</label>
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
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-3">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

