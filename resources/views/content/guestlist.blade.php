@extends('layouts.guest')

@section('title','Guest Records')

@section('guestlist-content')

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
                    <th>Status</th>     
                    <th>Actions</th>
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
                    <td>
                        <a href="#">Edit</a>
                        <a href="#">Delete</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>  
@endsection