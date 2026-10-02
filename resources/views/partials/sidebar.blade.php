 <!-- Sidebar -->
            <div class="col-auto col-md-3 col-xl-2 px-sm-3 px-0 bg-white border-end vh-100 d-flex flex-column py-4">
                <div class="mt-2 text-center mb-4">
                    <!-- Logo Placeholder -->
                    <div class="align-items-center justify-content-center text-secondary fw-bold" style="width: 225px; height: 225px;">
                       <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-100 h-100 object-fit-cover">
                    </div>
                </div>
                <!-- Navigation Items Sidebar -->
                <ul class="nav flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100 gap-2" >
                    <li class="nav-item w-100">
                        <a href="{{ route('dashboard.index') }}" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">DASHBOARD</span>
                        </a>
                    </li>

                    <li class="nav-item w-100">
                        <a href="/reservation" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">RESERVATION</span>
                        </a>
                    </li>

                    <li class="nav-item w-100">
                        <a href="{{ route('booking.index') }}" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">BOOKING</span>
                        </a>
                    </li>

                    <li class="nav-item w-100">
                        <a href="{{ route('content.billing') }}" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">BILLING</span>
                        </a>
                    </li>

                    <li class="nav-item w-100">
                        <a href="#" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">SETTINGS</span>
                        </a>
                    </li>
                </ul>
                <!-- Rather than Logout Button, It will place in the settings, the settings will be dropdown -->
                <!-- Logout Button 
                <hr class="w-100 border-secondary opacity-25">
                <div class="w-100">
                    <a href="/signupform" class="nav-link px-3 py-2 text-danger fw-bold text-uppercase">
                        <span class="d-none d-sm-inline">Logout</span>
                    </a>
                </div>
                End of Logout -->
            </div>