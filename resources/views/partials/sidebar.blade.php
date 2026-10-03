<link href="{{ asset('css/sidebar.css') }}" rel="stylesheet">

<!-- Sidebar -->
<div class="col-auto col-md-3 col-xl-2 px-sm-3 px-0 border-end vh-100 d-flex flex-column py-4" style="background-color: #A18276;">
    
    <!-- Logo and Name -->
    <div class="mt-2 text-center mb-4">
        <!-- Logo Container (Edit width and height here to resize) -->
        <div class="mx-auto" style="width: 210px; height: 210px;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-100 h-100 object-fit-contain">
        </div>
        
        <!-- Hotel Name -->
        <div class="text-black fw-semibold text-uppercase" style="margin-top: -30px; font-size: 26px; font-family: 'Trebuchet MS', sans-serif;">
            ALFRENZO'S HOTEL
        </div>
    </div>

    <!-- Navigation Items Sidebar -->
    <ul class="nav flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100 gap-2">
        <li class="nav-item w-100">
            <a href="{{ route('dashboard.index') }}" class="nav-link align-middle px-3 py-2 fw-semibold text-uppercase sidebar-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
                <span class="ms-1 d-none d-sm-inline">DASHBOARD</span>
            </a>
        </li>

        <li class="nav-item w-100">
            <a href="/reservation" class="nav-link align-middle px-3 py-2 fw-semibold text-uppercase sidebar-link {{ request()->is('reservation') ? 'active' : '' }}">
                <span class="ms-1 d-none d-sm-inline">RESERVATION</span>
            </a>
        </li>

        <li class="nav-item w-100">
            <a href="{{ route('booking.index') }}" class="nav-link align-middle px-3 py-2 fw-semibold text-uppercase sidebar-link {{ request()->routeIs('booking.index') ? 'active' : '' }}">
                <span class="ms-1 d-none d-sm-inline">BOOKING</span>
            </a>
        </li>

        <li class="nav-item w-100">
            <a href="{{ route('content.billing') }}" class="nav-link align-middle px-3 py-2 fw-semibold text-uppercase sidebar-link {{ request()->routeIs('content.billing') ? 'active' : '' }}">
                <span class="ms-1 d-none d-sm-inline">BILLING</span>
            </a>
        </li>

        <li class="nav-item w-100">
            <a href="#" class="nav-link align-middle px-3 py-2 fw-semibold text-uppercase sidebar-link">
                <span class="ms-1 d-none d-sm-inline">SETTINGS</span>
            </a>
        </li>
    </ul>
</div>