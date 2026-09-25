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
    <title>Staff &amp; Manager Login - Swine Guard</title>
    <meta name="description" content="Login to Swine Guard - Advanced Livestock Protection &amp; Management Ecosystem">

    <link rel="stylesheet" href="include/fonts.css">
    <link rel="stylesheet" href="include/bootstrap.css">
    <link rel="stylesheet" href="include/fontawesome-free-6.7.2-web/css/all.min.css">
    <script src="include/sweetalert.js"></script>
    <script src="include/bootstrap.js"></script>
    <script src="include/popper.js"></script>
    <link rel="stylesheet" href="include/theme.css">

    <style>
        /* ── Login Page Specific ───────────────────────────────── */
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-dark);
            position: relative;
            overflow: hidden;
        }

        /* Animated background grid */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(124,58,237,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(124,58,237,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        /* Corner accent blobs */
        body::after {
            content: '';
            position: fixed;
            top: -200px;
            right: -200px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(124,58,237,0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .login-page-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 960px;
            padding: 1rem;
        }

        .login-card {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
        }

        @media (max-width: 640px) {
            .login-card { grid-template-columns: 1fr; }
            .login-brand { display: none; }
        }

        /* ── Brand Panel ── */
        .login-brand {
            background: linear-gradient(145deg, #0a0a1a 0%, #130d28 50%, #0a1428 100%);
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
            border-right: 1px solid var(--border);
        }

        .login-brand::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(124,58,237,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(6,182,212,0.06) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .brand-logo-wrap {
            position: relative;
            z-index: 1;
            margin-bottom: 1.5rem;
        }

        .brand-hex {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, rgba(124,58,237,0.2), rgba(6,182,212,0.2));
            border: 2px solid rgba(124,58,237,0.4);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--purple);
            margin: 0 auto;
        }

        .brand-name {
            position: relative;
            z-index: 1;
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(90deg, #fff 0%, var(--cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            position: relative;
            z-index: 1;
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.6;
            max-width: 240px;
        }

        .brand-stats {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 2rem;
            width: 100%;
        }

        .brand-stat {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 0.75rem;
            text-align: center;
        }

        .brand-stat-val {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--cyan);
        }

        .brand-stat-label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ── Form Panel ── */
        .login-form-panel {
            padding: 3rem 2.5rem;
        }

        .login-portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(124,58,237,0.12);
            border: 1px solid rgba(124,58,237,0.25);
            color: var(--purple);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 50px;
            margin-bottom: 1.5rem;
        }

        .login-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 2rem;
        }

        .sg-input-group {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .sg-input-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.5rem;
        }

        .sg-input-wrap {
            position: relative;
        }

        .sg-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .sg-input {
            width: 100%;
            background: var(--surface-alt);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 12px 14px 12px 40px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s, background 0.2s;
            outline: none;
        }

        .sg-input:focus {
            border-color: var(--purple);
            background: rgba(124,58,237,0.05);
        }

        .sg-input:focus + .sg-input-icon-right,
        .sg-input-wrap:focus-within .sg-input-icon {
            color: var(--purple);
        }

        .sg-input::placeholder { color: var(--text-muted); opacity: 0.6; }

        .sg-login-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--purple), #5b21b6);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 1rem;
            padding: 13px;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
            transition: opacity 0.2s, transform 0.15s;
            letter-spacing: 0.02em;
            font-family: inherit;
        }

        .sg-login-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .sg-login-btn:active { transform: translateY(0); }

        .sg-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin-bottom: 1.25rem;
        }

        .sg-switch-link {
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .sg-switch-link a {
            color: var(--cyan);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }

        .sg-switch-link a:hover { color: var(--purple); text-decoration: underline; }
    </style>
</head>

<body>
    <div class="login-page-wrap animate__animated animate__fadeIn">
        <div class="login-card">

            <!-- ── Brand Panel ── -->
            <div class="login-brand d-none d-sm-flex flex-column">
                <div class="brand-logo-wrap">
                    <div class="brand-hex"><i class="fa-solid fa-shield-halved"></i></div>
                </div>
                <div class="brand-name">Swine Guard</div>
                <div class="brand-tagline">Advanced Livestock Protection &amp; Management Ecosystem</div>

                <div class="brand-stats">
                    <div class="brand-stat">
                        <div class="brand-stat-val">24/7</div>
                        <div class="brand-stat-label">Monitoring</div>
                    </div>
                    <div class="brand-stat">
                        <div class="brand-stat-val"><i class="fa-solid fa-circle" style="color:#22c55e;font-size:0.55rem;"></i> Live</div>
                        <div class="brand-stat-label">Telemetry</div>
                    </div>
                    <div class="brand-stat">
                        <div class="brand-stat-val" style="color:var(--amber);">Auto</div>
                        <div class="brand-stat-label">Fan Control</div>
                    </div>
                    <div class="brand-stat">
                        <div class="brand-stat-val" style="color:var(--purple);">IoT</div>
                        <div class="brand-stat-label">Integrated</div>
                    </div>
                </div>
            </div>

            <!-- ── Form Panel ── -->
            <div class="login-form-panel">
                <div class="login-portal-badge">
                    <i class="fa-solid fa-users"></i>
                    Staff &amp; Manager Portal
                </div>

                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Enter your credentials to access your account</p>

                <form id="userSubmitForm" method="POST" autocomplete="off">
                    <div class="sg-input-group">
                        <label for="username" class="sg-input-label">Username</label>
                        <div class="sg-input-wrap">
                            <i class="fa-regular fa-user sg-input-icon"></i>
                            <input type="text" class="sg-input" id="username" name="username" placeholder="Enter your username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="sg-input-group">
                        <label for="password" class="sg-input-label">Password</label>
                        <div class="sg-input-wrap">
                            <i class="fa-solid fa-lock sg-input-icon"></i>
                            <input type="password" class="sg-input" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" class="sg-login-btn" id="userLoginBtn">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                    </button>

                    <hr class="sg-divider">

                    <div class="sg-switch-link">
                        <span>Are you an administrator? </span>
                        <a href="index.php"><i class="fa-solid fa-user-shield me-1"></i>Admin Login</a>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script src="include/jquery.js"></script>
    <script>
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('error') === 'disabled') {
                Swal.fire({title:'ACCOUNT DISABLED',text:'Your account has been disabled. Please contact the administrator for assistance.',icon:'error',confirmButtonColor:'#7c3aed',confirmButtonText:'OK'}).then(()=>{window.history.replaceState({},'',window.location.pathname);});
            }
        });

        $("#userSubmitForm").on("submit", function(e) {
            e.preventDefault();
            const btn = document.getElementById('userLoginBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Authenticating...';

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
                    if (response === "require_email") {
                        window.location.href = "add_email.php";
                    } else if (response === "manager") {
                        window.location.href = "manager/dashboard.php";
                    } else if (response === "client") {
                        window.location.href = "client/dashboard.php";
                    } else if (response === "disable") {
                        Swal.fire({title:"ACCOUNT DISABLED",text:"Your account is currently disabled. Please contact the administrator.",icon:"error",confirmButtonColor:"#7c3aed",confirmButtonText:"OK"});
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Sign In';
                    } else {
                        Swal.fire({title:"Login Failed",text:response,icon:"error",confirmButtonColor:"#7c3aed",confirmButtonText:"OK"});
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Sign In';
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({title:"ERROR",text:"Something went wrong. Please try again.",icon:"error",confirmButtonColor:"#7c3aed",confirmButtonText:"OK"});
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Sign In';
                    console.error("AJAX Error:", status, error);
                },
            });
        });
    </script>
</body>
</html>