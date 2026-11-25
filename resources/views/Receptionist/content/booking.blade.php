@extends('Receptionist.layouts.dashboard')

@section('title', 'Booking History')

@section('receptionist-content')
<div class="container-fluid p-0">
    <!-- Top Action Row: Status filters on left, Search bar & Export to Excel button on top-right corner -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <!-- Status filters -->
        <div class="d-flex gap-2 flex-wrap">
            @foreach(['all' => 'All', 'in_house' => 'In house', 'overdue' => 'Overdue', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                <a href="{{ route('booking.index', array_merge(request()->query(), ['filter' => $key, 'page' => 1])) }}"
                   class="btn {{ $filter === $key ? 'btn-brown' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
        </div>

        <!-- Search Bar and Export to Excel Button (Top-Right Corner) -->
        <div class="d-flex align-items-center gap-2 ms-auto">
            <!-- Search Bar -->
            <form method="GET" action="{{ route('booking.index') }}" class="d-flex align-items-center m-0" id="bookingSearchForm">
                @if(request('filter'))
                    <input type="hidden" name="filter" value="{{ request('filter') }}">
                @endif
                <div class="input-group input-group-sm" style="width: 250px;">
                    <button type="submit" class="input-group-text bg-white border-end-0 text-muted" title="Search" style="cursor: pointer;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </button>
                    <input type="text" name="search" id="bookingSearchInput" class="form-control border-start-0 ps-0" placeholder="Search bookings..." value="{{ request('search') }}" aria-label="Search bookings">
                    @if(request('search'))
                        <a href="{{ route('booking.index', array_filter(['filter' => request('filter')])) }}" class="btn btn-outline-secondary border-start-0 d-flex align-items-center justify-content-center" title="Clear search" style="font-size: 1rem; line-height: 1;">&times;</a>
                    @endif
                </div>
            </form>

            <!-- Export to Excel Button -->
            <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 text-nowrap fw-semibold px-2.5 py-1.5" data-bs-toggle="modal" data-bs-target="#exportBookingModal" id="openExportBookingModalBtn" title="Export Booking History to Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Export to Excel
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small booking-history-table" style="table-layout: fixed; width: 100%; min-width: 950px;">
                    <colgroup>
                        <col style="width: 9%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 8%;">
                        <col style="width: 15%;">
                        <col style="width: 15%;">
                        <col style="width: 10%;">
                        <col style="width: 9%;">
                        <col style="width: 6%;">
                    </colgroup>
                    <thead style="background-color: #6A4435; color: #ffffff;">
                        <tr style="background-color: #6A4435; color: #ffffff;">
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Booking</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Guest Name</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Last Name</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Rooms</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Check-in</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Check-out</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Total</th>
                            <th class="py-3 px-3 text-white border-0" style="background-color: #6A4435; color: #ffffff;">Status</th>
                            <th class="py-3 px-3 text-center text-white border-0" style="background-color: #6A4435; color: #ffffff;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            @php
                                $peso = fn ($n) => '₱' . number_format($n, 2);
                                $invoice = $booking->invoice;
                                $log = $invoice ? $invoice->paymentLogs->sortByDesc('id')->first() : null;
                                $rooms = collect($booking->booked_rooms)->map(fn ($r) => str_pad($r, 2, '0', STR_PAD_LEFT))->implode(', ');
                                $stay = $booking->stay_type === 'short_stay'
                                    ? 'Short stay, ' . $booking->short_stay_hours . ' hours'
                                    : '24 hours, ' . ($booking->with_breakfast ? 'with' : 'without') . ' breakfast';
                                $paid = $invoice && $invoice->status === 'paid';
                                $cancelled = $booking->status === 'cancelled';
                                $overdue = $booking->check_out_datetime->lt(now()->setTimezone('Asia/Manila'));
                                $discount = ['senior' => 'Senior Citizen (20%)', 'pwd' => 'PWD (20%)'][$booking->discount_type] ?? 'None';
                                $paidOn = $log ? \App\Services\SystemFormatService::formatDateTime($log->created_at) : null;
                                $statusText = $cancelled ? 'Cancelled'
                                    : (($paid || $booking->status === 'checked_out') ? 'Completed'
                                    : ($overdue ? 'Overdue' : 'In house'));
                                $guestName = $booking->guest?->fname ?? 'Walk-in';
                                $lastName = $booking->guest?->lname ?: '-';
                                $details = [
                                    'no' => 'BK-' . str_pad($booking->id, 4, '0', STR_PAD_LEFT),
                                    'name' => $guestName,
                                    'last' => $lastName,
                                    'phone' => $booking->phone ?: '-',
                                    'stay' => $stay,
                                    'rooms' => $rooms,
                                    'checkin' => \App\Services\SystemFormatService::formatDateTime($booking->check_in_datetime),
                                    'checkout' => \App\Services\SystemFormatService::formatDateTime($booking->check_out_datetime),
                                    'discount' => $discount,
                                    'status' => $statusText,
                                    'paid_on' => $paidOn ?? 'Not paid yet',
                                    'method' => $log ? (in_array(strtolower((string) $log->payment_method), ['ewallet', 'gcash', 'e-wallet']) ? 'E-Wallet' : ucfirst(str_replace('_', ' ', (string) $log->payment_method))) : ($booking->payment_method ? ($booking->payment_method === 'ewallet' ? ($booking->ewallet_type ?? 'E-Wallet') . ($booking->reference_number ? ' (Ref: ' . $booking->reference_number . ')' : '') : 'Cash') : '-'),
                                    'deposit' => $peso($booking->deposit ?? 0),
                                    'subtotal' => $peso(($invoice->room_charges ?? 0) + ($invoice->extra_charges_total ?? 0)),
                                    'discount_amount' => '-' . $peso($invoice->discount_amount ?? 0),
                                    'total' => $peso($invoice->grand_total ?? 0),
                                    'room_charges' => $booking->charges->where('type', 'room')->map(fn ($c) => [
                                        'description' => $c->description, 'qty' => $c->quantity, 'amount' => $peso($c->amount),
                                    ])->values()->all(),
                                    'amenities' => $booking->charges->where('type', '!=', 'room')->map(fn ($c) => [
                                        'description' => $c->description, 'qty' => $c->quantity, 'amount' => $peso($c->amount),
                                    ])->values()->all(),
                                ];
                            @endphp
                            <tr style="background-color: #F2F6D0;">
                                <td class="px-3 fw-semibold" style="background-color: #F2F6D0;">{{ $details['no'] }}</td>
                                <td class="px-3" style="background-color: #F2F6D0;">{{ $guestName }}</td>
                                <td class="px-3" style="background-color: #F2F6D0;">{{ $lastName }}</td>
                                <td class="px-3" style="background-color: #F2F6D0;">{{ $rooms }}</td>
                                <td class="px-3" style="background-color: #F2F6D0;">{{ $details['checkin'] }}</td>
                                <td class="px-3" style="background-color: #F2F6D0;">{{ $details['checkout'] }}</td>
                                <td class="px-3" style="background-color: #F2F6D0;">{{ $details['total'] }}</td>
                                <td class="px-3" style="background-color: #F2F6D0;">
                                    @if($cancelled)
                                        <span class="badge border border-danger text-danger bg-white">{{ $statusText }}</span>
                                    @elseif($statusText === 'Completed')
                                        <span class="text-black fw-bold">{{ $statusText }}</span>
                                    @elseif($overdue)
                                        <span class="badge border border-danger text-danger bg-white">Overdue</span>
                                    @else
                                        <span class="text-black fw-bold">{{ $statusText }}</span>
                                    @endif
                                </td>
                                <td class="px-3 text-center" style="background-color: #F2F6D0;">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <!-- View: eye icon -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-booking" title="View details"
                                                data-bs-toggle="modal" data-bs-target="#viewBookingModal"
                                                data-details='@json($details)'>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                                        </button>
                                        @if($statusText !== 'Completed')
                                            <!-- Add amenities: plus icon -->
                                            <button type="button" class="btn btn-sm btn-brown btn-add-amenity" title="Add amenities"
                                                    data-bs-toggle="modal" data-bs-target="#addAmenityModal"
                                                    data-action="{{ route('booking.addAmenities', $booking->id) }}"
                                                    data-guest="{{ $details['no'] }} - {{ trim($guestName . ' ' . ($lastName === '-' ? '' : $lastName)) }}"
                                                    @if($paid || $cancelled) disabled @endif>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr style="background-color: #F2F6D0;">
                                <td colspan="9" class="text-center py-4 text-muted" style="height: 180px; vertical-align: middle; background-color: #F2F6D0;">No bookings found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center py-3 flex-wrap gap-2 border-0" style="background-color: #6A4435; color: #F5F7DF;">
            <span class="small" style="color: #F5F7DF;">
                @if($bookings->total() > 0)
                    Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} booking(s)
                @else
                    No bookings found
                @endif
            </span>
            <div class="table-pagination">
                {{ $bookings->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<!-- View booking: full guest details, charges and amenities -->
<div class="modal fade" id="viewBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking <span data-field="no"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6"><div class="text-muted small">Guest name</div><div class="fw-semibold" data-field="name"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Last name</div><div class="fw-semibold" data-field="last"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Phone</div><div data-field="phone"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Status</div><div data-field="status"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Stay type</div><div data-field="stay"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Rooms</div><div data-field="rooms"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Checked in</div><div data-field="checkin"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Check-out</div><div data-field="checkout"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Discount</div><div data-field="discount"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Deposit</div><div data-field="deposit"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Paid on</div><div data-field="paid_on"></div></div>
                    <div class="col-sm-6"><div class="text-muted small">Payment method</div><div data-field="method"></div></div>
                </div>

                <h6 class="fw-bold">Room charges</h6>
                <table class="table table-sm mb-3">
                    <thead class="table-light"><tr><th>Item</th><th>Qty</th><th class="text-end">Amount</th></tr></thead>
                    <tbody id="vb_room_rows"></tbody>
                </table>

                <h6 class="fw-bold">Amenities added</h6>
                <table class="table table-sm mb-3">
                    <thead class="table-light"><tr><th>Item</th><th>Qty</th><th class="text-end">Amount</th></tr></thead>
                    <tbody id="vb_amenity_rows"></tbody>
                </table>

                <div class="d-flex justify-content-between"><span>Subtotal</span><span data-field="subtotal"></span></div>
                <div class="d-flex justify-content-between"><span>Discount</span><span data-field="discount_amount"></span></div>
                <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-2 mt-2"><span>Total</span><span data-field="total"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add amenities to a booking -->
<div class="modal fade" id="addAmenityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add amenities</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addAmenityForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><span class="text-muted small">Guest</span><div class="fw-semibold" id="aa_guest"></div></div>
                    <div class="small text-muted mb-2">Enter a quantity for each item the guest wants.</div>
                    @php
                        $activeAmenities = isset($availableAmenities)
                            ? collect($availableAmenities)->filter(fn($a) => (bool) ($a->offered ?? $a->is_available ?? false))
                            : \App\Models\Amenity::where('offered', true)->get();
                    @endphp
                    <div id="bookingAddAmenitiesList">
                        @forelse($activeAmenities as $item)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom" data-amenity-id="{{ $item->id }}">
                                <div>
                                    <div class="fw-semibold">{{ $item->name }}</div>
                                    <div class="small text-muted">₱{{ number_format($item->price, 2) }} per {{ $item->charged ?? 'item' }}</div>
                                </div>
                                <input type="number" name="qty[{{ $item->id }}]" value="0" min="0" max="20" class="form-control form-control-sm text-center" style="width: 5rem;">
                            </div>
                        @empty
                            <div class="py-3 text-center text-muted small">No amenities currently offered.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brown">Add to bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('Receptionist.modals.exportbookingmodal')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Search across all pages with debounced submission and cursor focus retention
        const searchInput = document.getElementById('bookingSearchInput');
        const searchForm = document.getElementById('bookingSearchForm');
        if (searchInput && searchForm) {
            let searchDebounceTimer = null;

            // Retain focus and cursor position if search is already active
            if (searchInput.value) {
                searchInput.focus();
                const len = searchInput.value.length;
                searchInput.setSelectionRange(len, len);
            }

            // Automatically submit after typing pause (searches all pages on server)
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function () {
                    searchForm.submit();
                }, 500);
            });

            // Prevent double submission on Enter key
            searchForm.addEventListener('submit', function () {
                clearTimeout(searchDebounceTimer);
            });
        }
        // Fills a small charges table (rows are built with textContent, so no HTML can sneak in)
        function fillRows(tbodyId, rows, emptyText) {
            var tbody = document.getElementById(tbodyId);
            tbody.innerHTML = '';
            if (!rows.length) {
                var tr = document.createElement('tr');
                var td = document.createElement('td');
                td.colSpan = 3;
                td.className = 'text-muted';
                td.textContent = emptyText;
                tr.appendChild(td);
                tbody.appendChild(tr);
                return;
            }
            rows.forEach(function (row) {
                var tr = document.createElement('tr');
                [row.description, row.qty, row.amount].forEach(function (value, i) {
                    var td = document.createElement('td');
                    td.textContent = value;
                    if (i === 2) { td.className = 'text-end'; }
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
        }

        // View: fill the details window
        document.querySelectorAll('.btn-view-booking').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var d = JSON.parse(this.dataset.details);
                document.querySelectorAll('#viewBookingModal [data-field]').forEach(function (el) {
                    el.textContent = d[el.dataset.field] !== undefined ? d[el.dataset.field] : '-';
                });
                fillRows('vb_room_rows', d.room_charges, 'No room charges.');
                var emptyAmenityText = (d.status && d.status.toLowerCase() === 'completed') ? 'None' : 'No amenities added yet';
                fillRows('vb_amenity_rows', d.amenities, emptyAmenityText);
            });
        });

        // Add: point the form at this booking and clear the quantities
        document.querySelectorAll('.btn-add-amenity').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('addAmenityForm').action = this.dataset.action;
                document.getElementById('aa_guest').textContent = this.dataset.guest;
                document.querySelectorAll('#addAmenityForm input[type=number]').forEach(function (input) { input.value = 0; });
            });
        });
    });
</script>
@endpush

<style>
    .booking-history-table {
        table-layout: fixed !important;
        width: 100% !important;
        min-width: 950px;
        margin-bottom: 0;
    }
    .booking-history-table thead th {
        vertical-align: middle;
        white-space: nowrap;
    }
    .booking-history-table tbody tr,
    .booking-history-table tbody td {
        background-color: #F2F6D0 !important;
        --bs-table-bg: #F2F6D0 !important;
    }
    .booking-history-table.table-hover > tbody > tr:hover > * {
        background-color: #E7EBC4 !important;
        --bs-table-hover-bg: #E7EBC4 !important;
    }
    .booking-history-table tbody td {
        vertical-align: middle;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
    }
</style>
@endsection