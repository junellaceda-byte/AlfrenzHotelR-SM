<!-- resources/views/billing.blade.php -->
@extends('Receptionist.layouts.payment')

@section('title', 'Billing Records')

@section('billing-content')

<link rel="stylesheet" href="{{ asset('css/billing.css') }}">

@php
    $maxPendingTotal = (float) ($pendingPayments->max('grand_total') ?? 0);
    $maxConfirmedTotal = (float) ($confirmedPayments->max('grand_total') ?? 0);
    $maxGrandTotal = max($maxPendingTotal, $maxConfirmedTotal);
    $sampleFormatted = '₱ ' . number_format($maxGrandTotal, 2);
    $charCount = strlen($sampleFormatted);
    // Base width for ~10 chars ("₱ 500.00" or "Total Amount") is ~115px.
    // Each additional character beyond 11 adds ~9.5px plus cell padding buffer.
    $computedAmountColWidth = max(115, (int) ceil(115 + max(0, $charCount - 11) * 9.5));
    $minTableWidth = 440 + $computedAmountColWidth;
@endphp

<!-- Added d-flex and flex-column to stretch container. Adjust the 110px based on your top navbar's height -->
<div class="container-fluid p-0 d-flex flex-column" style="min-height: calc(100vh - 110px);">
    <!-- Top Row: Pending and Confirmed Payments Tables -->
    <div class="row g-4 flex-grow-1 mb-4">
        
        <!-- Card 1: Pending Payments (Left Side) -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="card border rounded shadow-sm flex-grow-1 d-flex flex-column overflow-hidden billing-card-container" style="background-color: #F5F7DF; border-color: rgba(68, 54, 39, 0.25) !important;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center p-3 px-4" style="background-color: #443627; color: #ffffff;">
                    <div>
                        <h5 class="card-title text-white mb-1 fs-5 fw-bold" style="color: #ffffff !important;">Pending Payments</h5>
                        <small style="color: #F5F7DF; opacity: 0.9;">Payments that are due or not yet settled.</small>
                    </div>
                    <span class="badge bg-danger rounded-pill">{{ $pendingPayments->total() }} items</span>
                </div>
                
                <div class="card-body p-3 p-lg-4 d-flex flex-column flex-grow-1 billing-table-container" style="min-height: 0; background-color: #F5F7DF;">
                    <!-- Added flex-grow-1 and overflow-auto so the table scrolls internally if it exceeds screen height -->
                    <div class="table-responsive flex-grow-1 billing-table-container" style="min-height: 0; overflow-y: auto; background-color: #F5F7DF;">
                        <table class="table table-bordered align-middle billing-table billing-pending-table mb-0" style="table-layout: fixed; width: 100%; min-width: {{ $minTableWidth }}px;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 24%;">
                                <col style="width: 11%;">
                                <col class="col-total-amount" style="width: {{ $computedAmountColWidth }}px; min-width: {{ $computedAmountColWidth }}px;">
                                <col style="width: 13%;">
                                <col style="width: 16%;">
                            </colgroup>
                            <thead class="sticky-top billing-table-header-vintage-brown" style="background-color: #6A4435; --bs-table-bg: #6A4435; --bs-table-color: #ffffff; color: #ffffff;">
                                <tr>
                                    <th>Due Date</th>
                                    <th>Guest Name</th>
                                    <th>Room</th>
                                    <th class="col-total-amount text-nowrap">Total Amount</th>
                                    <th>Status</th>     
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingPayments as $invoice)
                                <tr style="background-color: #E7E6B9;">
                                    <td style="background-color: #E7E6B9;">
                                        <div class="small">{{ $invoice->booking->check_out_datetime ? \App\Services\SystemFormatService::formatDate($invoice->booking->check_out_datetime) : 'N/A' }}</div>
                                        <div class="text-muted small">{{ $invoice->booking->check_out_datetime ? \App\Services\SystemFormatService::formatTime($invoice->booking->check_out_datetime) : '' }}</div>
                                    </td>
                                    <td style="background-color: #E7E6B9;">
                                        <div>{{ $invoice->booking->guest->fname ?? 'N/A' }} {{ $invoice->booking->guest->lname ?? '' }}</div>
                                    </td>
                                    <td style="background-color: #E7E6B9;">
                                        {{-- Original:
                                            @if(is_array($invoice->booking->booked_rooms))
                                                {{ implode(', ', $invoice->booking->booked_rooms) }}
                                            @else
                                                {{ $invoice->booking->booked_rooms }}
                                            @endif
                                        --}}
                                        <div>
                                            {{ $invoice->booking?->formattedBookedRooms() ?? '' }}
                                        </div>
                                    </td>
                                    <td class="col-total-amount text-nowrap">₱ {{ number_format($invoice->grand_total, 2) }}</td>
                                    <td style="background-color: #E7E6B9;"><span class="badge {{ $invoice->status == 'unpaid' ? 'bg-warning text-dark' : 'bg-info' }}">{{ ucfirst($invoice->status) }}</span></td>
                                    <td class="text-center text-nowrap" style="background-color: #E7E6B9;">
                                        <a href="{{ route('content.billing', ['edit_id' => $invoice->id]) }}" class="btn btn-sm btn-outline-primary text-nowrap px-2 btn-pay-now">Pay Now</a>
                                    </td>
                                </tr>
                                @empty
                                <tr style="background-color: #E7E6B9;">
                                    <td colspan="6" class="text-center text-muted" style="height: 180px; vertical-align: middle; background-color: #F2F6D0;">No pending payments.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Footer background: Vintage Brown #443627 -->
                <div class="card-footer border-0 d-flex justify-content-between align-items-center p-3 px-4 mt-auto flex-wrap gap-2" style="background-color: #443627; color: #F5F7DF;">
                    <span style="font-size: 0.8rem; color: #F5F7DF;">
                        Showing {{ $pendingPayments->firstItem() ?? 0 }} to {{ $pendingPayments->lastItem() ?? 0 }} of {{ $pendingPayments->total() }} result(s)
                    </span>
                    
                    <div class="billing-pagination">
                        {{ $pendingPayments->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div> 
        
        <!-- Card 2: Confirmed Payments (Right Side) -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="card border rounded shadow-sm flex-grow-1 d-flex flex-column overflow-hidden billing-card-container" style="background-color: #F5F7DF; border-color: rgba(68, 54, 39, 0.25) !important;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center p-3 px-4" style="background-color: #443627; color: #ffffff;">
                    <div>
                        <h5 class="card-title text-white mb-1 fs-5 fw-bold" style="color: #ffffff !important;">Confirmed Payments</h5>
                        <small style="color: #F5F7DF; opacity: 0.9;">Successfully processed and settled payments.</small>
                    </div>
                    <span class="badge bg-success rounded-pill">{{ $confirmedPayments->total() }} items</span>
                </div>
                
                <div class="card-body p-3 p-lg-4 d-flex flex-column flex-grow-1 billing-table-container" style="min-height: 0; background-color: #F5F7DF;">
                    <!-- Added flex-grow-1 and overflow-auto so the table scrolls internally if it exceeds screen height -->
                    <div class="table-responsive flex-grow-1 billing-table-container" style="min-height: 0; overflow-y: auto; background-color: #F5F7DF;">
                        <table class="table table-bordered align-middle billing-table billing-confirmed-table mb-0" style="table-layout: fixed; width: 100%; min-width: {{ $minTableWidth }}px;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 24%;">
                                <col style="width: 11%;">
                                <col class="col-total-amount" style="width: {{ $computedAmountColWidth }}px; min-width: {{ $computedAmountColWidth }}px;">
                                <col style="width: 13%;">
                                <col style="width: 16%;">
                            </colgroup>
                            <thead class="sticky-top billing-table-header-vintage-brown" style="background-color: #6A4435; --bs-table-bg: #6A4435; --bs-table-color: #ffffff; color: #ffffff;">
                                <tr>
                                    <th>Date Paid</th>
                                    <th>Guest Name</th>
                                    <th>Room</th>
                                    <th class="col-total-amount text-nowrap">Total Amount</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($confirmedPayments as $invoice)
                                @php
                                    $log = $invoice->paymentLogs->sortByDesc('id')->first();
                                    $methodRaw = (!empty($log?->payment_method)) ? $log->payment_method : ($invoice->booking?->payment_method ?? 'cash');
                                    $cleanMethod = trim(strtolower((string) $methodRaw));
                                    $methodFormatted = match ($cleanMethod) {
                                        'ewallet', 'gcash', 'e-wallet', 'paymaya', 'paypal' => 'E-Wallet',
                                        'cash' => 'Cash',
                                        'credit_card' => 'Credit Card',
                                        'bank_transfer' => 'Bank Transfer',
                                        default => ucfirst(str_replace('_', ' ', (string) $methodRaw)),
                                    };
                                    $isEwallet = in_array($cleanMethod, ['ewallet', 'gcash', 'e-wallet', 'paymaya', 'paypal']);
                                    $referenceNumber = $log?->reference_number ?? $invoice->booking?->reference_number ?? '';

                                    $advanceDeposit = (float) ($invoice->advance_deposit ?? $invoice->booking?->deposit ?? 0);
                                    $remainingPayment = max(0.0, round((float) $invoice->grand_total - $advanceDeposit, 2));

                                    // Build charges JSON for the details modal
                                    $charges = [];
                                    if ($invoice->room_charges > 0) {
                                        $charges[] = ['description' => 'Room Charge', 'type' => 'room', 'amount' => $invoice->room_charges];
                                    }
                                    foreach (($invoice->booking->charges ?? collect()) as $charge) {
                                        if ($charge->type !== 'room') {
                                            $charges[] = [
                                                'description' => $charge->description . ($charge->quantity > 1 ? ' (x' . $charge->quantity . ')' : ''),
                                                'type' => $charge->type,
                                                'amount' => $charge->amount,
                                            ];
                                        }
                                    }
                                    if ($invoice->discount_amount > 0) {
                                        $charges[] = ['description' => 'Discount', 'type' => 'discount', 'amount' => -$invoice->discount_amount];
                                    }
                                    if ($advanceDeposit > 0) {
                                        $charges[] = ['description' => 'Advance Deposit', 'type' => 'deposit', 'amount' => -$advanceDeposit];
                                    }
                                @endphp
                                <tr style="background-color: #E7E6B9;">
                                    <td style="background-color: #E7E6B9;">
                                        <div class="small">{{ \App\Services\SystemFormatService::formatDate($invoice->updated_at) }}</div>
                                        <div class="text-muted small">{{ \App\Services\SystemFormatService::formatTime($invoice->updated_at) }}</div>
                                    </td>
                                    <td style="background-color: #E7E6B9;">
                                        <div>{{ $invoice->booking->guest->fname ?? 'N/A' }} {{ $invoice->booking->guest->lname ?? '' }}</div>
                                    </td>
                                    <td style="background-color: #E7E6B9;">
                                        {{-- Original:
                                            @if(is_array($invoice->booking->booked_rooms))
                                                {{ implode(', ', $invoice->booking->booked_rooms) }}
                                            @else
                                                {{ $invoice->booking->booked_rooms }}
                                            @endif
                                        --}}
                                        <div>
                                            {{ $invoice->booking?->formattedBookedRooms() ?? '' }}
                                        </div>
                                    </td>
                                    <td class="col-total-amount text-nowrap">₱ {{ number_format($invoice->grand_total, 2) }}</td>
                                    <td style="background-color: #E7E6B9;"><span class="badge bg-success">Paid</span></td>
                                    <td class="text-center text-nowrap" style="background-color: #E7E6B9;">
                                        {{-- Original data-rooms: data-rooms="@if(is_array($invoice->booking->booked_rooms)) Room {{ implode(', ', $invoice->booking->booked_rooms) }} @else Room {{ $invoice->booking->booked_rooms }} @endif" --}}
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-view-payment"
                                            data-guest="{{ trim(($invoice->booking->guest->fname ?? 'N/A') . ' ' . ($invoice->booking->guest->lname ?? '')) }}"
                                            data-rooms="Room {{ $invoice->booking?->formattedBookedRooms() ?? '' }}"
                                            data-paid-at="{{ \App\Services\SystemFormatService::formatDateTime($invoice->updated_at) }}"
                                            data-method="{{ $methodFormatted }}"
                                            data-is-ewallet="{{ $isEwallet ? '1' : '0' }}"
                                            data-reference-number="{{ $referenceNumber }}"
                                            data-grand-total="{{ number_format($invoice->grand_total, 2) }}"
                                            data-advance-deposit="{{ number_format($advanceDeposit, 2) }}"
                                            data-remaining-payment="{{ number_format($remainingPayment, 2) }}"
                                            data-charges='@json($charges)'
                                            title="View Payment Details">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr style="background-color: #F2F6D0;">
                                    <td colspan="6" class="text-center text-muted" style="height: 180px; vertical-align: middle; background-color: #F2F6D0;">No confirmed payments yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Footer background: Vintage Brown #443627 -->
                <div class="card-footer border-0 d-flex justify-content-between align-items-center p-3 px-4 mt-auto flex-wrap gap-2" style="background-color: #443627; color: #F5F7DF;">
                    <span style="font-size: 0.8rem; color: #F5F7DF;">
                        Showing {{ $confirmedPayments->firstItem() ?? 0 }} to {{ $confirmedPayments->lastItem() ?? 0 }} of {{ $confirmedPayments->total() }} result(s)
                    </span>
                    
                    <div class="billing-pagination">
                        {{ $confirmedPayments->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Inclusion & Auto-Trigger -->
@include('Receptionist.modals.quickresmodal')

@if($selectedInvoice)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Automatically open the modal when the page reloads with an edit_id
            var billingModalEl = document.getElementById('billingModal');
            if (billingModalEl) {
                var billingModal = new bootstrap.Modal(billingModalEl);
                billingModal.show();

                // Clean up edit_id from the URL so subsequent pagination or refresh does not re-open the modal
                var url = new URL(window.location.href);
                if (url.searchParams.has('edit_id')) {
                    url.searchParams.delete('edit_id');
                    window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : ''));
                }
            }
        });
    </script>
