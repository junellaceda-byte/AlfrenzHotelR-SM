<link href="{{ asset('css/sidebar.css') }}" rel="stylesheet">

<!-- Sidebar -->
<aside class="app-sidebar d-flex flex-column h-100 py-3 px-2 flex-shrink-0" id="receptionistSidebarMenu" style="width: clamp(12rem, 14vw, 16rem); background-color: #6A4435;">
    
    <!-- Logo and Name -->
    <div class="text-center pt-2 pb-3 px-2">
        <!-- Logo Container with White Outline Stroke -->
        <div class="sidebar-logo-circle mx-auto d-flex align-items-center justify-content-center mb-2">
            <img src="{{ asset('images/logo.png') }}" alt="Alfrenzo's Hotel Logo" class="sidebar-logo-img">
        </div>
        
        <!-- Hotel Name -->
        <div class="sidebar-hotel-name text-uppercase fw-bold">
            ALFRENZO'S HOTEL
        </div>
    </div>

    <!-- Navigation Items -->
    <ul class="nav flex-column align-items-stretch w-100 gap-2 px-1 my-2 position-relative sidebar-nav-list" id="receptionistSidebarNavList">
        <!-- Single Sliding Active Indicator -->
        <div class="sidebar-active-indicator" id="receptionistSidebarIndicator" aria-hidden="true"></div>

        <!-- Dashboard -->
        <li class="nav-item w-100">
            <a href="{{ route('dashboard.index') }}" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request()->routeIs('dashboard.*') || request()->is('dashboard') ? 'active' : '' }}">
                OVERVIEW
            </a>
        </li>
        <!-- Reservation -->
        <li class="nav-item w-100">
            <a href="{{ route('reservation.index') }}" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request()->routeIs('reservation.*') || request()->is('reservation') ? 'active' : '' }}">
                RESERVATION
            </a>
        </li>
        <!-- Booking History -->
        <li class="nav-item w-100">
            <a href="{{ route('booking.index') }}" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request()->routeIs('booking.*') || request()->is('bookings*') ? 'active' : '' }}">
                BOOKING HISTORY
            </a>
        </li>
        <!-- Billing -->
        <li class="nav-item w-100">
            <a href="{{ route('content.billing') }}" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request()->routeIs('content.billing') || request()->is('billing*') ? 'active' : '' }}">
                BILLING
            </a>
        </li>
        <!-- Settings -->
        <li class="nav-item w-100">
            <a href="{{ route('receptionist.settings') }}" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request()->routeIs('receptionist.settings*') ? 'active' : '' }}">
                SETTINGS
            </a>
        </li>
        <!-- Logout Option -->
        <li class="nav-item w-100">
            <button type="button" class="nav-link sidebar-nav-link text-uppercase fw-bold w-100 text-start" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                LOGOUT
            </button>
        </li>
    </ul>

    
</aside>

<script src="{{ asset('js/sidebar-sliding-indicator.js') }}"></script>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow overflow-hidden">
            <div class="modal-header border-0 pb-2 text-white" style="background-color: #443627;">
                <h6 class="modal-title fw-bold text-white" id="logoutConfirmModalLabel">Confirm Logout</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4" style="background-color: #F5F7DF;">
                <p class="mb-0 fw-semibold" style="color: #443627;">Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer border-0 pt-2 pb-2 justify-content-center gap-3" style="background-color: #443627;">
                <button type="button" class="btn btn-link text-decoration-none p-0 fw-semibold" style="color: #F5F7DF;" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('logout') }}" class="btn btn-sm btn-danger px-3 fw-semibold" id="receptionistConfirmLogoutBtn">Logout</a>
            </div>
        </div>
    </div>
</div>