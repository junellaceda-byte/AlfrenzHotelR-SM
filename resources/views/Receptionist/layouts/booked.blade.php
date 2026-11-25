<!DOCTYPE html>
<html lang="en">
    <!-- Head -->
    @include('Receptionist.partials.head')
    <!-- End of Head -->
<body style="background-color: #F5F7DF;">
    <div class="container-fluid p-0" style="background-color: #F5F7DF;">
        <div class="row g-0 flex-nowrap">
            <!-- Sidebar -->
             @include('Receptionist.partials.sidebar')
            <!-- End of sidebar -->

            <!-- Main Content Area -->
            <div class="col d-flex flex-column vh-100 overflow-hidden" id="content" style="background-color: #F5F7DF;">
                <!-- Topbar -->
                @include('Receptionist.partials.topbar')
                <!-- End of Topbar -->

                <!-- Page Content -->
                <main class="p-4 flex-grow-1 overflow-auto" style="background-color: #F5F7DF;">
                    @yield('reservation-content')
                </main>
                <!-- End of Page Content -->
            </div>
            <!-- End of Main Content Area -->
        </div>
    </div>

    <!-- Scripts -->
    @include('Receptionist.partials.scripts')
    <!--End of Scripts  -->
</body>
</html>
