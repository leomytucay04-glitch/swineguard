<?php

// Include your database connection ($conn)
include "include/dbcon.php";

// 1. Set the timezone to Philippines (Manila)
date_default_timezone_set('Asia/Manila');

// Check if data is coming from the ESP32 via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Safely capture the incoming sensor values from the ESP32 POST request
    $temperature = isset($_POST['temperature']) ? mysqli_real_escape_string($conn, $_POST['temperature']) : '0.0';
    $humidity    = isset($_POST['humidity']) ? mysqli_real_escape_string($conn, $_POST['humidity']) : '0.0';
    $water       = isset($_POST['water']) ? mysqli_real_escape_string($conn, $_POST['water']) : 'Unknown';

    // NEW: Block saving if temperature and humidity are both 0.0 (sensor error fallback)
    if ($temperature == '0.0' && $humidity == '0.0') {
        echo "Ignored: Sensor error data (0.0) received. Not saved to database.";
        exit(); // Stops the script immediately right here
    }

    // 3. Generate Philippine Date and Time in VARCHAR formats
    $current_date = date("Y-m-d");      // Format: YYYY-MM-DD
    $current_time = date("h:i A");      // Format: HH:MM AM/PM (e.g., 10:44 AM)

    // 4. Insert directly into your "sensor_data" table
    $sql = "INSERT INTO sensor_data (temperature, humidity, water, date, time) 
            VALUES ('$temperature', '$humidity', '$water', '$current_date', '$current_time')";

    if (mysqli_query($conn, $sql)) {
        // Automatically check environmental alert thresholds and trigger SMS if needed
        include_once __DIR__ . "/include/sms_helper.php";
        check_and_trigger_sms_alerts($temperature, $humidity, $water);

        // This is what the ESP32 receives back on success!
        echo "Data saved successfully! Time: " . $current_time;
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }

} else {
    echo "Error: Only POST requests are allowed.";
}

?>