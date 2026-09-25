<?php
session_start();

// Guard: Only users in pending_auth state can access this page
if (!isset($_SESSION['pending_auth'])) {
    if (isset($_SESSION['admin'])) {
        header("Location: admin/dashboard.php");
        exit();
    }
    if (isset($_SESSION['user'])) {
        if (($_SESSION['role'] ?? '') === 'manager') {
            header("Location: manager/dashboard.php");
        } else {
            header("Location: client/dashboard.php");
        }
        exit();
    }
    header("Location: login.php");
    exit();
}

$pending      = $_SESSION['pending_auth'];
$userName     = htmlspecialchars($pending['name'] ?? 'User');
$userUsername = htmlspecialchars($pending['username'] ?? '');
$userRole     = htmlspecialchars(ucfirst($pending['role'] ?? 'Client'));
$userEmail    = htmlspecialchars($pending['email'] ?? '');
$hasEmail     = !empty($pending['email']);

// Check if verification code was provided in URL query string (direct link)
$urlCode = isset($_GET['code']) ? preg_replace('/[^0-9]/', '', $_GET['code']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link &amp; Verify Gmail - Swine Guard</title>

    <link rel="stylesheet" href="include/fonts.css">
    <link rel="stylesheet" href="include/bootstrap.css">
    <link rel="stylesheet" href="include/icons.css">
    <link rel="stylesheet" href="include/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="stylesheet" href="include/animate.min.css">
    <link rel="stylesheet" href="include/theme.css">
    <script src="include/jquery.js"></script>
    <script src="include/popper.js"></script>
    <script src="include/bootstrap.js"></script>
    <script src="include/sweetalert.js"></script>

    <style>
        body {
            background-color: var(--bg, #0e0e18);
            color: var(--text, #e2e8f0);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', sans-serif;
            background-image: 
                radial-gradient(ellipse at 15% 20%, rgba(124, 58, 237, 0.12) 0%, transparent 45%),
                radial-gradient(ellipse at 85% 80%, rgba(6, 182, 212, 0.08) 0%, transparent 45%);
            background-attachment: fixed;
        }

        .auth-wrapper {
            max-width: 920px;
            width: 100%;
            background: var(--surface, #161626);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border, #2a2a44);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65), 0 0 30px rgba(124, 58, 237, 0.12);
        }

        .brand-panel {
            background: linear-gradient(160deg, #131322 0%, #19192e 100%);
            border-right: 1px solid var(--border, #2a2a44);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 44px;
            text-align: center;
            position: relative;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.04;
            background-image: radial-gradient(#fff 2px, transparent 2px);
            background-size: 24px 24px;
            pointer-events: none;
        }

        .brand-icon {
            font-size: 3.2rem;
            color: var(--purple-light, #a78bfa);
            filter: drop-shadow(0 0 16px rgba(124, 58, 237, 0.5));
            margin-bottom: 20px;
        }

        .form-section {
            padding: 44px;
            background: var(--surface, #161626);
        }

        .form-section h2 {
            font-weight: 700;
            color: var(--text, #f1f5f9);
            margin-bottom: 6px;
        }

        .subtitle {
            color: var(--text-muted, #8892a4);
            font-size: 0.92rem;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        .portal-badge {
            display: inline-block;
            background-color: var(--purple-dim, rgba(124, 58, 237, 0.15));
            color: var(--purple-light, #a78bfa);
            border: 1px solid rgba(124, 58, 237, 0.3);
            font-weight: 700;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
            padding: 5px 12px;
            border-radius: 50px;
            margin-bottom: 14px;
            text-transform: uppercase;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border, #2a2a44);
        }

        .step-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text-muted, #8892a4);
            transition: color 0.2s ease;
        }

        .step-pill.active {
            color: var(--purple-light, #a78bfa);
        }

        .step-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--surface2, #1c1c32);
            border: 1px solid var(--border, #2a2a44);
            color: var(--text-muted, #8892a4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .step-pill.active .step-circle {
            background: linear-gradient(135deg, #7c3aed, #6366f1);
            border-color: #7c3aed;
            color: #ffffff;
            box-shadow: 0 0 10px rgba(124, 58, 237, 0.45);
        }

        .step-divider {
            flex-grow: 1;
            height: 2px;
            background: var(--border, #2a2a44);
        }

        .input-group-text {
            background-color: var(--surface2, #1c1c32);
            border: 1px solid var(--border, #2a2a44);
            border-right: none;
            color: var(--text-muted, #8892a4);
            padding-left: 16px;
            padding-right: 16px;
        }

        .form-control {
            border: 1px solid var(--border, #2a2a44);
            border-left: none;
            background-color: var(--surface2, #1c1c32);
            color: var(--text, #f1f5f9);
            padding: 13px 16px;
            font-size: 0.95rem;
            transition: border-color 0.2s, background-color 0.2s;
        }

        .form-control:focus {
            background-color: rgba(124, 58, 237, 0.05);
            border-color: var(--purple, #7c3aed);
            color: #ffffff;
            box-shadow: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--purple, #7c3aed);
            background-color: rgba(124, 58, 237, 0.05);
            color: var(--purple-light, #a78bfa);
        }

        /* 6-Digit OTP Box Grid */
        .otp-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin: 20px 0;
        }

        .otp-input {
            width: 52px;
            height: 60px;
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            font-family: monospace;
            border: 2px solid var(--border, #2a2a44);
            border-radius: 12px;
            background-color: var(--surface2, #1c1c32);
            color: #ffffff;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .otp-input:focus {
            background-color: #1a1a30;
            border-color: var(--purple, #7c3aed);
            outline: none;
            box-shadow: 0 0 16px rgba(124, 58, 237, 0.4);
        }

        .btn-auth-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);
            border: none;
            color: #ffffff;
            padding: 14px;
            font-weight: 600;
            font-size: 0.95rem;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(124, 58, 237, 0.35);
            transition: all 0.25s ease;
        }

        .btn-auth-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #6d28d9 0%, #4f46e5 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.5);
            color: #ffffff;
        }

        .btn-auth-primary:disabled {
            background: var(--surface2, #1c1c32);
            border: 1px solid var(--border, #2a2a44);
            color: var(--text-muted, #8892a4);
            cursor: not-allowed;
            box-shadow: none;
        }

        .dev-badge {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--amber, #f59e0b);
            border: 1px dashed rgba(245, 158, 11, 0.35);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }

        .cancel-link {
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-muted, #8892a4);
            border-top: 1px solid var(--border, #2a2a44);
            padding-top: 18px;
        }

        .cancel-link a {
            color: var(--text-muted, #8892a4);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .cancel-link a:hover {
            color: var(--red, #ef4444);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .brand-panel {
                display: none;
            }

            .form-section {
                padding: 30px 20px;
            }

            .otp-input {
                width: 44px;
                height: 52px;
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>

    <div class="container flex-grow-1 d-flex align-items-center justify-content-center my-4 my-md-5">
        <div class="auth-wrapper row g-0 animate__animated animate__fadeIn">

            <!-- Left Branding Panel -->
            <div class="col-md-5 brand-panel d-none d-md-flex">
                <div class="brand-icon">
                    <i class="fa-solid fa-envelope-circle-check"></i>
                </div>
                <h3 class="fw-bold mb-2">Account Verification</h3>
                <p class="text-sg-muted small mb-4">Protecting your livestock with real-time temperature, water, and system alerts</p>
                <div class="text-start p-3 rounded-3 w-100 small" style="background: rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.25);">
                    <div class="fw-semibold text-white mb-1"><i class="fa-solid fa-shield-halved text-purple me-1"></i> Why link a Gmail?</div>
                    <div class="text-sg-muted" style="font-size: 0.8rem; line-height: 1.45;">Critical environment spikes, thermal anomalies, and emergency relay cutoffs will be instantly dispatched to your verified inbox.</div>
                </div>
            </div>

            <!-- Right Verification Section -->
            <div class="col-md-7 form-section">

                <!-- Step Progress -->
                <div class="step-indicator">
                    <div class="step-pill active" id="stepIndicator1">
                        <div class="step-circle">1</div>
                        <span>Add Gmail</span>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-pill" id="stepIndicator2">
                        <div class="step-circle">2</div>
                        <span>Verify OTP</span>
                    </div>
                </div>

                <!-- ================= STEP 1: ENTER GMAIL ================= -->
                <div id="step1Container">
                    <span class="portal-badge"><i class="fa-solid fa-user me-1"></i> Hello, <?php echo $userName; ?> (<?php echo $userRole; ?>)</span>
                    <h2>Add Your Gmail</h2>
                    <p class="subtitle">Please link your Gmail address to finalize sign in. A verification code will be dispatched to confirm ownership.</p>

                    <form id="sendEmailForm">
                        <input type="hidden" name="action" value="send_code">
                        <div class="mb-3">
                            <label for="gmailInput" class="form-label small fw-semibold text-sg-muted">Gmail Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" class="form-control" id="gmailInput" name="email"
                                    placeholder="yourname@gmail.com" value="<?php echo $userEmail; ?>" required autofocus>
                            </div>
                            <div class="form-text small text-sg-muted mt-1">We will send a 6-digit one-time passcode to this email.</div>
                        </div>

                        <button type="submit" class="btn btn-auth-primary w-100 mb-4" id="sendCodeBtn">
                            <span id="sendBtnText">Send Verification Code <i class="fa-solid fa-paper-plane ms-1 small"></i></span>
                            <span id="sendBtnSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Sending Code...
                            </span>
                        </button>
                    </form>
                </div>

                <!-- ================= STEP 2: VERIFY OTP CODE ================= -->
                <div id="step2Container" class="d-none">
                    <span class="portal-badge"><i class="fa-solid fa-shield-check me-1"></i> Verification Pending</span>
                    <h2>Enter 6-Digit Code</h2>
                    <p class="subtitle">
                        We sent a verification code to <strong id="sentEmailDisplay" class="text-white"></strong>.
                        <a href="javascript:void(0)" id="changeEmailLink" class="text-decoration-none small text-purple ms-1"><i class="fa-solid fa-pen-to-square"></i> Change</a>
                    </p>

                    <!-- Development Mode Alert (Only displayed if dev_mode is active) -->
                    <div id="devModeNotice" class="dev-badge d-none">
                        <div class="fw-bold"><i class="fa-solid fa-flask-vial me-1"></i> Development Mode Active</div>
                        <div>Your 6-digit OTP is: <strong class="fs-6 text-white" id="devOtpCode"></strong></div>
                    </div>

                    <form id="verifyCodeForm">
                        <input type="hidden" name="action" value="verify_code">
                        <input type="hidden" name="code" id="combinedOtp">

                        <div class="otp-container">
                            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*" autofocus>
                            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*">
                            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*">
                            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*">
                            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*">
                            <input type="text" maxlength="1" class="otp-input" inputmode="numeric" pattern="[0-9]*">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="small text-sg-muted" id="timerDisplay">
                                Code expires in: <strong id="expiryTimer" class="text-white">10:00</strong>
                            </span>
                            <button type="button" class="btn btn-link p-0 text-decoration-none small text-purple" id="resendBtn" disabled>
                                Resend Code (<span id="resendCountdown">60</span>s)
                            </button>
                        </div>

                        <button type="submit" class="btn btn-auth-primary w-100 mb-4" id="verifyBtn">
                            <span id="verifyBtnText">Verify &amp; Complete Login <i class="fa-solid fa-circle-check ms-1 small"></i></span>
                            <span id="verifyBtnSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Verifying...
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Cancel & Return to Login -->
                <div class="cancel-link">
                    <span>Logging in as someone else? </span>
                    <a href="javascript:void(0)" id="cancelPendingBtn"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Cancel and Sign Out</a>
                </div>

            </div>

        </div>
    </div>

    <script>
        let countdownInterval = null;
        let resendInterval = null;
        let resendSeconds = 60;
        let expirySeconds = 600; // 10 minutes

        const urlProvidedCode = "<?php echo $urlCode; ?>";

        $(document).ready(function() {
            // If code is in URL query (?code=123456), auto fill and prompt verification
            if (urlProvidedCode && urlProvidedCode.length === 6) {
                switchToStep2("<?php echo $userEmail ?: 'your email'; ?>");
                fillOtpInputs(urlProvidedCode);
                // Trigger auto verification
                setTimeout(function() {
                    $("#verifyCodeForm").submit();
                }, 300);
            }

            // OTP Input auto-focus & navigation logic
            const $otpInputs = $(".otp-input");
            $otpInputs.each(function(index) {
                $(this).on("input", function() {
                    const val = $(this).val().replace(/[^0-9]/g, '');
                    $(this).val(val);
                    if (val && index < $otpInputs.length - 1) {
                        $otpInputs.eq(index + 1).focus();
                    }
                    syncCombinedOtp();
                });

                $(this).on("keydown", function(e) {
                    if (e.key === "Backspace" && !$(this).val() && index > 0) {
                        $otpInputs.eq(index - 1).focus();
                    }
                });

                // Paste support for full 6-digit code
                $(this).on("paste", function(e) {
                    e.preventDefault();
                    const pastedData = (e.originalEvent.clipboardData || window.clipboardData)
                        .getData('text')
                        .replace(/[^0-9]/g, '')
                        .slice(0, 6);

                    fillOtpInputs(pastedData);
                    syncCombinedOtp();
                });
            });

            function fillOtpInputs(codeStr) {
                const chars = codeStr.split('');
                $otpInputs.each(function(i) {
                    $(this).val(chars[i] || '');
                });
                if (chars.length >= 6) {
                    $otpInputs.last().focus();
                }
            }

            function syncCombinedOtp() {
                let code = '';
                $otpInputs.each(function() {
                    code += $(this).val();
                });
                $("#combinedOtp").val(code);
            }

            // Switch to Step 2
            function switchToStep2(email) {
                $("#step1Container").addClass("d-none");
                $("#step2Container").removeClass("d-none").addClass("animate__animated animate__fadeIn");
                $("#stepIndicator1").removeClass("active");
                $("#stepIndicator2").addClass("active");
                $("#sentEmailDisplay").text(email);
                startTimers();
                $(".otp-input").first().focus();
            }

            // Switch back to Step 1 (Change Email)
            $("#changeEmailLink").on("click", function() {
                clearInterval(countdownInterval);
                clearInterval(resendInterval);
                $("#step2Container").addClass("d-none");
                $("#step1Container").removeClass("d-none").addClass("animate__animated animate__fadeIn");
                $("#stepIndicator2").removeClass("active");
                $("#stepIndicator1").addClass("active");
                $("#gmailInput").focus();
            });

            function startTimers() {
                // 1. Resend cooldown timer
                resendSeconds = 60;
                $("#resendBtn").prop("disabled", true).text(`Resend Code (${resendSeconds}s)`);
                clearInterval(resendInterval);
                resendInterval = setInterval(function() {
                    resendSeconds--;
                    if (resendSeconds <= 0) {
                        clearInterval(resendInterval);
                        $("#resendBtn").prop("disabled", false).text("Resend Code");
                    } else {
                        $("#resendBtn").text(`Resend Code (${resendSeconds}s)`);
                    }
                }, 1000);

                // 2. 10-minute expiry countdown
                expirySeconds = 600;
                clearInterval(countdownInterval);
                countdownInterval = setInterval(function() {
                    expirySeconds--;
                    if (expirySeconds <= 0) {
                        clearInterval(countdownInterval);
                        $("#expiryTimer").text("Expired").addClass("text-danger");
                    } else {
                        const mins = Math.floor(expirySeconds / 60);
                        const secs = expirySeconds % 60;
                        $("#expiryTimer").text(`${mins}:${secs < 10 ? '0' : ''}${secs}`);
                    }
                }, 1000);
            }

            // STEP 1: Submit Email Form
            $("#sendEmailForm").on("submit", function(e) {
                e.preventDefault();
                const email = $("#gmailInput").val().trim();

                if (!email) {
                    Swal.fire({
                        title: 'Required',
                        text: 'Please enter your Gmail address.',
                        icon: 'warning',
                        confirmButtonColor: '#7c3aed'
                    });
                    return;
                }

                // Show spinner
                $("#sendCodeBtn").prop("disabled", true);
                $("#sendBtnText").addClass("d-none");
                $("#sendBtnSpinner").removeClass("d-none");

                $.ajax({
                    url: 'function/email_verification.php',
                    type: 'POST',
                    data: {
                        action: 'send_code',
                        email: email
                    },
                    dataType: 'json',
                    success: function(response) {
                        $("#sendCodeBtn").prop("disabled", false);
                        $("#sendBtnText").removeClass("d-none");
                        $("#sendBtnSpinner").addClass("d-none");

                        if (response.status === 'success') {
                            if (response.dev_mode && response.dev_code) {
                                $("#devOtpCode").text(response.dev_code);
                                $("#devModeNotice").removeClass("d-none");
                            } else {
                                $("#devModeNotice").addClass("d-none");
                            }

                            Swal.fire({
                                title: 'Code Sent!',
                                text: response.message,
                                icon: 'success',
                                timer: 2500,
                                confirmButtonColor: '#7c3aed',
                                showConfirmButton: false
                            });

                            switchToStep2(email);
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'Unable to send verification code.',
                                icon: 'error',
                                confirmButtonColor: '#7c3aed'
                            });
                        }
                    },
                    error: function() {
                        $("#sendCodeBtn").prop("disabled", false);
                        $("#sendBtnText").removeClass("d-none");
                        $("#sendBtnSpinner").addClass("d-none");

                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while contacting the server.',
                            icon: 'error',
                            confirmButtonColor: '#7c3aed'
                        });
                    }
                });
            });

            // Resend Code Button
            $("#resendBtn").on("click", function() {
                const email = $("#sentEmailDisplay").text() || $("#gmailInput").val().trim();
                $("#resendBtn").prop("disabled", true).text("Sending...");

                $.ajax({
                    url: 'function/email_verification.php',
                    type: 'POST',
                    data: {
                        action: 'resend_code',
                        email: email
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            if (response.dev_mode && response.dev_code) {
                                $("#devOtpCode").text(response.dev_code);
                                $("#devModeNotice").removeClass("d-none");
                            }
                            Swal.fire({
                                title: 'Code Resent!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                confirmButtonColor: '#7c3aed',
                                showConfirmButton: false
                            });
                            startTimers();
                        } else {
                            $("#resendBtn").prop("disabled", false).text("Resend Code");
                            Swal.fire({
                                title: 'Error',
                                text: response.message,
                                icon: 'error',
                                confirmButtonColor: '#7c3aed'
                            });
                        }
                    },
                    error: function() {
                        $("#resendBtn").prop("disabled", false).text("Resend Code");
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to request new code.',
                            icon: 'error',
                            confirmButtonColor: '#7c3aed'
                        });
                    }
                });
            });

            // STEP 2: Verify OTP Code Form
            $("#verifyCodeForm").on("submit", function(e) {
                e.preventDefault();
                syncCombinedOtp();
                const code = $("#combinedOtp").val().trim();

                if (code.length !== 6) {
                    Swal.fire({
                        title: 'Incomplete Code',
                        text: 'Please enter all 6 digits of your verification code.',
                        icon: 'warning',
                        confirmButtonColor: '#7c3aed'
                    });
                    return;
                }

                $("#verifyBtn").prop("disabled", true);
                $("#verifyBtnText").addClass("d-none");
                $("#verifyBtnSpinner").removeClass("d-none");

                $.ajax({
                    url: 'function/email_verification.php',
                    type: 'POST',
                    data: {
                        action: 'verify_code',
                        code: code
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Email Verified!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#7c3aed',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        } else {
                            $("#verifyBtn").prop("disabled", false);
                            $("#verifyBtnText").removeClass("d-none");
                            $("#verifyBtnSpinner").addClass("d-none");

                            Swal.fire({
                                title: 'Verification Failed',
                                text: response.message || 'Invalid or expired code.',
                                icon: 'error',
                                confirmButtonColor: '#7c3aed'
                            });
                        }
                    },
                    error: function() {
                        $("#verifyBtn").prop("disabled", false);
                        $("#verifyBtnText").removeClass("d-none");
                        $("#verifyBtnSpinner").addClass("d-none");

                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to verify code with the server.',
                            icon: 'error',
                            confirmButtonColor: '#7c3aed'
                        });
                    }
                });
            });

            // Cancel Pending & Sign Out
            $("#cancelPendingBtn").on("click", function() {
                $.ajax({
                    url: 'function/email_verification.php',
                    type: 'POST',
                    data: { action: 'cancel_pending' },
                    dataType: 'json',
                    success: function(res) {
                        window.location.href = res.redirect || 'login.php';
                    },
                    error: function() {
                        window.location.href = 'login.php';
                    }
                });
            });

        });
    </script>

</body>

</html>
