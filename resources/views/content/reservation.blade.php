@extends('layouts.dashboard')

@section('title','Reservation')

@section('receptionist-content')
<div class="container-fluid p-0" style="min-height: 100vh;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <!-- Status filters -->
        <div class="d-flex gap-2">
            @foreach(['today' => 'Arriving today', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
                <a href="{{ route('reservation.index', ['filter' => $key]) }}"
                class="btn {{ $filter === $key ? 'btn-brown' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="dataTable" width="100%">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4">Guest</th>
                        <th class="py-3 px-4">Arrival</th>
                        <th class="py-3 px-4">Stay</th>
                        <th class="py-3 px-4">Rooms</th>
                        <th class="py-3 px-4">Deposit</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $reservation)
                        @php
                            $arrival = $reservation->reservation_start_date;
                            $arrivesToday = $arrival->toDateString() === now()->setTimezone('Asia/Manila')->toDateString();
                            $rooms = collect($reservation->selected_rooms)->map(fn($r) => str_pad($r, 2, '0', STR_PAD_LEFT))->implode(', ');
                            $stay = $reservation->stay_type === 'short_stay'
                                ? $reservation->short_stay_hours . '-hour short stay'
                                : '24 hours, ' . ($reservation->with_breakfast ? 'with' : 'without') . ' breakfast';
                        @endphp
                        <tr>
                            <td class="px-4">
                                <div class="fw-semibold">{{ $reservation->guest_name }}</div>
                                <div class="small text-muted">{{ $reservation->phone_number }}</div>
                            </td>
                            <td class="px-4">{{ $arrival->format('M d, Y h:i A') }}</td>
                            <td class="px-4">{{ $stay }}</td>
                            <td class="px-4">{{ $rooms }}</td>
                            <td class="px-4">{{ $reservation->deposit > 0 ? '₱' . number_format($reservation->deposit, 2) : 'None' }}</td>
                            <td class="px-4">
                                @if($reservation->status === 'cancelled')
                                    <span class="badge border border-danger text-danger bg-white">Cancelled</span>
                                @elseif($reservation->status === 'checked_in')
                                    <span class="badge border border-success text-success bg-white">Checked in</span>
                                @elseif($arrivesToday)
                                    <span class="badge border border-dark text-dark bg-white">Arriving today</span>
                                @else
                                    <span class="badge border border-secondary text-secondary bg-white">Reserved</span>
                                @endif
                            </td>
                            <td class="px-4 text-end">
                                @if($reservation->status === 'reserved')
                                    <button type="button" class="btn btn-sm text-white btn-checkin-reservation" style="background-color: #6A4435;"
                                            data-bs-toggle="modal" data-bs-target="#checkinReservationModal"
                                            data-action="{{ route('reservation.checkIn', $reservation->id) }}"
                                            data-name="{{ $reservation->guest_name }}"
                                            data-rooms="{{ $rooms }}"
                                            data-stay="{{ $stay }}"
                                            data-deposit="{{ $reservation->deposit > 0 ? '₱' . number_format($reservation->deposit, 2) : 'None' }}">
                                        Check in
                                    </button>
                                    <form action="{{ route('reservation.cancel', $reservation->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Cancel this reservation?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No reservations found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Footer with Pagination -->
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
            <span class="text-muted small">Showing {{ $reservations->firstItem() ?? 0 }} to {{ $reservations->lastItem() ?? 0 }} of {{ $reservations->total() }} result(s)</span>
            
            <nav aria-label="Table pagination">
                {{ $reservations->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    </div>
</div>

<!-- Check-in confirmation (the guest has arrived) -->
<div class="modal fade" id="checkinReservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Check in guest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="checkinReservationForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="mb-1"><strong>Guest:</strong> <span id="ci_name"></span></div>
                    <div class="mb-1"><strong>Rooms:</strong> <span id="ci_rooms"></span></div>
                    <div class="mb-1"><strong>Stay:</strong> <span id="ci_stay"></span></div>
                    <div class="mb-3"><strong>Deposit:</strong> <span id="ci_deposit"></span></div>

                    <label class="form-label fw-semibold">Discount</label>
                    <div class="d-flex gap-3">
                        <div class="form-check"><input class="form-check-input" type="radio" name="discount_type" id="ci_disc_none" value="" checked><label class="form-check-label" for="ci_disc_none">None</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="discount_type" id="ci_disc_senior" value="senior"><label class="form-check-label" for="ci_disc_senior">Senior Citizen (20%)</label></div>
                        <div class="form-check"><input class="form-check-input" type="radio" name="discount_type" id="ci_disc_pwd" value="pwd"><label class="form-check-label" for="ci_disc_pwd">PWD (20%)</label></div>
                    </div>
                    <div class="small text-muted mt-3">The stay is counted from the time you confirm.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn text-white" style="background-color: #6A4435;">Confirm check-in</button>
                </div>
            </form>
        </div>
    </div>
</div>

    @push('scripts')
        <script>
            // Fill the check-in window with the clicked reservation
            document.querySelectorAll('.btn-checkin-reservation').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('checkinReservationForm').action = this.dataset.action;
                    document.getElementById('ci_name').textContent = this.dataset.name;
                    document.getElementById('ci_rooms').textContent = this.dataset.rooms;
                    document.getElementById('ci_stay').textContent = this.dataset.stay;
                    document.getElementById('ci_deposit').textContent = this.dataset.deposit;
                    document.getElementById('ci_disc_none').checked = true;
                });
            });
        </script>
    @endpush
@endsection