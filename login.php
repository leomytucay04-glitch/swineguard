<?php
session_start();

// 1. Check if an Admin is already logged in
if (isset($_SESSION['admin'])) {
    header("Location: admin/dashboard.php");
    exit();
}

// 2. Check if a Manager or Client is already logged in
if (isset($_SESSION['user']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'manager') {
        header("Location: manager/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'client') {
        header("Location: client/dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff & Manager Login - Swine Guard</title>

    <link rel="stylesheet" href="include/fonts.css">
    <script src="include/jquery.js"></script>
    <link rel="stylesheet" href="include/bootstrap.css">
    <link rel="stylesheet" href="include/icons.css">
    <script src="include/sweetalert.js"></script>
    <script src="include/bootstrap.js"></script>
    <script src="include/popper.js"></script>
    <link rel="stylesheet" href="include/fontawesome-free-6.7.2-web/css/all.min.css">
    <style>
        :root {
            --primary-color: #10b981;
            /* Tech Green */
            --dark-color: #0f172a;
            /* Deep Slate */
            --bg-color: #f8fafc;
            /* Light Modern Gray */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: #334155;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navbar Styling */
        .navbar {
            background-color: var(--dark-color) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand,
        .nav-link {
            color: #fff !important;
            transition: color 0.3s ease;
        }

        .navbar-brand:hover,
        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Split Screen Login Layout */
        .login-wrapper {
            max-width: 900px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }

        .brand-panel {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            text-align: center;
            position: relative;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.05;
            background-image: radial-gradient(#fff 2px, transparent 2px);
            background-size: 24px 24px;
        }

        .brand-icon {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        /* Form Controls styling */
        .form-section {
            padding: 40px;
        }

        .form-section h2 {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 28px;
        }

        .input-group-text {
            background-color: #f1f5f9;
            border-right: none;
            color: #94a3b8;
        }

        .form-control {
            border-left: none;
            background-color: #f1f5f9;
            padding: 12px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .form-control:focus+.input-group-text,
        .input-group:focus-within .input-group-text {
            border-color: var(--primary-color);
            background-color: #fff;
            color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        .portal-badge {
            display: inline-block;
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 50px;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .switch-portal-link {
            text-align: center;
            font-size: 0.875rem;
            color: #64748b;
        }

        .switch-portal-link a {
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .switch-portal-link a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .brand-panel {
                display: none;
            }

            .form-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

    <div class="container flex-grow-1 d-flex align-items-center justify-content-center my-5">
        <div class="login-wrapper row g-0 animate__animated animate__fadeIn">

            <!-- Left Branding Panel -->
            <div class="col-md-5 brand-panel d-none d-md-flex">
                <div class="brand-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="fw-bold mb-2">Swine Guard</h3>
                <p class="text-white-50 small">Advanced Livestock Protection & Management Ecosystem</p>
            </div>

            <!-- Right Form Section -->
            <div class="col-md-7 form-section">
                <span class="portal-badge"><i class="fa-solid fa-users me-1"></i> Staff & Manager Portal</span>
                <h2>User Login</h2>
                <p class="subtitle">Please enter your credentials to access your account</p>

                <form id="userSubmitForm" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-medium text-secondary">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-medium text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 shadow-sm mb-4">
                        Sign In <i class="fa-solid fa-arrow-right ms-2 small"></i>
                    </button>

                    <!-- Switch to Admin Portal -->
                    <div class="switch-portal-link pt-2 border-top">
                        <span>Are you an administrator? </span>
                        <a href="index.php"><i class="fa-solid fa-user-shield me-1"></i> Login as Administrator</a>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="include/jquery.js"></script>

    <script>
        $(document).ready(function() {
            // Check if "error=disabled" is present in the URL query string
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get('error') === 'disabled') {
                Swal.fire({
                    title: 'ACCOUNT DISABLED',
                    text: 'Your account has been disabled. Please contact the administrator for assistance.',
                    icon: 'error',
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Optional: Clean up URL by removing the query string without reloading
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            }
        });

        $("#userSubmitForm").on("submit", function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            $.ajax({
                url: "function/user_auth.php",
                type: "POST",
                cache: false,
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    response = response.trim();
                    if (response === "manager") {
                        window.location.href = "manager/dashboard.php";
                    } else if (response === "client") {
                        window.location.href = "client/dashboard.php";
                    } else if (response === "disable") {
                        Swal.fire({
                            title: "ACCOUNT DISABLED",
                            text: "Your account is currently disabled. Please contact the administrator.",
                            icon: "error",
                            confirmButtonColor: "#10b981",
                            confirmButtonText: "OK",
                        });
                    } else {
                        Swal.fire({
                            title: "ERROR",
                            text: response,
                            icon: "error",
                            confirmButtonColor: "#10b981",
                            confirmButtonText: "OK",
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: "ERROR",
                        text: "Something went wrong",
                        icon: "error",
                        confirmButtonColor: "#10b981",
                        confirmButtonText: "OK",
                    });
                    console.error("AJAX Error:", status, error);
                },
            });
        });
    </script>

</body>

</html>