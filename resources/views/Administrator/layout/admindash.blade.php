<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Alfrenzo's Hotel - Admin Dashboard</title>

    <!-- Google Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap & Sidebar CSS -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sidebar.css') }}" rel="stylesheet">
    <!-- Component-Specific Border Radius Design System -->
    <link href="{{ asset('css/global-radius.css') }}" rel="stylesheet">
    <!-- Administrator Theme CSS -->
    <link href="{{ asset('css/Administrator/admin.css') }}" rel="stylesheet">
</head>

<body class="m-0 p-0 overflow-hidden" style="background-color: #F5F7DF; font-family: 'Inter', system-ui, -apple-system, sans-serif; height: 100vh;">

    <div class="container-fluid p-0 vh-100 overflow-hidden">
        <div class="row g-0 flex-nowrap h-100 w-100 m-0">
            <!-- Sidebar -->
            @include('Administrator.partials.sidebar')

            <!-- Main Content Area -->
            <div class="col d-flex flex-column h-100 overflow-hidden" id="content" style="min-width: 0;">
                <!-- Topbar -->
                @include('Administrator.partials.topbar')

                <!-- Main Content Box -->
                <main class="p-3 p-xl-4 flex-grow-1 overflow-y-auto overflow-x-hidden" style="background-color: #fbedfb;">
                    @php
                        $section = request('section', 'overview');
                    @endphp

                    @if($section == 'overview')
                        @include('Administrator.content.overview')
                    @elseif($section == 'accman' || $section == 'staff')
                        @include('Administrator.content.accman')
                    @elseif($section == 'amenity')
                        @include('Administrator.content.amenity')
                    @elseif($section == 'activities')
                        @include('Administrator.content.activities')
                    @elseif($section == 'salerep')
                        @include('Administrator.content.salerep')
                    @elseif($section == 'rooms')
                        @include('Administrator.content.rooms')
                    @elseif($section == 'settings')
                        @include('Administrator.content.settings')
                    @else
                        @include('Administrator.content.overview')
                    @endif
                </main>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <!-- Chart.js and Custom Scripts -->
    @include('Administrator.partials.scripts')
</body>

</html>