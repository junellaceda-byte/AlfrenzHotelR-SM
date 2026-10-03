<!DOCTYPE html>
<html lang="en">
    <!-- Head -->
    @include('partials.head')
    <!-- End of Head -->
    <body style="background-color: #eeeddf;">
        <div class="container-fluid p-0">
            <div class="row g-0 flex-nowrap">
                <!-- Sidebar -->
                @include('partials.sidebar')
                <!-- End of sidebar -->

                <!-- Main Content Area -->
                <div class="col d-flex flex-column vh-100 overflow-hidden" id="content">
                    <!-- Topbar -->
                    @include('partials.topbar')
                    <!-- End of Topbar -->

                    <!-- Page Content -->
                    <main class="p-4 flex-grow-1 overflow-auto">
                        @yield('receptionist-content')
                    </main>
                    <!-- End of Page Content -->
                </div>
                <!-- End of Main Content Area -->
            </div>
        </div>

        <!-- Scripts -->
        @include('partials.scripts')
        @stack('scripts')
        <!--End of Scripts  -->

    </body>
</html>