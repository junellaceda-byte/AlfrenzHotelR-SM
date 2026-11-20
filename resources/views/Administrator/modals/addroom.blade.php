<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="addRoomModalLabel">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.rooms.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Room Number</label>
                        <input type="text" name="room_number" class="form-control" placeholder="e.g. 21" required>
                        <small class="text-muted" style="font-size: 0.75rem;">Must be unique across all hotel rooms.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Short Stay Rate (₱)</label>
                        <input type="number" step="0.01" name="short_stay_rate" class="form-control" value="600.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small mb-1">Overnight Rate (₱)</label>
                        <input type="number" step="0.01" name="overnight_rate" class="form-control" value="2200.00" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold px-3">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

