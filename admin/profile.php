<?php
include "out.php";

?>
<?php
include "../include/dbcon.php";
date_default_timezone_set('Asia/Manila');

// Fetch current administrator profile data (Assuming id 1 for system admin context)
$admin_id = 1;
$query = "SELECT name, username, email FROM admin WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();

// Fallback values if database target returns blank
$admin_name = !empty($admin_data['name']) ? htmlspecialchars($admin_data['name']) : 'Operator Account';
$admin_user = !empty($admin_data['username']) ? htmlspecialchars($admin_data['username']) : 'admin';
$admin_email = !empty($admin_data['email']) ? htmlspecialchars($admin_data['email']) : 'no';
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

        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }

        .avatar-circle {
            width: 80px;
            height: 80px;
            background-color: #e2e8f0;
            color: var(--dark-color);
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .form-control {
            border-radius: 8px;
            background-color: #f1f5f9;
            padding: 12px;
            border: 1px solid transparent;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #059669;
        }
    </style>
</head>

<body>

    <?php include("nav.php"); ?>

    <div class="container py-2 animate__animated animate__fadeIn">
        <div class="mb-4">
            <h1 class="h3 fw-bold text-dark m-0">Account Profile</h1>
            <p class="text-secondary small m-0">Manage personal contact profiles and system security credentials</p>
        </div>

        <div class="row">
            <!-- Left Panel: Avatar display info -->
            <div class="col-lg-4">
                <div class="profile-card text-center d-flex flex-column align-items-center">
                    <div class="avatar-circle" id="profileInitials">
                        <?php echo substr($admin_name, 0, 2); ?>
                    </div>
                    <h5 class="fw-bold mb-1" id="displayAdminName"><?php echo $admin_name; ?></h5>
                    <p class="text-muted small mb-3" id="displayAdminUser"><?php echo $admin_user; ?></p>
                    <span class="badge bg-dark px-3 py-2 rounded-pill small fw-medium">System Administrator</span>
                </div>
            </div>

            <!-- Right Panel: Edit Credentials Forms -->
            <div class="col-lg-8">
                <!-- Personal Info Details Box -->
                <div class="profile-card mb-4">
                    <h5 class="fw-bold mb-4"><i class="fa-regular fa-id-card me-2 text-success"></i>Profile Information</h5>
                    <form id="profileInfoForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Display Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($admin_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($admin_user); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin_email ?? ''); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Save Personal Changes</button>
                    </form>
                </div>

                <!-- Password Rotation Box -->
                <div class="profile-card">
                    <h5 class="fw-bold mb-4"><i class="fa-solid fa-key me-2 text-success"></i>Update Security Password</h5>
                    <form id="passwordSecurityForm">
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function() {

            // 1. Handle Profile Info Form submission via AJAX
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
                            // Dynamically refresh visual card labels without reloading page
                            var updatedName = $("input[name='name']").val();
                            var updatedUser = $("input[name='username']").val();
                            $("#displayAdminName").text(updatedName);
                            $("#displayAdminUser").text(updatedUser);
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

            // 2. Handle Password Rotation Form submission via AJAX
            $("#passwordSecurityForm").on("submit", function(e) {
                e.preventDefault();
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
                            $("#passwordSecurityForm")[0].reset(); // Clear form fields out
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