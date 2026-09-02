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
    <script src="../include/bootstrap.js"></script>
    <script src="../include/sweetalert.js"></script>
    <script src="../include/popper.js"></script>
    <script src="../include/chart.js"></script>
    <script src="../include/jquery.js"></script>

    <style>
        :root {
            --primary-color: #10b981;
            --dark-color: #0f172a;
            --bg-color: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: #334155;
            min-height: 100vh;
        }

        .metric-card,
        .log-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .metric-card {
            height: 100%;
            padding: 16px 14px;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
        }

        .icon-shape {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 16px;
        }

        .icon-users {
            background-color: #fef3c7;
            color: #d97706;
        }

        .icon-manager {
            background-color: #e0f2fe;
            color: #0284c7;
        }

        .icon-client {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .badge-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 50px;
        }

        .status-manager {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .status-client {
            background-color: #f1f5f9;
            color: #475569;
        }

        .status-active {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .table {
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .table th {
            font-weight: 600;
            color: #475569;
            background-color: #f8fafc;
        }

        .metric-card h3 {
            font-size: 1.35rem;
            white-space: nowrap;
        }

        .metric-card .card-label {
            font-size: 0.78rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .metric-card .card-subtext {
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #f1f5f9;
        }

        .modal-content {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="container py-4 animate__animated animate__fadeIn">

        <!-- Welcome banner section -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">User Account Management</h1>
                <p class="text-secondary small m-0">Control system access roles, user identities, and credentials</p>
            </div>
            <div>
                <button class="btn btn-primary rounded-3 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-1"></i> Add New User
                </button>
            </div>
        </div>

        <!-- Metrics Overview Grid -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="metric-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="icon-shape icon-users mb-2">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="card-label text-muted fw-medium">Total Accounts</div>
                        <h3 class="fw-bold mb-0 text-dark" id="total-users-count">0</h3>
                        <div class="card-subtext text-success mt-1">
                            <i class="fa-solid fa-circle-check me-1"></i> Registered profiles
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="metric-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="icon-shape icon-manager mb-2">
                            <i class="fa-solid fa-user-gear"></i>
                        </div>
                        <div class="card-label text-muted fw-medium">Manager Roles</div>
                        <h3 class="fw-bold mb-0 text-dark" id="manager-users-count">0</h3>
                        <div class="card-subtext text-secondary mt-1">
                            <i class="fa-solid fa-shield-halved me-1"></i> Full system access
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="metric-card d-flex align-items-center justify-content-between">
                    <div>
                        <div class="icon-shape icon-client mb-2">
                            <i class="fa-solid fa-user-tag"></i>
                        </div>
                        <div class="card-label text-muted fw-medium">Client Roles</div>
                        <h3 class="fw-bold mb-0 text-dark" id="client-users-count">0</h3>
                        <div class="card-subtext text-secondary mt-1">
                            <i class="fa-solid fa-eye me-1"></i> Monitoring privileges
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Data Table Section -->
        <div class="log-card p-0 overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users-gear me-2 text-success"></i>System Users</h5>
                <span class="badge bg-light text-dark border" id="table-count-badge">Loading...</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">User Details</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fa-solid fa-spinner fa-spin me-2"></i> Loading user accounts...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

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
                            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Username</label>
                            <input type="text" name="username" class="form-control rounded-3" placeholder="e.g. johndoe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Password</label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="••••••••" required>
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
                        <button type="submit" class="btn btn-primary rounded-3 fw-semibold">Save User</button>
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
                            <input type="password" name="password" class="form-control rounded-3" placeholder="••••••••">
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
                        <button type="submit" class="btn btn-primary rounded-3 fw-semibold">Update Account</button>
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
                                        ? '<span class="badge-status status-manager"><i class="fa-solid fa-shield-halved me-1"></i>Manager</span>' 
                                        : '<span class="badge-status status-client"><i class="fa-solid fa-user me-1"></i>Client</span>';

                                    const userStatus = user.status || 'active';
                                    const statusBadge = userStatus === 'active'
                                        ? '<span class="badge-status status-active"><i class="fa-solid fa-circle-check me-1"></i>Active</span>'
                                        : '<span class="badge-status status-inactive"><i class="fa-solid fa-circle-xmark me-1"></i>Disable</span>';

                                    rows += `
                                        <tr>
                                            <td class="ps-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center me-3 border fw-bold" style="width: 36px; height: 36px;">
                                                        ${user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark">${escapeHtml(user.name)}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="fw-medium text-secondary">${escapeHtml(user.username)}</td>
                                            <td>${roleBadge}</td>
                                            <td>${statusBadge}</td>
                                            <td class="text-muted small">${user.created_at}</td>
                                            <td class="text-end pe-3">
                                                <button class="action-btn me-1 edit-btn" 
                                                    data-id="${user.id}" 
                                                    data-name="${escapeHtml(user.name)}" 
                                                    data-username="${escapeHtml(user.username)}" 
                                                    data-role="${user.role}"
                                                    data-status="${userStatus}">
                                                    <i class="fa-solid fa-pen-to-square text-primary"></i>
                                                </button>
                                                <button class="action-btn delete-btn" 
                                                    data-id="${user.id}" 
                                                    data-name="${escapeHtml(user.name)}">
                                                    <i class="fa-solid fa-trash text-danger"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                                });
                            } else {
                                rows = '<tr><td colspan="6" class="text-center py-4 text-muted">No user accounts found.</td></tr>';
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

            // Function 2: Add User AJAX
            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
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
                            Swal.fire('Error', response.message, 'error');
                        }
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
                $('#editUserModal').modal('show');
            });

            // Function 4: Edit User AJAX
            $('#editUserForm').on('submit', function(e) {
                e.preventDefault();
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
                            Swal.fire('Error', response.message, 'error');
                        }
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