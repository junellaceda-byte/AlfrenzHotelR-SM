<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Guest Processing Dashboard')</title>
    <!-- Include local Bootstrap CSS -->
    <link href="{{ asset('bootstrap-5.3.8-dist/css/bootstrap.min.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid p-0">
        <div class="row g-0 flex-nowrap">
            <!-- Sidebar -->
            <div class="col-auto col-md-3 col-xl-2 px-sm-3 px-0 bg-white border-end vh-100 d-flex flex-column py-4">
                <div class="mt-2 text-center mb-4">
                    <!-- Logo Placeholder -->
                    <div class="rounded-circle bg-light border d-inline-flex align-items-center justify-content-center text-secondary fw-bold" style="width: 80px; height: 80px;">
                        LOGO
                    </div>
                </div>
                <ul class="nav flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100 gap-2">
                    <li class="nav-item w-100">
                        <a href="#" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">Book Reservation</span>
                        </a>
                    </li>
                    <li class="nav-item w-100">
                        <a href="#" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">Guests</span>
                        </a>
                    </li>
                    <li class="nav-item w-100">
                        <a href="#" class="nav-link align-middle px-3 py-2 text-secondary fw-semibold text-uppercase">
                            <span class="ms-1 d-none d-sm-inline">Settings</span>
                        </a>
                    </li>
                </ul>
                <!-- Logout Button -->
                <hr class="w-100 border-secondary opacity-25">
                <div class="w-100">
                    <a href="#" class="nav-link px-3 py-2 text-danger fw-bold text-uppercase">
                        <span class="d-none d-sm-inline">Logout</span>
                    </a>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col d-flex flex-column vh-100 overflow-hidden">
                <!-- Topbar -->
                <header class="bg-white border-bottom p-3 shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <!--Welcoming Message -->
                        <div class="border border-secondary rounded px-3 py-2 text-secondary fw-semibold bg-light d-inline-block">
                            Good day, Name!
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4 pe-3">
                        <!-- Bell Icon -->
                        <a href="#" class="text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16">
                              <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
                            </svg>
                        </a>
                        <!-- Exit/Logout Icon
                        <a href="#" class="text-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                              <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                              <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                            </svg> -->
                        </a>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="p-4 flex-grow-1 overflow-auto">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>

    <!-- Include local Bootstrap JS -->
    <script src="{{ asset('bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
