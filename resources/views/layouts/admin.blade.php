<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - LJS Realty</title>
    
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navbar-admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body">

    <div class="admin-layout">
        <!-- DYNAMIC SIDEBAR (Changes based on what the page injects) -->
        @yield('sidebar')

        <div class="admin-main-wrapper">
            <!-- GLOBAL NAVBAR (Always present for everyone) -->
            @include('admin.navbar-admin')

            <!-- MAIN PAGE CONTENT -->
            <main class="admin-content">
                <div class="dashboard-frame">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script src="{{ asset('js/sidebar.js') }}"></script>
    <script src="{{ asset('js/navbar-admin.js') }}"></script>
    @stack('scripts')
</body>
</html>