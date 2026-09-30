@extends('layouts.booked')

@section('title','Booking')

@section('reservation-content')

    <div class="container-fluid p-0">
      <!-- Main Content -->
        <table class="table table-bordered" id="dataTable" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest's Name</th>
                    <th>Phone</th>
                    <th>Check-In</th>
                    <th>Check-out</th>
                    <th>Room Number</th>  
                    <th>Status</th>  
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $guest)
                <tr>
                    <td>{{ $guest->id }}</td>
                    <td>{{ $guest->fname }} , {{ $guest->lname }}</td>
                    <td>{{ $guest->phone }}</td>
                    <td>{{ $guest->check_in }}</td>
                    <td>{{ $guest->check_out }}</td> 
                    <td>{{$guest->room_number}}</td>
                    <!-- <td>Status (Checked-in , Reserved, etc)</td> -->
                    <td>
                        <a href="#">Edit</a>
                        <a href="#">Delete</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>  
@endsection
