<!DOCTYPE html>
<html lang="en">
    <!-- Head -->
    @include('partials.head')
    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- End of Head -->
    <body class="m-0 p-0 overflow-hidden" style="background-color: #f8f9fa; font-family: 'Inter', system-ui, -apple-system, sans-serif; height: 100vh;">
        <div class="container-fluid p-0 vh-100 overflow-hidden">
            <div class="row g-0 flex-nowrap h-100 w-100 m-0">
                <!-- Sidebar -->
                @include('partials.sidebar')
                <!-- End of sidebar -->

                <!-- Main Content Area -->
                <div class="col d-flex flex-column h-100 overflow-hidden" id="content" style="min-width: 0;">
                    <!-- Topbar -->
                    @include('partials.topbar')
                    <!-- End of Topbar -->

                    <!-- Page Content -->
                    <main class="p-3 p-xl-4 flex-grow-1 overflow-y-auto overflow-x-hidden" style="background-color: #f8f9fa;">
                        <!-- Success Message -->
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger m-3">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
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