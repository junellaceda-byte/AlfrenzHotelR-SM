<!-- Check-ins Modal -->
<div class="modal fade" id="checkinsModal" tabindex="-1" aria-labelledby="checkinsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="checkinsModalLabel">Check-ins Today</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Data</th>
                            </tr>
                        </thead>
                        <tbody id="checkinsModalBody">
                            @forelse($todayCheckIns ?? [] as $booking)
                                @php
                                    $guestName = trim(($booking->guest->fname ?? 'Guest') . ' ' . ($booking->guest->lname ?? ''));
                                    $rooms = is_array($booking->booked_rooms) ? $booking->booked_rooms : (json_decode($booking->booked_rooms, true) ?? []);
                                    $roomStr = !empty($rooms) ? 'Room ' . implode(', ', $rooms) : 'Room N/A';
                                    $timeStr = $booking->check_in_datetime ? \App\Services\SystemFormatService::formatTime($booking->check_in_datetime) : '';
                                @endphp
                                <tr>
                                    <td class="py-3 px-4 fw-semibold">{{ $guestName }}</td>
                                    <td class="py-3 px-4 text-muted">{{ $roomStr }}{{ $timeStr ? ' • ' . $timeStr : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-4 text-center text-muted">No check-ins recorded today.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Check-outs Modal -->
<div class="modal fade" id="checkoutsModal" tabindex="-1" aria-labelledby="checkoutsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="checkoutsModalLabel">Check-outs Today</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Data</th>
                            </tr>
                        </thead>
                        <tbody id="checkoutsModalBody">
                            @forelse($todayCheckOuts ?? [] as $booking)
                                @php
                                    $guestName = trim(($booking->guest->fname ?? 'Guest') . ' ' . ($booking->guest->lname ?? ''));
                                    $rooms = is_array($booking->booked_rooms) ? $booking->booked_rooms : (json_decode($booking->booked_rooms, true) ?? []);
                                    $roomStr = !empty($rooms) ? 'Room ' . implode(', ', $rooms) : 'Room N/A';
                                    $time = $booking->actual_check_out ?? $booking->check_out_datetime;
                                    $timeStr = $time ? \App\Services\SystemFormatService::formatTime($time) : '';
                                @endphp
                                <tr>
                                    <td class="py-3 px-4 fw-semibold">{{ $guestName }}</td>
                                    <td class="py-3 px-4 text-muted">{{ $roomStr }}{{ $timeStr ? ' • ' . $timeStr : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-4 text-center text-muted">No check-outs recorded today.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>