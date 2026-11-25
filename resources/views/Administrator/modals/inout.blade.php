<!-- Check-ins Modal -->
<div class="modal fade" id="checkinsModal" tabindex="-1" aria-labelledby="checkinsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-0 pb-2 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="checkinsModalLabel">Check-ins Today</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="background-color: #F5F7DF;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="border-bottom" style="background-color: #443627; color: #ffffff;">
                            <tr>
                                <th class="py-3 px-4 text-white" style="background-color: #443627; color: #ffffff;">Name</th>
                                <th class="py-3 px-4 text-white" style="background-color: #443627; color: #ffffff;">Data</th>
                            </tr>
                        </thead>
                        <tbody id="checkinsModalBody" style="background-color: #F5F7DF;">
                            @forelse($todayCheckIns ?? [] as $booking)
                                @php
                                    $guestName = trim(($booking->guest->fname ?? 'Guest') . ' ' . ($booking->guest->lname ?? ''));
                                    $rooms = is_array($booking->booked_rooms) ? $booking->booked_rooms : (json_decode($booking->booked_rooms, true) ?? []);
                                    $roomStr = !empty($rooms) ? 'Room ' . implode(', ', $rooms) : 'Room N/A';
                                    $timeStr = $booking->check_in_datetime ? \App\Services\SystemFormatService::formatTime($booking->check_in_datetime) : '';
                                @endphp
                                <tr>
                                    <td class="py-3 px-4 fw-semibold text-dark">{{ $guestName }}</td>
                                    <td class="py-3 px-4 text-secondary">{{ $roomStr }}{{ $timeStr ? ' • ' . $timeStr : '' }}</td>
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
            <div class="modal-footer border-0 pt-2 pb-2" style="background-color: #443627;">
                <button type="button" class="btn btn-link text-decoration-none p-0 fw-semibold" style="color: #F5F7DF;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Check-outs Modal -->
<div class="modal fade" id="checkoutsModal" tabindex="-1" aria-labelledby="checkoutsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-0 pb-2 text-white" style="background-color: #443627;">
                <h5 class="modal-title fw-bold text-white" id="checkoutsModalLabel">Check-outs Today</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="background-color: #F5F7DF;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="border-bottom" style="background-color: #443627; color: #ffffff;">
                            <tr>
                                <th class="py-3 px-4 text-white" style="background-color: #443627; color: #ffffff;">Name</th>
                                <th class="py-3 px-4 text-white" style="background-color: #443627; color: #ffffff;">Data</th>
                            </tr>
                        </thead>
                        <tbody id="checkoutsModalBody" style="background-color: #F5F7DF;">
                            @forelse($todayCheckOuts ?? [] as $booking)
                                @php
                                    $guestName = trim(($booking->guest->fname ?? 'Guest') . ' ' . ($booking->guest->lname ?? ''));
                                    $rooms = is_array($booking->booked_rooms) ? $booking->booked_rooms : (json_decode($booking->booked_rooms, true) ?? []);
                                    $roomStr = !empty($rooms) ? 'Room ' . implode(', ', $rooms) : 'Room N/A';
                                    $time = $booking->actual_check_out ?? $booking->check_out_datetime;
                                    $timeStr = $time ? \App\Services\SystemFormatService::formatTime($time) : '';
                                @endphp
                                <tr>
                                    <td class="py-3 px-4 fw-semibold text-dark">{{ $guestName }}</td>
                                    <td class="py-3 px-4 text-secondary">{{ $roomStr }}{{ $timeStr ? ' • ' . $timeStr : '' }}</td>
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
            <div class="modal-footer border-0 pt-2 pb-2" style="background-color: #443627;">
                <button type="button" class="btn btn-link text-decoration-none p-0 fw-semibold" style="color: #F5F7DF;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