@endif

<script>
    function adjustBillingTotalAmountWidth() {
        const pendingTable = document.querySelector('.billing-pending-table');
        const confirmedTable = document.querySelector('.billing-confirmed-table');
        if (!pendingTable && !confirmedTable) return;

        let canvas = document.createElement('canvas');
        let context = canvas.getContext('2d');
        
        const sampleCell = document.querySelector('.billing-table td.col-total-amount, .billing-table th.col-total-amount, .billing-table td');
        let font = '500 14px "Inter", system-ui, -apple-system, sans-serif';
        if (sampleCell) {
            const computed = window.getComputedStyle(sampleCell);
            font = `${computed.fontWeight || '500'} ${computed.fontSize || '14px'} ${computed.fontFamily || '"Inter", sans-serif'}`;
        }
        context.font = font;

        let maxContentWidth = context.measureText('Total Amount').width;

        const amountCells = document.querySelectorAll('.billing-table td.col-total-amount');
        amountCells.forEach(function(cell) {
            const text = cell.textContent.trim();
            if (text) {
                const w = context.measureText(text).width;
                if (w > maxContentWidth) {
                    maxContentWidth = w;
                }
            }
        });

        // Generous padding buffer (left/right cell padding 16px + breathing room)
        const requiredPixelWidth = Math.ceil(maxContentWidth + 28);
        const finalAmountWidth = Math.max(115, requiredPixelWidth);

        const colTotalAmounts = document.querySelectorAll('.billing-table col.col-total-amount');
        colTotalAmounts.forEach(function(col) {
            col.style.width = finalAmountWidth + 'px';
            col.style.minWidth = finalAmountWidth + 'px';
        });

        const minTableWidth = 440 + finalAmountWidth;
        document.querySelectorAll('.billing-table').forEach(function(table) {
            table.style.minWidth = minTableWidth + 'px';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        adjustBillingTotalAmountWidth();
        window.addEventListener('resize', adjustBillingTotalAmountWidth);
    });
</script>

<style>
    .billing-table {
        table-layout: fixed !important;
        width: 100% !important;
        margin-bottom: 0;
    }
    .billing-table thead,
    .billing-table thead tr,
    .billing-table thead th {
        background-color: #6A4435 !important;
        --bs-table-bg: #6A4435 !important;
        --bs-table-color: #ffffff !important;
        color: #ffffff !important;
        vertical-align: middle;
        white-space: nowrap;
    }
    .billing-table tbody tr,
    .billing-table tbody td {
        background-color: #E7E6B9 !important;
        --bs-table-bg: #E7E6B9 !important;
    }
    .billing-table tbody td {
        vertical-align: middle;
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
    }
    .billing-table tbody td.text-nowrap,
    .billing-table .col-total-amount,
    .billing-table th.col-total-amount,
    .billing-table td.col-total-amount {
        white-space: nowrap !important;
        word-break: normal !important;
        overflow-wrap: normal !important;
    }
    .billing-table .col-total-amount {
        font-variant-numeric: tabular-nums;
    }
    .billing-pagination .small.text-muted {
        display: none !important;
    }
    .billing-pagination .pagination {
        margin-bottom: 0;
        --bs-pagination-padding-x: 0.5rem;
        --bs-pagination-padding-y: 0.25rem;
        --bs-pagination-font-size: 0.875rem;
    }
    .billing-pagination .page-item.active .page-link {
        background-color: #6A4435 !important;
        border-color: #6A4435 !important;
        color: #fff !important;
    }
    .billing-pagination .page-link {
        color: #6A4435;
    }
    .billing-pagination .page-item.disabled .page-link {
        color: #6c757d !important;
    }
</style>

@endsection