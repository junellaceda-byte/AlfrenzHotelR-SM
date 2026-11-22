<!-- Admin Block Room (Maintenance) Modal -->
<div class="modal fade" id="adminBlockRoomModal" tabindex="-1" aria-labelledby="adminBlockRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="adminBlockRoomModalLabel">Block Room for Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="adminBlockRoomForm" method="POST" action="{{ route('room-block.store') }}" novalidate>
                @csrf
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">Choose the start and end dates/times for room maintenance.</p>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="admin_bl_start" class="form-label small fw-bold text-muted">From Date & Time</label>
                            <input type="datetime-local" class="form-control" id="admin_bl_start" name="start_date"
                                   min="{{ now()->setTimezone('Asia/Manila')->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="admin_bl_end" class="form-label small fw-bold text-muted">Until Date & Time</label>
                            <input type="datetime-local" class="form-control" id="admin_bl_end" name="end_date" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Select Room(s) to Block</label>
                        <div class="d-flex flex-wrap gap-2" style="max-height: 140px; overflow-y: auto;">
                            @foreach($rooms ?? [] as $r)
                                <div class="form-check form-check-inline m-0 p-0">
                                    <input type="checkbox" class="btn-check" name="room_ids[]" value="{{ $r->id }}" id="admin_block_room_{{ $r->id }}">
                                    <label class="btn btn-outline-secondary btn-sm px-2.5 py-1" for="admin_block_room_{{ $r->id }}">
                                        RM {{ str_pad($r->room_number ?? $r->id, 2, '0', STR_PAD_LEFT) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="admin_bl_reason" class="form-label small fw-bold text-muted">Reason</label>
                        <input type="text" class="form-control" id="admin_bl_reason" name="reason" maxlength="120" placeholder="e.g. Air conditioning repair" required>
                    </div>

                    <h6 class="fw-bold mt-4 small text-uppercase text-secondary">Active Maintenance Blocks</h6>
                    <div style="max-height: 10rem; overflow-y: auto;" class="border rounded p-2 bg-light">
                        @forelse($activeBlocks ?? [] as $block)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-1.5 small">
                                <div>
                                    <span class="fw-semibold">Room {{ str_pad($block->room->room_number ?? $block->room_id, 2, '0', STR_PAD_LEFT) }}</span> &mdash; {{ $block->reason }}<br>
                                    <span class="text-muted" style="font-size: 0.72rem;">{{ \App\Services\SystemFormatService::formatDateTime($block->start_date) }} &rarr; {{ \App\Services\SystemFormatService::formatDateTime($block->end_date) }}</span>
                                </div>
                                <form method="POST" action="{{ route('room-block.destroy', $block->id) }}" class="d-inline mb-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.72rem;">Remove</button>
                                </form>
                            </div>
                        @empty
                            <div class="text-muted small py-2 text-center">No active maintenance blocks.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-brown fw-bold px-3">Block Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

