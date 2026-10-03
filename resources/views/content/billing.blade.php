@extends('layouts.payment')

@section('title', 'Billing Records')

@section('billing-content')

<!-- Added background color and min-height so the color fills the screen -->
<div class="container-fluid p-0" style="background-color: #EFDCAB; min-height: 100vh;">
    <!-- Top Row: Pending and Confirmed Payments Tables -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Pending Payments -->
        <div class="col-md-6">
            <div class="card h-100 border-0 bg-body-tertiary rounded-0 shadow-sm">
                <div class="card-body p-4 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title text-dark mb-1 fs-5">Pending Payments</h5>
                            <small class="text-muted">Payments that are due or not yet settled.</small>
                        </div>
                        <span class="badge bg-danger rounded-pill">{{ $pendingPayments->count() }} items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Due Date</th>
                                    <th>Guest Name</th>
                                    <th>Room</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>     
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingPayments as $invoice)
                                <tr>
                                    <td>
                                        <div class="small">{{ $invoice->booking->check_out_datetime ? $invoice->booking->check_out_datetime->format('M j, Y') : 'N/A' }}</div>
                                        <div class="text-muted small">{{ $invoice->booking->check_out_datetime ? $invoice->booking->check_out_datetime->format('h:i A') : '' }}</div>
                                    </td>
                                    <td>{{ $invoice->booking->guest->fname ?? 'N/A' }} {{ $invoice->booking->guest->lname ?? '' }}</td>
                                    <td>
                                        @if(is_array($invoice->booking->booked_rooms))
                                            {{ implode(', ', $invoice->booking->booked_rooms) }}
                                        @else
                                            {{ $invoice->booking->booked_rooms }}
                                        @endif
                                    </td>
                                    <td>₱ {{ number_format($invoice->grand_total, 2) }}</td>
                                    <td><span class="badge {{ $invoice->status == 'unpaid' ? 'bg-warning text-dark' : 'bg-info' }}">{{ ucfirst($invoice->status) }}</span></td>
                                    <td>
                                        <a href="{{ route('content.billing', ['edit_id' => $invoice->id]) }}" class="btn btn-sm btn-outline-primary">Select</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No pending payments.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Compact Static Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="text-muted" style="font-size: 0.8rem;">
                            Showing {{ $pendingPayments->count() }} result(s)
                        </span>
                        
                        <nav aria-label="Table pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <span class="page-link text-muted" aria-hidden="true">&laquo;</span>
                                </li>
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link" style="background-color: #76380e; border-color: #76380e;">1</span>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link text-muted" aria-hidden="true">&raquo;</span>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div> 
        
                            
        <!-- Card 2: Confirmed Payments -->
        <div class="col-md-6">
            <div class="card h-100 border-0 bg-body-tertiary rounded-0 shadow-sm">
                <div class="card-body p-4 p-lg-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title text-dark mb-1 fs-5">Confirmed Payments</h5>
                            <small class="text-muted">Successfully processed and settled payments.</small>
                        </div>
                        <span class="badge bg-success rounded-pill">{{ $confirmedPayments->count() }} items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" width="100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Date Paid</th>
                                    <th>Guest Name</th>
                                    <th>Room</th>
                                    <th>Total Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>   
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($confirmedPayments as $invoice)
                                <tr>
                                    <td>
                                        <div class="small">{{ $invoice->updated_at->format('M j, Y') }}</div>
                                        <div class="text-muted small">{{ $invoice->updated_at->format('h:i A') }}</div>
                                    </td>
                                    <td>{{ $invoice->booking->guest->fname ?? 'N/A' }} {{ $invoice->booking->guest->lname ?? '' }}</td>
                                    <td>
                                        @if(is_array($invoice->booking->booked_rooms))
                                            {{ implode(', ', $invoice->booking->booked_rooms) }}
                                        @else
                                            {{ $invoice->booking->booked_rooms }}
                                        @endif
                                    </td>
                                    <td>₱ {{ number_format($invoice->grand_total, 2) }}</td>
                                    <td>
                                        {{ ucfirst($invoice->paymentLogs->first()->payment_method ?? 'Cash') }}
                                    </td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No confirmed payments yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Compact Static Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="text-muted" style="font-size: 0.8rem;">
                            Showing {{ $pendingPayments->count() }} result(s)
                        </span>
                        
                        <nav aria-label="Table pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <span class="page-link text-muted" aria-hidden="true">&laquo;</span>
                                </li>
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link" style="background-color: #76380e; border-color: #76380e;">1</span>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link text-muted" aria-hidden="true">&raquo;</span>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Billing Process -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 bg-body-tertiary rounded-0 shadow-sm">
                <div class="card-body p-4 p-lg-4">
                    <h5 class="card-title text-dark mb-1 fs-5">Billing Process</h5>
                    <small class="text-muted mb-4 d-block">Create a new bill, add charges, and process payment.</small>
                    
                    <!-- if selected invoice -->
                    @if($selectedInvoice)
                    <div class="row g-4">
                        <!-- 1. Guest & Room Information -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-subtitle text-dark fw-bold mb-1"><span class="badge bg-secondary rounded-circle me-2">1</span>Guest & Room Information</h6>
                                    <small class="text-muted d-block mb-4 ms-4">Selected room and guest details.</small>
                                    
                                    <div class="mb-3 px-2">
                                        <label class="form-label text-muted small fw-bold mb-0">Room Number</label>
                                        <div>
                                            <!-- loop if booked multiple rooms -->
                                            @if(is_array($selectedInvoice->booking->booked_rooms))
                                                {{ implode(', ', $selectedInvoice->booking->booked_rooms) }}
                                            @else
                                                {{ $selectedInvoice->booking->booked_rooms }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mb-3 px-2">
                                        <label class="form-label text-muted small fw-bold mb-0">Guest Name</label>
                                        <!-- This is main guest -->
                                        <div>{{ $selectedInvoice->booking->guest->fname ?? 'Unknown' }} {{ $selectedInvoice->booking->guest->lname ?? '' }}</div>
                                    </div>
                                    <div class="mb-3 px-2">
                                        <label class="form-label text-muted small fw-bold mb-0">Contact Number</label>
                                        <!-- This is main guest's contact number -->
                                        <div>{{ $selectedInvoice->booking->guest->phone ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-3 px-2">
                                        <div class="col-6">
                                            <label class="form-label text-muted small fw-bold mb-0">Check-in Date</label>
                                            <!-- This is booked room date -->
                                            <div>{{ $selectedInvoice->booking->check_in_datetime ? $selectedInvoice->booking->check_in_datetime->format('M j, Y') : 'N/A' }}</div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-muted small fw-bold mb-0">Check-out Date</label>
                                            <!-- This is booked room date -->
                                            <div>{{ $selectedInvoice->booking->check_out_datetime ? $selectedInvoice->booking->check_out_datetime->format('M j, Y') : 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="mb-3 px-2">
                                        <label class="form-label text-muted small fw-bold mb-0">Nights</label>
                                        <div>
                                            
                                            @if($selectedInvoice->booking->check_in_datetime && $selectedInvoice->booking->check_out_datetime)
                                                {{ $selectedInvoice->booking->check_in_datetime->startOfDay()->diffInDays($selectedInvoice->booking->check_out_datetime->startOfDay()) }} nights
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Charges & Add-ons -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-subtitle text-dark fw-bold mb-1"><span class="badge bg-secondary rounded-circle me-2">2</span>Charges & Add-ons</h6>
                                    <small class="text-muted d-block mb-4 ms-4">View daily rate and add incidentals.</small>
                                    
                                    <div class="d-flex justify-content-between mb-3 px-2">
                                        <span class="text-muted">Room Rate</span>
                                        <span class="fw-bold">₱ {{ number_format($selectedInvoice->room_charges, 2) }}</span>
                                    </div>
                                    
                                    <!-- Display Added Amenities -->
                                    @if($selectedInvoice->amenities && $selectedInvoice->amenities->count() > 0)
                                        <div class="mt-3 mb-2 fw-bold small px-2">Added Incidentals</div>
                                        <div class="px-2">
                                        @foreach($selectedInvoice->amenities as $amenity)
                                            <div class="d-flex justify-content-between mb-2 small">
                                                <span class="text-muted">{{ $amenity->item_name }} (x{{ $amenity->quantity }})</span>
                                                <span>₱ {{ number_format($amenity->total_amount, 2) }}</span>
                                            </div>
                                        @endforeach
                                        </div>
                                    @endif
                                    
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold mb-4 px-2">
                                        <span>Subtotal</span>
                                        <!-- This is the sum of room charges and extra charges -->
                                        <span>₱ {{ number_format($selectedInvoice->room_charges + $selectedInvoice->extra_charges_total, 2) }}</span>
                                    </div>

                                    <!-- Quick Add Incidentals -->
                                    <div class="fw-bold small mb-2 px-2">Quick Add Incidentals</div>
                                    <ul class="list-group list-group-flush mb-3">
                                        @foreach($availableAmenities as $item)
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-2 border-0 bg-transparent">
                                                @if($item->is_available)
                                                    <span class="small">{{ $item->item_name }}</span>
                                                    <div class="d-flex align-items-center">
                                                        <span class="text-muted small me-3">₱ {{ number_format($item->price, 2) }}</span>
                                                        <form action="{{ route('billing.addAmenity', $selectedInvoice->id) }}" method="POST" class="m-0">
                                                            @csrf
                                                            <input type="hidden" name="item_name" value="{{ $item->item_name }}">
                                                            <input type="hidden" name="price" value="{{ $item->price }}">
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" style="border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">+</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-decoration-line-through text-muted small">{{ $item->item_name }}</span>
                                                    <span class="text-muted small fst-italic">Out of Stock</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Payment & Summary -->
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-subtitle text-dark fw-bold mb-1"><span class="badge bg-secondary rounded-circle me-2">3</span>Payment & Summary</h6>
                                    <small class="text-muted d-block mb-4 ms-4">Select payment method and finalize.</small>
                                    
                                    <div class="mb-4 bg-light p-3 rounded">
                                        <div class="fw-bold small mb-3">Summary</div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Room Charge</span>
                                            <span>₱ {{ number_format($selectedInvoice->room_charges, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Incidentals</span>
                                            <span>₱ {{ number_format($selectedInvoice->extra_charges_total, 2) }}</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between fw-bold fs-6 mt-3">
                                            <span>Total Amount</span>
                                            <span>₱ {{ number_format($selectedInvoice->grand_total, 2) }}</span>
                                        </div>
                                    </div>

                                    <form action="{{ route('billing.processPayment', $selectedInvoice->id) }}" method="POST" class="mt-auto">
                                        @csrf
                                        <div class="mb-4">
                                            <div class="fw-bold small mb-2">Payment Method</div>
                                            <div class="btn-group w-100" role="group">
                                                <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="cash" checked>
                                                <label class="btn btn-outline-secondary" for="pay_cash">Cash</label>

                                                <input type="radio" class="btn-check" name="payment_method" id="pay_card" value="credit_card">
                                                <label class="btn btn-outline-secondary" for="pay_card">Card</label>

                                                <input type="radio" class="btn-check" name="payment_method" id="pay_ewallet" value="gcash">
                                                <label class="btn btn-outline-secondary" for="pay_ewallet">E-Wallet</label>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-dark w-100 py-2 rounded-1">Process Payment</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <div class="text-muted mb-2">
                            <i class="fs-1">📄</i>
                        </div>
                        <h6 class="text-muted">Select a pending payment from the table above to start the billing process.</h6>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>   
</div>       
@endsection