@extends('layouts.dashboard')

@section('title','Receptionist Dashboard')

@section('content')
<div class="container-fluid p-0">
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
                                <div class="rounded-circle border border-2 border-success text-success d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                                      <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                                    </svg>
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
                                <div class="rounded-circle border border-2 border-danger text-danger d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-fill" viewBox="0 0 16 16">
                                        <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                                    </svg>
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
                                <div class="rounded-circle border border-2 border-warning text-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <span class="fw-bold fs-4">!</span>
                                </div>
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
                                <div class="rounded-circle border border-2 border-secondary text-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <span class="fw-bold fs-5">⚠</span>
                                    <!-- <img class="icon-img icon-gear"
                                    src="icons/gear.svg" alt="Belle Mariano"> -->
                                </div>
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
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">NEW RESE..</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">CHECK-IN</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold">CHECK OUT</button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-outline-secondary w-100 py-2 text-start fw-semibold" style="font-size: 0.8rem;">ADD AMENITIES</button>
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
                    <!-- Image Placeholder -->
                    <div class="bg-light border-bottom d-flex align-items-center justify-content-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#ced4da" viewBox="0 0 16 16">
                            <path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.59 1.5-1.5 1.5H5.5C4.59 2 4 1.18 4 .5a.5.5 0 0 1 .146-.354zm8.019 12.385l-1.397-2.128a1.5 1.5 0 0 0-2.456-.12l-1.57 2.05-1.12-1.344a1.5 1.5 0 0 0-2.316.03l-2.035 2.54A1 1 0 0 0 2 15h12a1 1 0 0 0 .78-1.624zM2.5 14l1.83-2.288 1.166 1.4a.5.5 0 0 0 .753-.02l1.626-2.122 1.344 2.046a.5.5 0 0 0 .836.002L11.5 11l1.968 3z"/>
                        </svg>
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
                    <!-- Image Placeholder -->
                    <div class="bg-light border-bottom d-flex align-items-center justify-content-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#ced4da" viewBox="0 0 16 16">
                            <path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.59 1.5-1.5 1.5H5.5C4.59 2 4 1.18 4 .5a.5.5 0 0 1 .146-.354zm8.019 12.385l-1.397-2.128a1.5 1.5 0 0 0-2.456-.12l-1.57 2.05-1.12-1.344a1.5 1.5 0 0 0-2.316.03l-2.035 2.54A1 1 0 0 0 2 15h12a1 1 0 0 0 .78-1.624zM2.5 14l1.83-2.288 1.166 1.4a.5.5 0 0 0 .753-.02l1.626-2.122 1.344 2.046a.5.5 0 0 0 .836.002L11.5 11l1.968 3z"/>
                        </svg>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <h6 class="card-title text-secondary fw-bold mb-2">ROOM 02</h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-uppercase fw-bold text-success d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-success rounded-circle p-1 me-2 border border-light"></span> AVAILABLE
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Card 3 (Reserved - Warning) -->
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card shadow-sm bg-white overflow-hidden border">
                    <!-- Image Placeholder -->
                    <div class="bg-light border-bottom d-flex align-items-center justify-content-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#ced4da" viewBox="0 0 16 16">
                            <path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.59 1.5-1.5 1.5H5.5C4.59 2 4 1.18 4 .5a.5.5 0 0 1 .146-.354zm8.019 12.385l-1.397-2.128a1.5 1.5 0 0 0-2.456-.12l-1.57 2.05-1.12-1.344a1.5 1.5 0 0 0-2.316.03l-2.035 2.54A1 1 0 0 0 2 15h12a1 1 0 0 0 .78-1.624zM2.5 14l1.83-2.288 1.166 1.4a.5.5 0 0 0 .753-.02l1.626-2.122 1.344 2.046a.5.5 0 0 0 .836.002L11.5 11l1.968 3z"/>
                        </svg>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <h6 class="card-title text-secondary fw-bold mb-2">ROOM 03</h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-uppercase fw-bold text-warning d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-warning rounded-circle p-1 me-2 border border-light"></span> RESERVED
                            </span>
                            <span class="text-muted fw-semibold" style="font-size: 0.75rem;">14:00-12:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Card 4 (Maintenance - Secondary) -->
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card shadow-sm bg-white overflow-hidden border">
                    <!-- Image Placeholder -->
                    <div class="bg-light border-bottom d-flex align-items-center justify-content-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#ced4da" viewBox="0 0 16 16">
                            <path d="M4.146.146A.5.5 0 0 1 4.5 0h7a.5.5 0 0 1 .5.5c0 .68-.59 1.5-1.5 1.5H5.5C4.59 2 4 1.18 4 .5a.5.5 0 0 1 .146-.354zm8.019 12.385l-1.397-2.128a1.5 1.5 0 0 0-2.456-.12l-1.57 2.05-1.12-1.344a1.5 1.5 0 0 0-2.316.03l-2.035 2.54A1 1 0 0 0 2 15h12a1 1 0 0 0 .78-1.624zM2.5 14l1.83-2.288 1.166 1.4a.5.5 0 0 0 .753-.02l1.626-2.122 1.344 2.046a.5.5 0 0 0 .836.002L11.5 11l1.968 3z"/>
                        </svg>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body p-3">
                        <h6 class="card-title text-secondary fw-bold mb-2">ROOM 04</h6>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-uppercase fw-bold text-secondary d-flex align-items-center" style="font-size: 0.8rem;">
                                <span class="badge bg-secondary rounded-circle p-1 me-2 border border-light"></span> MAINTENANCE
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection