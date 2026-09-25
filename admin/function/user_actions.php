<?php
header('Content-Type: application/json');
include_once __DIR__ . "/../../include/dbcon.php";
include_once __DIR__ . "/../../include/password_validator.php";

$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

// --- 1. FETCH USERS ---
if ($action === 'fetch') {
    $query = "SELECT id, first_name, last_name, name, username, role, status, created_at FROM users ORDER BY id DESC";
    $result = mysqli_query($conn, $query);

    $users = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['created_at'] = date('M d, Y | h:i A', strtotime($row['created_at']));
            if (empty($row['first_name']) && !empty($row['name'])) {
                $parts = explode(' ', trim($row['name']));
                $row['last_name'] = count($parts) > 1 ? array_pop($parts) : '';
                $row['first_name'] = implode(' ', $parts);
            }
            $row['first_name'] = $row['first_name'] ?? '';
            $row['last_name'] = $row['last_name'] ?? '';
            $users[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $users]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve users']);
    }
    exit;
}

// --- 2. ADD USER ---
if ($action === 'add_user') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $role       = $_POST['role'] ?? 'client';
    $status     = $_POST['status'] ?? 'active';
    $name       = trim("$first_name $last_name");

    if (empty($first_name) || empty($last_name) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'First Name, Last Name, Username, and Password are all required']);
        exit;
    }

    // PASSWORD VALIDATION ENFORCEMENT
    $passwordValidation = validate_password_strength($password, $username, $name);
    if (!$passwordValidation['valid']) {
        echo json_encode([
            'status'  => 'error',
            'message' => $passwordValidation['message'],
            'errors'  => $passwordValidation['errors']
        ]);
        exit;
    }

    // CHECK FOR DUPLICATE USERNAME
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username is already taken. Please choose another.']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // INSERT NEW USER
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, name, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $first_name, $last_name, $name, $username, $password, $role, $status);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        echo json_encode([
            'status' => 'success', 
            'message' => 'User created successfully',
            'user' => [
                'id' => $newId,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'name' => $name,
                'username' => $username,
                'role' => $role,
                'status' => $status,
                'created_at' => date('M d, Y | h:i A')
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// --- 3. EDIT USER ---
if ($action === 'edit_user') {
    $id         = intval($_POST['user_id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $role       = $_POST['role'] ?? 'client';
    $status     = $_POST['status'] ?? 'active';
    $password   = trim($_POST['password'] ?? '');
    $name       = trim("$first_name $last_name");

    if ($id <= 0 || empty($first_name) || empty($last_name) || empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'First Name, Last Name, and Username are required']);
        exit;
    }

    // CHECK FOR DUPLICATE USERNAME (EXCLUDING CURRENT USER ID)
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $checkStmt->bind_param("si", $username, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username is already in use by another account.']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // UPDATE RECORD
    if (!empty($password)) {
        // Enforce password security rules on update as well
        $passwordValidation = validate_password_strength($password, $username, $name);
        if (!$passwordValidation['valid']) {
            echo json_encode([
                'status'  => 'error',
                'message' => $passwordValidation['message'],
                'errors'  => $passwordValidation['errors']
            ]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, name = ?, username = ?, password = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $first_name, $last_name, $name, $username, $password, $role, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, name = ?, username = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $name, $username, $role, $status, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User account updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// --- 4. DELETE USER ---
if ($action === 'delete_user') {
    $id = intval($_POST['user_id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid User ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user']);
    }
    $stmt->close();
    exit;
}