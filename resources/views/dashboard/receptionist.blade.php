@extends('layouts.dashboard')

@section('title','Receptionist Dashboard')

@section('receptionist-content')

<div class="container-fluid p-0">
    <!-- Main Content -->
    <!-- Top Section: Status Cards & Quick Actions -->
    <div class="row mb-4 g-4">
        <!-- Status Cards Column -->
        <div class="col-xl-8">
            <div class="row g-3 h-100">
                <!-- Available -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-success shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3">Available</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rounded-circle border border-2 border-success text-success d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                    <img src="{{ asset('icons/check.svg') }}" alt="Available" width="45" height="45">
                                </div>
                                <span class="fs-1 fw-bold text-success">8</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Occupied -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-danger shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3">Occupied</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="rounded-circle border border-2 border-danger text-danger d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                                    <img src="{{ asset('icons/person.svg') }}" alt="Available" width="45" height="45">
                                </div>
                                <span class="fs-1 fw-bold text-danger">7</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Reserved -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-warning shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3">Reserved</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                    <img src="{{ asset('icons/bookmark.svg') }}" alt="Reserved" width="40" height="40">
                                <span class="fs-1 fw-bold text-warning">3</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Under Maintenance -->
                <div class="col-sm-6 col-md-3">
                    <div class="card h-100 border-secondary shadow-sm">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <h6 class="card-title text-uppercase text-secondary fw-bold mb-3" style="font-size: 0.8rem;">Under Maintenance</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                    <img src="{{ asset('icons/cone.svg') }}" alt="Under Maintenance" width="50" height="50">
                                <span class="fs-1 fw-bold text-secondary">1</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Column -->
        <div class="col-xl-4">
            <div class="card h-100 bg-white border shadow-sm">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-secondary fw-bold mb-3">QUICK ACTIONS</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold" data-bs-toggle="modal" data-bs-target="#res-modal">NEW RESERVATION</button>
                            
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">CHECK-IN</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">CHECK OUT</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">ADD AMENITIES</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Section: Two Large Panels -->
    <div class="row mb-5 g-4">
        <div class="col-md-6">
            <div class="card shadow-sm border" style="min-height: 250px;">
                <div class="card-body d-flex align-items-center justify-content-center bg-white rounded">
                    <p class="text-muted text-uppercase fw-bold m-0">Main Data Panel</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border" style="min-height: 250px;">
                <div class="card-body d-flex align-items-center justify-content-center bg-white rounded">
                    <p class="text-muted text-uppercase fw-bold m-0">Secondary Data Panel</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Room Status Grid -->
    <div>
        <div class="d-flex align-items-center mb-3">
            <h5 class="text-secondary fw-bold text-uppercase m-0">Room Status Grid</h5>
        </div>
        
        <div class="row g-4">
            
            <!-- Room Card 1 (Occupied - Danger) -->
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card shadow-sm bg-white overflow-hidden border">
                    <!-- Room Image -->
                    <div class="border-bottom" style="height: 140px;">
                        <img src="{{ asset('images/room.png') }}" alt="Room Image" class="w-100 h-100 object-fit-cover">
                    </div>
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <h6 class="card-title text-secondary fw-bold mb-2">ROOM 01</h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-uppercase fw-bold text-danger d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-danger rounded-circle p-1 me-2 border border-light"></span> OCCUPIED
                            </span>
                            <span class="text-muted fw-semibold" style="font-size: 0.75rem;">00:00-00:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Card 2 (Available - Success) -->
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card shadow-sm bg-white overflow-hidden border">
                    <!-- Room Image -->
                    <div class="border-bottom" style="height: 140px;">
                        <img src="{{ asset('images/room.png') }}" alt="Room Image" class="w-100 h-100 object-fit-cover">
                    </div>
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <h6 class="card-title text-secondary fw-bold mb-2">ROOM 02</h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-uppercase fw-bold text-success d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-success rounded-circle p-1 me-2 border border-light"></span> AVAILABLE
                            </span>
                            <span class="text-muted fw-semibold" style="font-size: 0.75rem;">04:00-10:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Card 3 (Reserved - Warning) -->
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card shadow-sm bg-white overflow-hidden border">
                    <!-- Room Image -->
                    <div class="border-bottom" style="height: 140px;">
                        <img src="{{ asset('images/room.png') }}" alt="Room Image" class="w-100 h-100 object-fit-cover">
                    </div>
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <h6 class="card-title text-secondary fw-bold mb-2">ROOM 03</h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-uppercase fw-bold text-warning d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-warning rounded-circle p-1 me-2 border border-light"></span> RESERVED
                            </span>
                            <span class="text-muted fw-semibold" style="font-size: 0.75rem;">06:00-14:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Card 4 (Maintenance - Danger) -->
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card shadow-sm bg-white overflow-hidden border">
                    <!-- Room Image -->
                    <div class="border-bottom" style="height: 140px;">
                        <img src="{{ asset('images/room.png') }}" alt="Room Image" class="w-100 h-100 object-fit-cover">
                    </div>
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <h6 class="card-title text-secondary fw-bold mb-2">ROOM 04</h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-uppercase fw-bold text-danger d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-danger rounded-circle p-1 me-2 border border-light"></span> MAINTENANCE
                            </span>
                            <span class="text-muted fw-semibold" style="font-size: 0.75rem;">09:00-18:00</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    
</div>
@endsection