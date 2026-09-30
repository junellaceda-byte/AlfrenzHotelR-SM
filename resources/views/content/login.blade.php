<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfrenzo's Hotel — Log In</title>

    <!-- Bootstrap -->
    <script src="{{ asset('bootstrap-5.3.8-dist/css/bootstrap.bundle.min.css') }}"></script>
    <!-- Bootstrap Icons -->
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"> -->
</head>

<body class="min-vh-100 d-flex flex-column justify-content-between m-0 p-0">

    <!-- Top Header Bar -->
    <header class="py-2 text-center border-bottom">
        <span class="fw-bold text-uppercase small"><h3>Alfrenzo's Hotel</h3></span>
    </header>

    <!-- Main Centered Content -->
    <main class="container d-flex align-items-center justify-content-center flex-grow-1 py-4">
        
        <!-- Card Container -->
        <div class="card border rounded-3 w-100 p-4" style="max-width: 450px;">

            <!-- Header Layout: Logo Top-Left + Welcome Text -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="border rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px;">
                    <span class="fw-bold small">LOGO</span>
                </div>
                <div>
                    <h1 class="h4 fw-bold mb-0">Welcome</h1>
                    <p class="small mb-0">Sign into your account to continue</p>
                </div>
            </div>

            <!-- Log-In Form -->
            <form action="/login" method="POST">
                @csrf

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="login_email" class="form-label small fw-semibold mb-1">Email Address</label>
                    <input 
                        type="email" 
                        id="login_email" 
                        name="email" 
                        class="form-control form-control-sm border" 
                        placeholder="Email Address" 
                        required
                    >
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="login_password" class="form-label small fw-semibold mb-1">Password</label>
                    <input 
                        type="password" 
                        id="login_password" 
                        name="password" 
                        class="form-control form-control-sm border" 
                        placeholder="Password" 
                        required
                    >
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="login_remember" name="remember">
                        <label class="form-check-label small" for="login_remember">Remember me</label>
                    </div>
                    <a href="{{ url('/forgot-password') }}" class="small text-decoration-underline">
                        Forgot Password?
                    </a>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn border fw-semibold py-2">
                        Log In &rarr;
                    </button>
                </div>

            </form>

        </div>
    </main>

    <!-- Bottom Footer Bar -->
    <footer class="py-2 text-center border-top small">
        &copy; {{ date('Y') }} Alfrenzo's Hotel
    </footer>

</body>
</html>