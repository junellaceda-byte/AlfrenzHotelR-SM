@extends('Receptionist.layouts.dashboard')

@section('title','Reservation')

@section('receptionist-content')
<div class="container-fluid p-0" style="min-height: 100vh;">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <!-- Status filters -->
        <div class="d-flex gap-2">
            @foreach(['all' => 'All', 'today' => 'Arriving today', 'cancelled' => 'Cancelled'] as $key => $label)
                <a href="{{ route('reservation.index', ['filter' => $key]) }}"
                class="btn {{ $filter === $key ? 'btn-brown' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 reservation-table" id="dataTable" style="table-layout: fixed; width: 100%; min-width: 900px;">
                    <colgroup>
                        <col style="width: 20%;">
                        <col style="width: 17%;">
                        <col style="width: 18%;">
                        <col style="width: 10%;">
                        <col style="width: 14%;">
                        <col style="width: 10%;">
                        <col style="width: 11%;">
                    </colgroup>
                    <thead style="background-color: #6A4435; color: #ffffff;">
                        <tr style="background-color: #6A4435; color: #ffffff;">
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Guest</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Arrival</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Stay</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Rooms</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Deposit</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Status</th>
                            <th class="py-3 px-3 text-end text-white border-0" style="background-color: #6A4435; color: #ffffff;">Action</th>
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
                                $depositDisplay = 'None';
                                if ($reservation->deposit > 0) {
                                    $depositDisplay = '₱' . number_format($reservation->deposit, 2);
                                    if ($reservation->payment_method === 'ewallet') {
                                        $depositDisplay .= ' via ' . ($reservation->ewallet_type ?? 'E-Wallet') . ' (Ref: ' . ($reservation->reference_number ?? 'N/A') . ')';
                                    } else {
                                        $depositDisplay .= ' (Cash)';
                                    }
                                }
                            @endphp
                            <tr style="background-color: #F2F6D0;">
                                <td class="px-3" style="background-color: #F2F6D0;">
                                    <div class="fw-semibold">{{ $reservation->guest_name }}</div>
                                    <div class="small text-muted">{{ $reservation->phone_number }}</div>
                                </td>
                                <td class="px-3" style="background-color: #F2F6D0;">
                                    <div>{{ \App\Services\SystemFormatService::formatDateTime($arrival) }}</div>
                                </td>
                                <td class="px-3" style="background-color: #F2F6D0;">
                                    <div>{{ $stay }}</div>
                                </td>
                                <td class="px-3" style="background-color: #F2F6D0;">
                                    <div>{{ $rooms }}</div>
                                </td>
                                <td class="px-3" style="background-color: #F2F6D0;">
                                    @if($reservation->deposit > 0)
                                        <div>₱{{ number_format($reservation->deposit, 2) }}</div>
                                        @if($reservation->payment_method === 'ewallet')
                                            <div class="small text-muted">
                                                {{ $reservation->ewallet_type ?? 'E-Wallet' }} <span class="text-secondary">Ref: {{ $reservation->reference_number ?? 'N/A' }}</span>
                                            </div>
                                        @else
                                            <div class="small text-muted">Cash</div>
                                        @endif
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td class="px-3" style="background-color: #F2F6D0;">
                                    <div>
                                        @if($reservation->status === 'cancelled')
                                            <span class="text-danger fw-bold">Cancelled</span>
                                        @elseif($reservation->status === 'checked_in')
                                            <span class="text-black fw-bold">Checked in</span>
                                        @elseif($arrivesToday)
                                            <span class="text-info fw-bold">Arriving today</span>
                                        @else
                                            <span class="text-success fw-bold">Reserved</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 text-end" style="background-color: #F2F6D0;">
                                    <div class="d-inline-flex gap-1 justify-content-end text-nowrap">
                                        @if($reservation->status === 'reserved')
                                            <button type="button" class="btn btn-sm text-white btn-checkin-reservation" style="background-color: #000000;"
                                                    data-bs-toggle="modal" data-bs-target="#checkinReservationModal"
                                                    data-action="{{ route('reservation.checkIn', $reservation->id) }}"
                                                    data-name="{{ $reservation->guest_name }}"
                                                    data-rooms="{{ $rooms }}"
                                                    data-stay="{{ $stay }}"
                                                    data-deposit="{{ $depositDisplay }}">
                                                Check in
                                            </button>
                                            <form action="{{ route('reservation.cancel', $reservation->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Cancel this reservation?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr style="background-color: #F2F6D0;">
                                <td colspan="7" class="text-center py-4 text-muted" style="height: 180px; vertical-align: middle; background-color: #F2F6D0;">No reservations found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Footer with Pagination -->
        <div class="card-footer d-flex justify-content-between align-items-center py-3 flex-wrap gap-2 border-0" style="background-color: #6A4435; color: #F5F7DF;">
            <span class="small" style="color: #F5F7DF;">
                @if($reservations->total() > 0)
                    Showing {{ $reservations->firstItem() }} to {{ $reservations->lastItem() }} of {{ $reservations->total() }} reservation(s)
                @else
                    No reservations found
                @endif
            </span>
            
            <div class="table-pagination">
                {{ $reservations->links('pagination::bootstrap-5') }}
            </div>
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
                    <button type="submit" class="btn text-white" style="background-color: #000000;">Confirm check-in</button>
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

<style>
    .reservation-table {
        table-layout: fixed !important;
        width: 100% !important;
        margin-bottom: 0;
    }
    .reservation-table thead th {
        vertical-align: middle;
        white-space: nowrap;
    }
    .reservation-table tbody tr,
    .reservation-table tbody td {
        background-color: #F2F6D0 !important;
        --bs-table-bg: #F2F6D0 !important;
    }
    .reservation-table.table-hover > tbody > tr:hover > * {
        background-color: #E7EBC4 !important;
        --bs-table-hover-bg: #E7EBC4 !important;
    }
    .reservation-table tbody td {
        vertical-align: middle;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
    }
</style>
@endsection