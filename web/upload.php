<?php
require_once __DIR__ . '/config.php';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Database connection is OK<br>";

// Check if data was sent via POST
if (isset($_POST["temperature"]) && isset($_POST["humidity"]) && isset($_POST["conductivity"]) && isset($_POST["ph"]) && isset($_POST["nitrogen"]) && isset($_POST["phosphorus"]) && isset($_POST["potassium"])) {
    // Retrieve the POST data
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];
    $conductivity = $_POST['conductivity'];
    $ph = $_POST['ph'];
    $nitrogen = $_POST['nitrogen'];
    $phosphorus = $_POST['phosphorus'];
    $potassium = $_POST['potassium'];

    // Prepared statement (avoids SQL injection from raw POST values)
    $sql = "INSERT INTO nutrients (ph, nitrogen, phosphorus, potassium, temperature, humidity, conductivity)
    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ddddddd", $ph, $nitrogen, $phosphorus, $potassium, $temperature, $humidity, $conductivity);

    if (mysqli_stmt_execute($stmt)) {
        echo "\nNew record created successfully";
    } else {
        echo "Error: " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>