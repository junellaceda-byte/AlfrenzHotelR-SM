<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Alfrenzo's Hotel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Login CSS -->
    <link href="{{ asset('css/LogIn/login.css') }}" rel="stylesheet">
</head>
<body>

    <!-- Top Left Text -->
    <div class="top-left-brand">
        Alfrenzo's Hotel
    </div>

    <div class="login-card">
        
        <!-- Logo Section -->
        <div class="logo-container">
            <img src="{{ asset('images/logo.png') }}" alt="Alfrenzo's Hotel Logo" class="login-logo">
        </div>
        
        <!-- Welcome Back Text -->
        <h4 class="text-center welcome-text mb-4">Welcome Back!</h4>
        
        <form method="POST" action="{{ url('/login') }}">
            @csrf

            <!-- Display Login Errors -->
            @error('username')
                <div class="alert alert-danger py-2">{{ $message }}</div>
            @enderror

            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-custom w-100">Log In</button>
        </form>
    </div>

</body>
</html>