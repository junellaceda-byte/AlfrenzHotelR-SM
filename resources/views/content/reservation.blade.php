@extends('layouts.dashboard')

@section('title','Reservation')

@section('receptionist-content')
<div class="container-fluid p-0" style="min-height: 100vh;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Search Bar and Filters -->
        <form action="{{ route('reservation.index') }}" method="GET" class="d-flex w-75 gap-2">
            <div class="input-group w-50">
                <span class="input-group-text bg-white border-end-0"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg></span>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Search guest name...">
            </div>
            <button type="submit" class="btn text-white" style="background-color: #76380e;">Search</button>
            <a href="{{ route('reservation.index') }}" class="btn btn-secondary">Clear</a>
        </form>
        <!-- Date and Time -->
        <div class="d-flex gap-2">
            <div class="border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary">
                <span id="current-date">{{ date('M d, Y') }}</span>
            </div>
            <div class="border px-3 py-2 bg-white rounded shadow-sm fw-bold text-secondary">
                <span id="current-time">{{ date('h:i A') }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Main Content -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="dataTable" width="100%">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-4">Date Created</th>
                        <th class="py-3 px-4">Guest Name</th>
                        <th class="py-3 px-4">Date to Reserve (Start)</th>
                        <th class="py-3 px-4">Date Overstaying (End)</th>
                        <th class="py-3 px-4">Available Room Selected</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $reservation)
                    <tr>
                        <td class="px-4">{{ $reservation->created_at->format('Y-m-d') }}</td>
                        <td class="px-4">{{ $reservation->guest_name }}</td>
                        <td class="px-4">{{ $reservation->reservation_start_date }}</td>
                        <td class="px-4">{{ $reservation->reservation_end_date }}</td>
                        <td class="px-4">
                            {{ is_array($reservation->selected_rooms) ? implode(', ', $reservation->selected_rooms) : $reservation->selected_rooms }}
                        </td>
                        <td class="px-4 text-center">
                            <div class="dropdown">
                                <button class="btn btn-link text-secondary p-0 text-decoration-none fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 1.2rem;">
                                    &#8942;
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <!--- Edit button: opens modal pre-filled with guest data --->
                                    <li>
                                        <a class="dropdown-item"
                                           href="#"
                                           data-bs-toggle="modal"
                                           data-bs-target="#edit-res-modal"
                                           data-id="{{ $reservation->id }}"
                                           data-fname="{{ $reservation->guest->fname ?? '' }}"
                                           data-lname="{{ $reservation->guest->lname ?? '' }}"
                                           data-phone="{{ $reservation->guest->phone ?? '' }}">
                                            Edit
                                        </a>
                                    </li>
                                    {{-- Delete button: triggers modal --}}
                                    <li>
                                        <a class="dropdown-item text-danger" href="#"
                                           data-bs-toggle="modal"
                                           data-bs-target="#delete-confirm-modal"
                                           onclick="setDeleteFormAction('{{ route('reservation.destroy', $reservation->id) }}')">Delete</a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No reservations found</td>
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

 <!-- Include the shared modals (New Reservation, Check-In, Edit Reservation, Edit Booking) -->
@include('modals.quickresmodal')

@push('scripts')
<script>
    function setDeleteFormAction(actionUrl) {
        document.getElementById('delete-confirm-form').action = actionUrl;
    }

    // Pre-fill the Edit Reservation modal with the clicked row's data
    document.querySelectorAll('[data-bs-target="#edit-res-modal"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            document.getElementById('edit-res-form').action = '/reservation/' + id;
            document.getElementById('edit_res_fname').value = this.dataset.fname;
            document.getElementById('edit_res_lname').value = this.dataset.lname;
            document.getElementById('edit_res_phone').value = this.dataset.phone;
        });
    });


</script>
@endpush
@endsection