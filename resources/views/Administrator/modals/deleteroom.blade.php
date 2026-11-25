<!-- Delete Room Modal -->
<div class="modal fade" id="deleteRoomModal{{ $room->id }}" tabindex="-1" aria-labelledby="deleteRoomModalLabel{{ $room->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-0 pb-2 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="deleteRoomModalLabel{{ $room->id }}">Delete Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center" style="background-color: #F5F7DF;">
                <p class="fs-6 mb-2 fw-semibold text-dark">
                    Are you sure you want to delete Room {{ str_pad($room->room_number ?? $room->id, 2, '0', STR_PAD_LEFT) }}?
                </p>
                <p class="text-secondary small mb-0">This will permanently remove the room record from the database.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2" style="background-color: #443627;">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">No, Cancel</button>
                <form method="POST" action="{{ route('admin.rooms.destroy', $room->id) }}" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4 fw-bold">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
