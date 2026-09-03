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
        $GLOBALS['dbservername'] = "sql300.infinityfree.com";
        $GLOBALS['dbusername']   = "if0_36673365";
        $GLOBALS['dbpassword']   = "CC0CvHt9bZtT570";
        $GLOBALS['dbname']       = "if0_36673365_wheel";
    }
}

// 2. Only connect if $conn is not already active
if (!isset($conn) || !($conn instanceof mysqli) || @mysqli_ping($conn) === false) {
    if (isset($GLOBALS['conn']) && ($GLOBALS['conn'] instanceof mysqli) && @mysqli_ping($GLOBALS['conn']) !== false) {
        $conn = $GLOBALS['conn'];
    } else {
        // Select environment
        localhost();
        // online();

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