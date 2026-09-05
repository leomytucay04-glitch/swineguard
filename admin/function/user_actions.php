<?php
header('Content-Type: application/json');
include_once __DIR__ . "/../../include/dbcon.php";
include_once __DIR__ . "/../../include/password_validator.php";

$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

// --- 1. FETCH USERS ---
if ($action === 'fetch') {
    $query = "SELECT id, name, username, role, status, created_at FROM users ORDER BY id DESC";
    $result = mysqli_query($conn, $query);

    $users = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['created_at'] = date('M d, Y | h:i A', strtotime($row['created_at']));
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
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role'] ?? 'client';
    $status   = $_POST['status'] ?? 'active';

    if (empty($name) || empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled out']);
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
    $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $username, $password, $role, $status);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'User created successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// --- 3. EDIT USER ---
if ($action === 'edit_user') {
    $id       = intval($_POST['user_id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role     = $_POST['role'] ?? 'client';
    $status   = $_POST['status'] ?? 'active';
    $password = trim($_POST['password'] ?? '');

    if ($id <= 0 || empty($name) || empty($username)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid user data provided']);
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

        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, password = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $username, $password, $role, $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $username, $role, $status, $id);
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