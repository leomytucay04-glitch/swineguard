<?php
include "out.php"; // Includes session_start() and authentication checks
include "../include/dbcon.php";
date_default_timezone_set('Asia/Manila');

// 1. Fetch current logged-in user details from session
$user_id      = $_SESSION['user'] ?? 0;
$session_role = $_SESSION['role'] ?? 'client';

// 2. Query the 'users' table using the active session ID
$query = "SELECT id, name, username, role, email FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fallback values if database record is blank
$display_name  = !empty($user_data['name']) ? htmlspecialchars($user_data['name']) : 'User Account';
$display_user  = !empty($user_data['username']) ? htmlspecialchars($user_data['username']) : 'user';
$display_email = !empty($user_data['email']) ? htmlspecialchars($user_data['email']) : 'Not linked';
$user_role     = !empty($user_data['role']) ? strtolower($user_data['role']) : strtolower($session_role);

// Role badge formatting
$role_badge_class = ($user_role === 'manager') ? 'bg-primary' : 'bg-success';
$role_title       = ucfirst($user_role); // "Manager" or "Client"
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Swine Guard</title>

    <link rel="stylesheet" href="../include/fonts.css">
    <link rel="stylesheet" href="../include/bootstrap.css">
    <link rel="stylesheet" href="../include/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="stylesheet" href="../include/animate.min.css">
    <link rel="stylesheet" href="../include/theme.css">
    <script src="../include/bootstrap.js"></script>
    <script src="../include/sweetalert.js"></script>
    <script src="../include/popper.js"></script>
    <script src="../include/jquery.js"></script>

    
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="sg-layout">
        <main class="sg-main animate__animated animate__fadeIn">
            
            <div class="sg-page-header">
                <div>
                    <h1 class="sg-page-title"><i class="fa-solid fa-circle-user me-2 text-purple"></i>Account Profile</h1>
                    <p class="sg-page-subtitle">Manage your personal account profile and update security credentials</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left Panel: Avatar & Role Info -->
                <div class="col-lg-4">
                    <div class="sg-card text-center d-flex flex-column align-items-center mb-4">
                        <div class="avatar-circle" id="profileInitials">
                            <?php echo substr($display_name, 0, 2); ?>
                        </div>
                        <h5 class="fw-bold mb-1 text-white" id="displayUserName"><?php echo $display_name; ?></h5>
                        <p class="text-sg-muted small mb-3" id="displayUserUsername">@<?php echo $display_user; ?></p>
                        
                        <!-- Role Badge -->
                        <span class="sg-badge sg-badge-purple">
                            <i class="fa-solid fa-user-shield me-1"></i> <?php echo $role_title; ?> Account
                        </span>
                    </div>
                </div>

                <!-- Right Panel: Profile Forms -->
                <div class="col-lg-8">
                    <!-- Personal Info Box -->
                    <div class="sg-card mb-4">
                        <div class="sg-card-header mb-3">
                            <h5 class="sg-card-title"><i class="fa-regular fa-id-card"></i> Profile Information</h5>
                        </div>
                        <form id="profileInfoForm">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="sg-form-label">Display Name</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $display_name; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="sg-form-label">Username</label>
                                    <input type="text" class="form-control" name="username" value="<?php echo $display_user; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="sg-form-label">System Role</label>
                                    <input type="text" class="form-control" value="<?php echo $role_title; ?>" readonly disabled style="opacity:0.6;">
                                </div>
                                <div class="col-md-6">
                                    <label class="sg-form-label">Verified Gmail <i class="fa-solid fa-circle-check text-green ms-1"></i></label>
                                    <input type="email" class="form-control" value="<?php echo $display_email; ?>" readonly disabled style="opacity:0.6;">
                                </div>
                            </div>
                            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Save Profile Changes</button>
                        </form>
                    </div>

                    <!-- Password Rotation Box -->
                    <div class="sg-card mb-4">
                        <div class="sg-card-header mb-3">
                            <h5 class="sg-card-title"><i class="fa-solid fa-key"></i> Update Security Password</h5>
                        </div>
                        <form id="passwordSecurityForm">
                            <div class="row g-3 mb-3">
                                <div class="col-12">
                                    <label class="sg-form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="current_password" id="profile_current_password" placeholder="Enter current password" required autocomplete="current-password">
                                        <button type="button" class="btn toggle-password-btn" data-target="#profile_current_password" tabindex="-1">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="sg-form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="new_password" id="profile_new_password" placeholder="••••••••" required autocomplete="new-password">
                                        <button type="button" class="btn toggle-password-btn" data-target="#profile_new_password" tabindex="-1">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>

                                    <!-- Password Strength Progress Bar -->
                                    <div class="strength-bar-container">
                                        <div id="profile_strength_bar" class="strength-bar"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="small text-sg-muted" style="font-size: 0.75rem;">Strength:</span>
                                        <span id="profile_strength_text" class="small fw-semibold text-sg-muted" style="font-size: 0.75rem;">None</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="sg-form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="confirm_password" id="profile_confirm_password" placeholder="••••••••" required autocomplete="new-password">
                                        <button type="button" class="btn toggle-password-btn" data-target="#profile_confirm_password" tabindex="-1">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="mt-2" id="profile_match_indicator" style="display: none;">
                                        <span class="small fw-semibold" id="profile_match_text"></span>
                                    </div>
                                </div>

                                <!-- Real-Time Password Checklist -->
                                <div class="col-12">
                                    <div class="password-checklist">
                                        <div class="small fw-semibold text-white mb-2"><i class="fa-solid fa-shield-halved me-1 text-green"></i>Security Requirements:</div>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="password-req-item" id="prof_req_len">
                                                    <i class="fa-solid fa-circle-xmark"></i> <span>Minimum length (8+ characters)</span>
                                                </div>
                                                <div class="password-req-item" id="prof_req_case">
                                                    <i class="fa-solid fa-circle-xmark"></i> <span>Uppercase and lowercase letters</span>
                                                </div>
                                                <div class="password-req-item" id="prof_req_num">
                                                    <i class="fa-solid fa-circle-xmark"></i> <span>At least one number (0-9)</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="password-req-item" id="prof_req_spec">
                                                    <i class="fa-solid fa-circle-xmark"></i> <span>At least one special character (!@#$%...)</span>
                                                </div>
                                                <div class="password-req-item" id="prof_req_common">
                                                    <i class="fa-solid fa-circle-xmark"></i> <span>Not a common/weak password</span>
                                                </div>
                                                <div class="password-req-item" id="prof_req_personal">
                                                    <i class="fa-solid fa-circle-xmark"></i> <span>Doesn't contain username or name</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm px-4" id="profilePasswordSubmitBtn">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function() {

            // Toggle Password Visibility Eye Icon
            $(document).on('click', '.toggle-password-btn', function() {
                const targetSelector = $(this).data('target');
                const targetInput = $(targetSelector);
                const icon = $(this).find('i');

                if (targetInput.attr('type') === 'password') {
                    targetInput.attr('type', 'text');
                    icon.removeClass('fa-eye fa-regular').addClass('fa-eye-slash fa-solid text-success');
                } else {
                    targetInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash fa-solid text-success').addClass('fa-eye fa-regular');
                }
            });

            // Common Weak Passwords Blacklist
            const commonWeakPasswords = [
                '123456', 'password', '12345678', 'qwerty', '123456789', '12345', '1234',
                '111111', '1234567', 'dragon', 'welcome', 'admin', 'admin123', 'admin888',
                'swineguard', 'pass1234', 'password123', 'iloveyou', 'sunshine', 'princess',
                'monkey', 'shadow', 'master', 'football', 'baseball', 'superman', 'trustno1',
                'letmein', 'login', 'p@ssword', 'p@ssw0rd', 'password1', '123123', 'root'
            ];

            // Password Security Rules Evaluation
            function checkPasswordRules(password, username, fullName) {
                const p = password || '';
                const u = (username || '').toLowerCase().trim();
                const fn = (fullName || '').toLowerCase().trim();

                const len = p.length >= 8;
                const hasUpper = /[A-Z]/.test(p);
                const hasLower = /[a-z]/.test(p);
                const hasCase = hasUpper && hasLower;
                const hasNum = /[0-9]/.test(p);
                const hasSpec = /[^a-zA-Z0-9]/.test(p);
                const notCommon = !commonWeakPasswords.includes(p.toLowerCase());

                let noPersonal = true;
                if (u.length >= 3 && p.toLowerCase().includes(u)) {
                    noPersonal = false;
                }
                if (fn.length >= 3) {
                    const parts = fn.split(/[\s,.-]+/);
                    for (let part of parts) {
                        if (part.length >= 3 && p.toLowerCase().includes(part)) {
                            noPersonal = false;
                            break;
                        }
                    }
                }

                const isValid = len && hasCase && hasNum && hasSpec && notCommon && noPersonal;

                return { len, hasCase, hasNum, hasSpec, notCommon, noPersonal, isValid };
            }

            // Update Checklist and Strength Indicator UI
            function updateChecklistUI(result, pwd) {
                const hasInput = (pwd && pwd.length > 0);

                const updateItem = function(elemId, isValid) {
                    const el = $('#' + elemId);
                    const icon = el.find('i');
                    if (!hasInput) {
                        el.removeClass('valid invalid has-input');
                        icon.removeClass('fa-circle-check fa-circle-xmark text-success text-danger')
                            .addClass('fa-circle-xmark');
                    } else if (isValid) {
                        el.removeClass('invalid has-input').addClass('valid');
                        icon.removeClass('fa-circle-xmark text-danger')
                            .addClass('fa-circle-check text-success');
                    } else {
                        el.removeClass('valid').addClass('invalid has-input');
                        icon.removeClass('fa-circle-check text-success')
                            .addClass('fa-circle-xmark text-danger');
                    }
                };

                updateItem('prof_req_len', result.len);
                updateItem('prof_req_case', result.hasCase);
                updateItem('prof_req_num', result.hasNum);
                updateItem('prof_req_spec', result.hasSpec);
                updateItem('prof_req_common', result.notCommon);
                updateItem('prof_req_personal', result.noPersonal);

                // Calculate strength score (0 to 6)
                let score = 0;
                if (result.len) score++;
                if (result.hasCase) score++;
                if (result.hasNum) score++;
                if (result.hasSpec) score++;
                if (result.notCommon) score++;
                if (result.noPersonal) score++;

                const bar = $('#profile_strength_bar');
                const text = $('#profile_strength_text');

                bar.removeClass('strength-weak strength-fair strength-good strength-strong');

                if (!hasInput) {
                    bar.css('width', '0%');
                    text.text('None').attr('class', 'small fw-semibold text-secondary');
                } else if (score <= 2) {
                    bar.css('width', '25%').addClass('strength-weak');
                    text.text('Weak').attr('class', 'small fw-bold text-danger');
                } else if (score <= 4) {
                    bar.css('width', '50%').addClass('strength-fair');
                    text.text('Fair').attr('class', 'small fw-bold text-warning');
                } else if (score === 5) {
                    bar.css('width', '75%').addClass('strength-good');
                    text.text('Good').attr('class', 'small fw-bold text-primary');
                } else {
                    bar.css('width', '100%').addClass('strength-strong');
                    text.text('Strong & Secure').attr('class', 'small fw-bold text-success');
                }
            }

            // Real-Time Event Handlers for Password Inputs
            $('#profile_new_password').on('input', function() {
                const pwd = $(this).val();
                const username = $('input[name="username"]').val() || '';
                const name = $('input[name="name"]').val() || '';
                const result = checkPasswordRules(pwd, username, name);
                updateChecklistUI(result, pwd);
                checkPasswordMatch();
            });

            $('#profile_confirm_password').on('input', function() {
                checkPasswordMatch();
            });

            function checkPasswordMatch() {
                const newPwd = $('#profile_new_password').val();
                const confirmPwd = $('#profile_confirm_password').val();
                const matchInd = $('#profile_match_indicator');
                const matchText = $('#profile_match_text');

                if (confirmPwd.length === 0) {
                    matchInd.hide();
                    return;
                }

                matchInd.show();
                if (newPwd === confirmPwd) {
                    matchText.removeClass('text-danger').addClass('text-success')
                             .html('<i class="fa-solid fa-circle-check me-1"></i> Passwords match');
                } else {
                    matchText.removeClass('text-success').addClass('text-danger')
                             .html('<i class="fa-solid fa-circle-xmark me-1"></i> Passwords do not match');
                }
            }

            // 1. Profile Info Form submission
            $("#profileInfoForm").on("submit", function(e) {
                e.preventDefault();
                var formData = new FormData(this);

                $.ajax({
                    url: "function/update_profile.php",
                    type: "POST",
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        var res = response.trim();
                        if (res === "success") {
                            Swal.fire({
                                title: "UPDATED",
                                text: "Profile details updated successfully.",
                                icon: "success",
                                confirmButtonColor: "#10b981"
                            });
                            // Refresh display elements live on UI
                            var updatedName = $("input[name='name']").val();
                            var updatedUser = $("input[name='username']").val();
                            $("#displayUserName").text(updatedName);
                            $("#displayUserUsername").text(updatedUser);
                            $("#profileInitials").text(updatedName.substring(0, 2).toUpperCase());
                        } else {
                            Swal.fire({
                                title: "ERROR",
                                text: res,
                                icon: "error",
                                confirmButtonColor: "#10b981"
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: "ERROR",
                            text: "Network error. Failed to reach server connection.",
                            icon: "error",
                            confirmButtonColor: "#10b981"
                        });
                    }
                });
            });

            // 2. Password Form submission with Validation Guard
            $("#passwordSecurityForm").on("submit", function(e) {
                e.preventDefault();

                const currentPwd = $('#profile_current_password').val();
                const newPwd = $('#profile_new_password').val();
                const confirmPwd = $('#profile_confirm_password').val();
                const username = $('input[name="username"]').val() || '';
                const name = $('input[name="name"]').val() || '';

                if (!currentPwd || !newPwd || !confirmPwd) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Fields',
                        text: 'Please fill out all password fields.',
                        confirmButtonColor: '#10b981'
                    });
                    return false;
                }

                if (newPwd !== confirmPwd) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Passwords Do Not Match',
                        text: 'The new password and confirmation password must match exactly.',
                        confirmButtonColor: '#10b981'
                    });
                    return false;
                }

                const val = checkPasswordRules(newPwd, username, name);
                if (!val.isValid) {
                    let missingMsg = [];
                    if (!val.len) missingMsg.push("• Minimum length (8+ characters)");
                    if (!val.hasCase) missingMsg.push("• Uppercase and lowercase letters");
                    if (!val.hasNum) missingMsg.push("• At least one number (0-9)");
                    if (!val.hasSpec) missingMsg.push("• At least one special character (!@#$%...)");
                    if (!val.notCommon) missingMsg.push("• Not a common/weak password");
                    if (!val.noPersonal) missingMsg.push("• Must not contain username or personal name");

                    Swal.fire({
                        icon: 'warning',
                        title: 'Password Too Weak',
                        html: '<div class="text-start small mt-2"><strong>Please satisfy all security requirements:</strong><br>' + missingMsg.join('<br>') + '</div>',
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Understood'
                    });
                    return false;
                }

                var formData = new FormData(this);

                $.ajax({
                    url: "function/update_password.php",
                    type: "POST",
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        var res = response.trim();
                        if (res === "success") {
                            Swal.fire({
                                title: "SECURED",
                                text: "Password changed successfully.",
                                icon: "success",
                                confirmButtonColor: "#10b981"
                            });
                            $("#passwordSecurityForm")[0].reset();
                            updateChecklistUI(checkPasswordRules('', '', ''), '');
                            $('#profile_match_indicator').hide();
                        } else {
                            Swal.fire({
                                title: "ERROR",
                                text: res,
                                icon: "error",
                                confirmButtonColor: "#10b981"
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: "ERROR",
                            text: "Network error occurred. Try again later.",
                            icon: "error",
                            confirmButtonColor: "#10b981"
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>