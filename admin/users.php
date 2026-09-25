<?php
include "out.php";
include "../include/dbcon.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Swine Guard</title>

    <link rel="stylesheet" href="../include/fonts.css">
    <link rel="stylesheet" href="../include/bootstrap.css">
    <link rel="stylesheet" href="../include/fontawesome-free-6.7.2-web/css/all.min.css">
    <link rel="stylesheet" href="../include/animate.min.css">
    <link rel="stylesheet" href="../include/theme.css">
    <script src="../include/bootstrap.js"></script>
    <script src="../include/sweetalert.js"></script>
    <script src="../include/popper.js"></script>
    <script src="../include/chart.js"></script>
    <script src="../include/jquery.js"></script>

    
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="sg-layout">
        <main class="sg-main animate__animated animate__fadeIn">

            <!-- Welcome banner section -->
            <div class="sg-page-header">
                <div>
                    <h1 class="sg-page-title"><i class="fa-solid fa-users-gear me-2 text-purple"></i>User Account Management</h1>
                    <p class="sg-page-subtitle">Control system access roles, user identities, and credentials</p>
                </div>
                <div>
                    <button class="sg-btn sg-btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fa-solid fa-user-plus me-1"></i> Add New User
                    </button>
                </div>
            </div>

            <!-- Metrics Overview Grid -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="sg-metric">
                        <div class="sg-icon-box sg-icon-users">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="sg-metric-label">Total Accounts</div>
                        <div class="sg-metric-value" id="total-users-count">0</div>
                        <div class="sg-metric-sub text-green">
                            <i class="fa-solid fa-circle-check me-1"></i> Registered profiles
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="sg-metric">
                        <div class="sg-icon-box sg-icon-manager">
                            <i class="fa-solid fa-user-gear"></i>
                        </div>
                        <div class="sg-metric-label">Manager Roles</div>
                        <div class="sg-metric-value text-cyan" id="manager-users-count">0</div>
                        <div class="sg-metric-sub text-sg-muted">
                            <i class="fa-solid fa-shield-halved me-1"></i> Full system access
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="sg-metric">
                        <div class="sg-icon-box sg-icon-client">
                            <i class="fa-solid fa-user-tag"></i>
                        </div>
                        <div class="sg-metric-label">Client Roles</div>
                        <div class="sg-metric-value text-purple" id="client-users-count">0</div>
                        <div class="sg-metric-sub text-sg-muted">
                            <i class="fa-solid fa-eye me-1"></i> Monitoring privileges
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Data Table Section -->
            <div class="sg-card mb-4">
                <div class="sg-card-header">
                    <h5 class="sg-card-title"><i class="fa-solid fa-users-gear"></i> System Users</h5>
                    <span class="sg-badge sg-badge-purple" id="table-count-badge">Loading...</span>
                </div>
                <div class="sg-table-wrap">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>User Details</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-sg-muted">
                                    <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading user accounts...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ================= ADD USER MODAL ================= -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="addUserForm">
                    <input type="hidden" name="action" value="add_user">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-plus me-2 text-success"></i>Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Full Name</label>
                            <input type="text" name="name" id="add_user_name" class="form-control rounded-3" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Username</label>
                            <input type="text" name="username" id="add_user_username" class="form-control rounded-3" placeholder="e.g. johndoe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="add_user_password" class="form-control" style="border-radius: 8px 0 0 8px;" placeholder="••••••••" required autocomplete="new-password">
                                <button type="button" class="btn toggle-password-btn" data-target="#add_user_password" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>

                            <!-- Password Strength Progress Bar -->
                            <div class="strength-bar-container">
                                <div id="add_strength_bar" class="strength-bar"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="small text-secondary" style="font-size: 0.75rem;">Password Strength:</span>
                                <span id="add_strength_text" class="small fw-semibold text-secondary" style="font-size: 0.75rem;">None</span>
                            </div>

                            <!-- Real-Time Password Checklist -->
                            <div class="password-checklist">
                                <div class="small fw-semibold text-dark mb-2"><i class="fa-solid fa-shield-halved me-1 text-success"></i>Security Requirements:</div>
                                <div class="password-req-item" id="add_req_len">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Minimum length (8+ characters)</span>
                                </div>
                                <div class="password-req-item" id="add_req_case">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Uppercase and lowercase letters</span>
                                </div>
                                <div class="password-req-item" id="add_req_num">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>At least one number (0-9)</span>
                                </div>
                                <div class="password-req-item" id="add_req_spec">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>At least one special character (!@#$%...)</span>
                                </div>
                                <div class="password-req-item" id="add_req_common">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Not a common/weak password</span>
                                </div>
                                <div class="password-req-item" id="add_req_personal">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Doesn't contain username or personal info</span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Role Access</label>
                            <select name="role" class="form-select rounded-3">
                                <option value="client" selected>Client (View-Only)</option>
                                <option value="manager">Manager (Full Access)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Account Status</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="active" selected>Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn sg-btn sg-btn-primary rounded-3 fw-semibold" id="addUserSubmitBtn">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= EDIT USER MODAL ================= -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editUserForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-header border-bottom-0 pb-0">
                        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-pen me-2 text-primary"></i>Edit User Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">New Password <span class="text-muted fw-normal">(Leave blank to keep unchanged)</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="edit_user_password" class="form-control" style="border-radius: 8px 0 0 8px;" placeholder="••••••••" autocomplete="new-password">
                                <button type="button" class="btn toggle-password-btn" data-target="#edit_user_password" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>

                            <!-- Password Strength Progress Bar -->
                            <div class="strength-bar-container">
                                <div id="edit_strength_bar" class="strength-bar"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="small text-secondary" style="font-size: 0.75rem;">Password Strength:</span>
                                <span id="edit_strength_text" class="small fw-semibold text-secondary" style="font-size: 0.75rem;">None</span>
                            </div>

                            <!-- Real-Time Password Checklist -->
                            <div class="password-checklist">
                                <div class="small fw-semibold text-dark mb-2"><i class="fa-solid fa-shield-halved me-1 text-primary"></i>Security Requirements:</div>
                                <div class="password-req-item" id="edit_req_len">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Minimum length (8+ characters)</span>
                                </div>
                                <div class="password-req-item" id="edit_req_case">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Uppercase and lowercase letters</span>
                                </div>
                                <div class="password-req-item" id="edit_req_num">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>At least one number (0-9)</span>
                                </div>
                                <div class="password-req-item" id="edit_req_spec">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>At least one special character (!@#$%...)</span>
                                </div>
                                <div class="password-req-item" id="edit_req_common">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Not a common/weak password</span>
                                </div>
                                <div class="password-req-item" id="edit_req_personal">
                                    <i class="fa-solid fa-circle-xmark"></i> <span>Doesn't contain username or personal info</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" id="edit_confirm_password" class="form-control" style="border-radius: 8px 0 0 8px;" placeholder="Re-enter new password" autocomplete="new-password">
                                <button type="button" class="btn toggle-password-btn" data-target="#edit_confirm_password" tabindex="-1">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div class="mt-1" id="edit_match_indicator" style="display: none;">
                                <span class="small fw-semibold" id="edit_match_text"></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Role Access</label>
                            <select name="role" id="edit_role" class="form-select rounded-3">
                                <option value="client">Client (View-Only)</option>
                                <option value="manager">Manager (Full Access)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Account Status</label>
                            <select name="status" id="edit_status" class="form-select rounded-3">
                                <option value="active">Active</option>
                                <option value="disabled">Disable</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn sg-btn sg-btn-primary rounded-3 fw-semibold" id="editUserSubmitBtn">Update Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AJAX Handler JavaScript -->
    <script>
        $(document).ready(function() {

            // Function 1: Fetch and Populate Users via AJAX
            function fetchUsers() {
                $.ajax({
                    url: 'function/user_actions.php?action=fetch',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            let rows = '';
                            let total = response.data.length;
                            let managers = 0;
                            let clients = 0;

                            if (total > 0) {
                                response.data.forEach(function(user) {
                                    if (user.role === 'manager') managers++;
                                    if (user.role === 'client') clients++;

                                    const roleBadge = user.role === 'manager' 
                                        ? '<span class="sg-badge sg-badge-cyan"><i class="fa-solid fa-shield-halved me-1"></i>Manager</span>' 
                                        : '<span class="sg-badge sg-badge-purple"><i class="fa-solid fa-user me-1"></i>Client</span>';

                                    const userStatus = user.status || 'active';
                                    const statusBadge = userStatus === 'active'
                                        ? '<span class="sg-badge sg-badge-active"><i class="fa-solid fa-circle-check me-1"></i>Active</span>'
                                        : '<span class="sg-badge sg-badge-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Disabled</span>';

                                    rows += `
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="sg-avatar" style="width:34px;height:34px;font-size:0.8rem;">
                                                        ${user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-white">${escapeHtml(user.name)}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="font-monospace text-sg-muted">${escapeHtml(user.username)}</td>
                                            <td>${roleBadge}</td>
                                            <td>${statusBadge}</td>
                                            <td class="text-sg-muted small">${user.created_at}</td>
                                            <td class="text-end">
                                                <button class="action-btn me-1 edit-btn" 
                                                    data-id="${user.id}" 
                                                    data-name="${escapeHtml(user.name)}" 
                                                    data-username="${escapeHtml(user.username)}" 
                                                    data-role="${user.role}"
                                                    data-status="${userStatus}">
                                                    <i class="fa-solid fa-pen-to-square text-cyan"></i>
                                                </button>
                                                <button class="action-btn delete-btn" 
                                                    data-id="${user.id}" 
                                                    data-name="${escapeHtml(user.name)}">
                                                    <i class="fa-solid fa-trash text-red"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                });
                            } else {
                                rows = '<tr><td colspan="6" class="text-center py-4 text-sg-muted">No user accounts found.</td></tr>';
                            }

                            // Update HTML and counters
                            $('#user-table-body').html(rows);
                            $('#total-users-count').text(total);
                            $('#manager-users-count').text(managers);
                            $('#client-users-count').text(clients);
                            $('#table-count-badge').text(`Total: ${total} records`);
                        }
                    }
                });
            }

            // Initial Call
            fetchUsers();

            // Toggle Password Visibility Eye Icon
            $(document).on('click', '.toggle-password-btn', function() {
                const targetSelector = $(this).data('target');
                const targetInput = $(targetSelector);
                const icon = $(this).find('i');
                if (targetInput.attr('type') === 'password') {
                    targetInput.attr('type', 'text');
                    icon.removeClass('fa-eye fa-regular').addClass('fa-eye-slash fa-solid text-primary');
                } else {
                    targetInput.attr('type', 'password');
                    icon.removeClass('fa-eye-slash fa-solid text-primary').addClass('fa-eye fa-regular');
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
                const pwd = password || '';
                const lower = pwd.toLowerCase();
                const cleanUser = (username || '').trim().toLowerCase();
                const cleanName = (fullName || '').trim().toLowerCase();

                const len = pwd.length >= 8;
                const hasCase = /[a-z]/.test(pwd) && /[A-Z]/.test(pwd);
                const hasNum = /[0-9]/.test(pwd);
                const hasSpec = /[^a-zA-Z0-9]/.test(pwd);
                const notCommon = pwd.length > 0 && !commonWeakPasswords.includes(lower);

                let noPersonal = true;
                if (cleanUser.length >= 3 && lower.includes(cleanUser)) {
                    noPersonal = false;
                }
                if (cleanName.length >= 3) {
                    const parts = cleanName.split(/[\s,\.]+/);
                    for (const part of parts) {
                        if (part.length >= 3 && lower.includes(part)) {
                            noPersonal = false;
                            break;
                        }
                    }
                }

                return {
                    len,
                    hasCase,
                    hasNum,
                    hasSpec,
                    notCommon,
                    noPersonal,
                    isValid: len && hasCase && hasNum && hasSpec && notCommon && noPersonal
                };
            }

            // Real-time Checklist & Strength Bar UI Updater
            function updateChecklistUI(prefix, result, password) {
                const hasInput = (password && password.length > 0);

                function setItem(elemId, isValid) {
                    const el = $('#' + elemId);
                    const icon = el.find('i');
                    if (isValid) {
                        el.removeClass('invalid has-input').addClass('valid');
                        icon.removeClass('fa-circle-xmark').addClass('fa-circle-check');
                    } else {
                        el.removeClass('valid').addClass('invalid');
                        if (hasInput) el.addClass('has-input');
                        icon.removeClass('fa-circle-check').addClass('fa-circle-xmark');
                    }
                }

                setItem(prefix + '_req_len', result.len);
                setItem(prefix + '_req_case', result.hasCase);
                setItem(prefix + '_req_num', result.hasNum);
                setItem(prefix + '_req_spec', result.hasSpec);
                setItem(prefix + '_req_common', result.notCommon);
                setItem(prefix + '_req_personal', result.noPersonal);

                // Calculate strength score
                let score = 0;
                if (result.len) score++;
                if (result.hasCase) score++;
                if (result.hasNum) score++;
                if (result.hasSpec) score++;
                if (result.notCommon) score++;
                if (result.noPersonal) score++;

                const bar = $('#' + prefix + '_strength_bar');
                const text = $('#' + prefix + '_strength_text');

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

            // Real-Time Event Handlers: Add User Modal
            $('#add_user_password, #add_user_username, #add_user_name').on('input', function() {
                const pwd = $('#add_user_password').val();
                const username = $('#add_user_username').val();
                const name = $('#add_user_name').val();
                const result = checkPasswordRules(pwd, username, name);
                updateChecklistUI('add', result, pwd);
            });

            $('#addUserModal').on('show.bs.modal', function() {
                $('#addUserForm')[0].reset();
                $('#add_user_password').attr('type', 'password');
                $('#addUserModal .toggle-password-btn i').removeClass('fa-eye-slash fa-solid text-primary').addClass('fa-eye fa-regular');
                updateChecklistUI('add', checkPasswordRules('', '', ''), '');
            });

            // Real-Time Event Handlers: Edit User Modal
            $('#edit_user_password, #edit_username, #edit_name').on('input', function() {
                const pwd = $('#edit_user_password').val();
                const username = $('#edit_username').val();
                const name = $('#edit_name').val();
                const result = checkPasswordRules(pwd, username, name);
                updateChecklistUI('edit', result, pwd);
                checkEditPasswordMatch();
            });

            $('#edit_confirm_password').on('input', function() {
                checkEditPasswordMatch();
            });

            function checkEditPasswordMatch() {
                const pwd = $('#edit_user_password').val();
                const confirmPwd = $('#edit_confirm_password').val();
                const matchInd = $('#edit_match_indicator');
                const matchText = $('#edit_match_text');

                if (pwd.length === 0 || confirmPwd.length === 0) {
                    matchInd.hide();
                    return;
                }

                matchInd.show();
                if (pwd === confirmPwd) {
                    matchText.removeClass('text-danger').addClass('text-success')
                             .html('<i class="fa-solid fa-circle-check me-1"></i> Passwords match');
                } else {
                    matchText.removeClass('text-success').addClass('text-danger')
                             .html('<i class="fa-solid fa-circle-xmark me-1"></i> Passwords do not match');
                }
            }

            // Function 2: Add User AJAX with Password Validation Guard
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();

                const pwd = $('#add_user_password').val();
                const username = $('#add_user_username').val();
                const name = $('#add_user_name').val();
                const val = checkPasswordRules(pwd, username, name);

                if (!val.isValid) {
                    let missingMsg = [];
                    if (!val.len) missingMsg.push("• Minimum length (8+ characters)");
                    if (!val.hasCase) missingMsg.push("• Uppercase and lowercase letters");
                    if (!val.hasNum) missingMsg.push("• At least one number (0-9)");
                    if (!val.hasSpec) missingMsg.push("• At least one special character (!@#$%...)");
                    if (!val.notCommon) missingMsg.push("• Not a common/weak password");
                    if (!val.noPersonal) missingMsg.push("• Must not contain username or personal info");

                    Swal.fire({
                        icon: 'warning',
                        title: 'Password Too Weak',
                        html: '<div class="text-start small mt-2"><strong>Please meet all security requirements:</strong><br>' + missingMsg.join('<br>') + '</div>',
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Understood'
                    });
                    return false;
                }

                $.ajax({
                    url: 'function/user_actions.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#addUserModal').modal('hide');
                            $('#addUserForm')[0].reset();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            fetchUsers();
                        } else {
                            Swal.fire('Validation Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'An unexpected error occurred while adding the user.', 'error');
                    }
                });
            });

            // Function 3: Populate Edit Modal
            $(document).on('click', '.edit-btn', function() {
                $('#edit_user_id').val($(this).data('id'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_username').val($(this).data('username'));
                $('#edit_role').val($(this).data('role'));
                $('#edit_status').val($(this).data('status'));
                $('#edit_user_password').val('').attr('type', 'password');
                $('#edit_confirm_password').val('').attr('type', 'password');
                $('#editUserModal .toggle-password-btn i').removeClass('fa-eye-slash fa-solid text-primary').addClass('fa-eye fa-regular');
                updateChecklistUI('edit', checkPasswordRules('', '', ''), '');
                $('#edit_match_indicator').hide();
                $('#editUserModal').modal('show');
            });

            // Function 4: Edit User AJAX with Password Validation Guard
            $('#editUserForm').on('submit', function(e) {
                e.preventDefault();

                const pwd = $('#edit_user_password').val();
                const confirmPwd = $('#edit_confirm_password').val();
                const username = $('#edit_username').val();
                const name = $('#edit_name').val();

                // If user entered a new password, validate it and check match
                if (pwd.length > 0) {
                    if (pwd !== confirmPwd) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Passwords Do Not Match',
                            text: 'The new password and confirmation password must match exactly.',
                            confirmButtonColor: '#10b981'
                        });
                        return false;
                    }

                    const val = checkPasswordRules(pwd, username, name);
                    if (!val.isValid) {
                        let missingMsg = [];
                        if (!val.len) missingMsg.push("• Minimum length (8+ characters)");
                        if (!val.hasCase) missingMsg.push("• Uppercase and lowercase letters");
                        if (!val.hasNum) missingMsg.push("• At least one number (0-9)");
                        if (!val.hasSpec) missingMsg.push("• At least one special character (!@#$%...)");
                        if (!val.notCommon) missingMsg.push("• Not a common/weak password");
                        if (!val.noPersonal) missingMsg.push("• Must not contain username or personal info");

                        Swal.fire({
                            icon: 'warning',
                            title: 'New Password Too Weak',
                            html: '<div class="text-start small mt-2"><strong>Please meet all security requirements:</strong><br>' + missingMsg.join('<br>') + '</div>',
                            confirmButtonColor: '#10b981',
                            confirmButtonText: 'Understood'
                        });
                        return false;
                    }
                }

                $.ajax({
                    url: 'function/user_actions.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#editUserModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            fetchUsers();
                        } else {
                            Swal.fire('Validation Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'An unexpected error occurred while updating the user.', 'error');
                    }
                });
            });

            // Function 5: Delete User AJAX (SweetAlert Confirmation)
            $(document).on('click', '.delete-btn', function() {
                const userId = $(this).data('id');
                const userName = $(this).data('name');

                Swal.fire({
                    title: 'Delete User Account?',
                    text: `Are you sure you want to remove "${userName}"? This cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'function/user_actions.php',
                            type: 'POST',
                            data: {
                                action: 'delete_user',
                                user_id: userId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                    fetchUsers();
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Utility to sanitize HTML output
            function escapeHtml(text) {
                if (!text) return '';
                return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
            }
        });
    </script>
</body>

</html>