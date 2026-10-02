@extends('layouts.dashboard')

@section('title','Booking')

@section('receptionist-content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Search Bar -->
        <div class="input-group w-50">
            <span class="input-group-text bg-white border-end-0"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg></span>
            <input type="text" class="form-control border-start-0" placeholder="Search bookings...">
        </div>
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
                        <th class="py-3 px-4">Guest Name</th>
                        <th class="py-3 px-4">Phone</th>
                        <th class="py-3 px-4">Check-In</th>
                        <th class="py-3 px-4">Check-out</th>
                        <th class="py-3 px-4">Booked Rooms</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!--Checks if there are any bookings -->
                    @forelse($bookings as $booking)
                        <tr>
                            <td class="px-4">{{ $booking->guest->fname ?? 'Walk-in' }} {{ $booking->guest->lname ?? 'Guest' }}</td>
                            <!-- Displays the guest's phone number, or 'None' if it's empty -->
                            <td class="px-4">{{ empty($booking->phone) ? 'None' : $booking->phone }}</td>
                            <!-- Displays the check-in date — formatted by the Booking model cast -->
                            <td class="px-4">{{ $booking->check_in_datetime }}</td>
                            <!-- Displays the check-out date -->
                            <td class="px-4">{{ $booking->check_out_datetime }}</td>
                            <!-- Displays the booked room(s) — already a PHP array from the Booking model, just join them -->
                            <td class="px-4">{{ implode(', ', $booking->booked_rooms ?? []) }}</td>
                            <td class="px-4 text-center">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 text-decoration-none fw-bold" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 1.2rem;">&#8942;</button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        {{-- Edit: opens modal pre-filled with guest data --}}
                                        <li>
                                            <a class="dropdown-item"
                                               href="#"
                                               data-bs-toggle="modal"
                                               data-bs-target="#edit-booking-modal"
                                               data-id="{{ $booking->id }}"
                                               data-fname="{{ $booking->guest->fname ?? '' }}"
                                               data-lname="{{ $booking->guest->lname ?? '' }}"
                                               data-phone="{{ $booking->guest->phone ?? '' }}">
                                                Edit
                                            </a>
                                        </li>
                                        {{-- Delete: submits form after confirmation --}}
                                        <li>
                                            <a class="dropdown-item text-danger" href="#"
                                               onclick="confirmBookingDelete('delete-booking-{{ $booking->id }}')">Delete</a>
                                            <form id="delete-booking-{{ $booking->id }}"
                                                  action="{{ route('booking.destroy', $booking->id) }}"
                                                  method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No bookings found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
            <span class="text-muted small">
                @if($bookings->total() > 0)
                    Showing {{ $bookings->firstItem() }}–{{ $bookings->lastItem() }} of {{ $bookings->total() }} bookings
                @else
                    No bookings found
                @endif
            </span>
            {{ $bookings->links() }}
        </div>
    </div>
</div>

@endsection
