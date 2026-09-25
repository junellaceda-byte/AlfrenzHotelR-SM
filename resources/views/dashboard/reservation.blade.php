@extends('layouts.reserve')

@section('title','RESERVATION')

@section('reservation-content')

    <div class="container-fluid p-0">
      <!-- Main Content -->
        <table class="table table-bordered" id="dataTable" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Room Number</th>
                    <th>Check-In</th>
                    <th>Check-out</th>
                    <th>Nights</th>
                    <th>Status</th>     
                    <th>Actions</th>
                    <th>Amounts</th>

                </tr>
            </thead>
            <tbody>
                
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>
                        <a href="#">Edit</a>
                        <a href="#">Delete</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>  
@endsection