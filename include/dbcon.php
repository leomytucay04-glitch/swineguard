<?php

// 1. Guard against redeclaring functions if dbcon.php is included multiple times
if (!function_exists('localhost')) {
    function localhost()
    {
        $GLOBALS['dbservername'] = "localhost";
        $GLOBALS['dbusername']   = "root";
        $GLOBALS['dbpassword']   = "";
        $GLOBALS['dbname']       = "pig";
    }
}

if (!function_exists('online')) {
    function online()
    {
        // Hostinger Database Configuration
        $GLOBALS['dbservername'] = "localhost";
        $GLOBALS['dbusername']   = "u793614128_user_pig";
        $GLOBALS['dbpassword']   = "YOUR_HOSTINGER_DB_PASSWORD"; // <-- REPLACE WITH YOUR HOSTINGER DB PASSWORD
        $GLOBALS['dbname']       = "u793614128_pig";
    }
}

// 2. Only connect if $conn is not already active
if (!isset($conn) || !($conn instanceof mysqli) || @mysqli_ping($conn) === false) {
    if (isset($GLOBALS['conn']) && ($GLOBALS['conn'] instanceof mysqli) && @mysqli_ping($GLOBALS['conn']) !== false) {
        $conn = $GLOBALS['conn'];
    } else {
        // Automatic environment detection (swinemonitoring.site vs localhost)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            localhost();
        } else {
            online();
        }

        $conn = mysqli_connect(
            $GLOBALS['dbservername'] ?? $dbservername,
            $GLOBALS['dbusername'] ?? $dbusername,
            $GLOBALS['dbpassword'] ?? $dbpassword,
            $GLOBALS['dbname'] ?? $dbname
        );

        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        $GLOBALS['conn'] = $conn;
    }
} else {
    $GLOBALS['conn'] = $conn;
}
?>