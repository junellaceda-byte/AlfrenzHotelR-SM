<link href="{{ asset('css/sidebar.css') }}" rel="stylesheet">

<!-- Sidebar -->
<aside class="app-sidebar offcanvas-md offcanvas-start d-flex flex-column h-100 py-3 px-2 flex-shrink-0" id="sidebarMenu" tabindex="-1" aria-labelledby="sidebarMenuLabel" style="background-color: #6A4435;">
    
    <!-- Logo and Name (with mobile close button) -->
    <div class="text-center pt-2 pb-3 px-2 flex-shrink-0 position-relative">
        <!-- Close button for mobile offcanvas -->
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-2 d-md-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" id="closeSidebar" aria-label="Close"></button>

        <!-- Logo Container with White Outline Stroke -->
        <div class="sidebar-logo-circle mx-auto d-flex align-items-center justify-content-center mb-2">
            <img src="{{ asset('images/logo.png') }}" alt="Alfrenzo's Hotel Logo" class="sidebar-logo-img">
        </div>
        
        <!-- Hotel Name -->
        <div class="sidebar-hotel-name text-uppercase fw-bold" id="sidebarMenuLabel">
            ALFRENZO'S HOTEL
        </div>
    </div>

    <!-- Navigation Items -->
    <div class="flex-grow-1 overflow-y-auto overflow-x-hidden w-100" style="scrollbar-width: thin;">
        <ul class="nav flex-column align-items-stretch w-100 gap-2 px-1 my-2 position-relative sidebar-nav-list" id="adminSidebarNavList">
            <!-- Single Sliding Active Indicator -->
            <div class="sidebar-active-indicator" id="adminSidebarIndicator" aria-hidden="true"></div>

            <!-- Overview -->
            <li class="nav-item w-100">
                <a href="?section=overview" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request('section', 'overview') == 'overview' ? 'active' : '' }}">
                    OVERVIEW
                </a>
            </li>
            <!-- Sales Reports -->
            <li class="nav-item w-100">
                <a href="?section=salerep" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request('section') == 'salerep' ? 'active' : '' }}">
                    SALES REPORTS
                </a>
            </li>
            <!-- Activities Logs -->
            <li class="nav-item w-100">
                <a href="?section=activities" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request('section') == 'activities' ? 'active' : '' }}">
                    ACTIVITIES LOGS
                </a>
            </li>
            <!-- Amenities -->
            <li class="nav-item w-100">
                <a href="?section=amenity" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request('section') == 'amenity' ? 'active' : '' }}">
                    AMENITIES
                </a>
            </li>
            <!-- Room Management -->
            <li class="nav-item w-100">
                <a href="?section=rooms" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request('section') == 'rooms' ? 'active' : '' }}">
                    ROOM MANAGEMENT
                </a>
            </li>
            <!-- Account Management -->
            <li class="nav-item w-100">
                <a href="?section=accman" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request('section') == 'accman' ? 'active' : '' }}">
                    Account Management
                </a>
            </li>
            <!-- Settings -->
            <li class="nav-item w-100">
                <a href="?section=settings" class="nav-link sidebar-nav-link text-uppercase fw-bold {{ request('section') == 'settings' ? 'active' : '' }}">
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
    </div>
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
                <p class="mb-0 fw-semibold" style="color: #2b2219;">Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer border-0 pt-2 pb-2 justify-content-center gap-2" style="background-color: #443627;">
                <button type="button" class="btn btn-sm px-3 fw-semibold text-decoration-underline" style="color: #F5F7DF; background: transparent; border: none;" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('logout') }}" class="btn btn-sm btn-danger px-3 fw-semibold" id="adminConfirmLogoutBtn">Logout</a>
            </div>
        </div>
    </div>
</div>