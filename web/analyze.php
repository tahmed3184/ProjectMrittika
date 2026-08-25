<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Crop Recommendation Results</title>
</head>
<body>
    <div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded-lg mt-10">
        <h1 class="text-3xl font-bold text-center mb-6">Crop Recommendation Results</h1>

        <?php
        if (isset($_POST['ph']) && isset($_POST['n']) && isset($_POST['p']) && isset($_POST['k']) && isset($_POST['month']) && isset($_POST['temperature']) && isset($_POST['desired_crop'])) {
            $ph = $_POST['ph'];
            $n = $_POST['n'];
            $p = $_POST['p'];
            $k = $_POST['k'];
            $month = $_POST['month'];
            $temperature = $_POST['temperature'];
            $desired_crop = $_POST['desired_crop'];

            $command = escapeshellarg(PYTHON_PATH) . " " . escapeshellarg(CROP_SCRIPT_PATH) .
                " " . escapeshellarg($ph) . " " . escapeshellarg($n) . " " . escapeshellarg($p) .
                " " . escapeshellarg($k) . " " . escapeshellarg($temperature) . " " .
                escapeshellarg($month) . " " . escapeshellarg($desired_crop);
            $output = shell_exec($command);
            
            echo "<h2 class='text-2xl font-semibold mb-4'>Input Data:</h2>";
            echo "<div class='mb-4 bg-gray-50 border border-gray-200 p-4 rounded-lg'>";
            echo "<p><strong>pH:</strong> $ph</p>";
            echo "<p><strong>Nitrogen (N):</strong> $n</p>";
            echo "<p><strong>Phosphorus (P):</strong> $p</p>";
            echo "<p><strong>Potassium (K):</strong> $k</p>";
            echo "<p><strong>Month:</strong> $month</p>";
            echo "<p><strong>Temperature:</strong> $temperature °C</p>";
            echo "</div>";

            echo "<h2 class='text-2xl font-semibold mb-4'>Recommended Crops and Fertilizer Requirements:</h2>";
            echo "<pre class='mb-4 bg-gray-50 border border-gray-200 p-4 rounded-lg'>" . nl2br(htmlspecialchars($output)) . "</pre>";

            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "SELECT * FROM nutrients ORDER BY id DESC LIMIT 1";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo "<h2 class='text-2xl font-semibold mb-4'>Additional Info:</h2>";
                echo "<div class='stats shadow'>";
                echo "<div class='stat place-items-center'><div class='stat-title'>Humidity</div><div class='stat-value'>{$row['humidity']} %</div></div>";
                echo "<div class='stat place-items-center'><div class='stat-title'>Conductivity</div><div class='stat-value'>{$row['conductivity']} μS/cm</div></div>";
                echo "</div>";
            } else {
                echo "<p>No additional info found.</p>";
            }
            $conn->close();
        } else {
            echo "<p>Invalid input data. Please go back and try again.</p>";
        }
        ?>

        <div class="text-center mt-6">
            <form action="generate_pdf.php" method="post">
                <input type="hidden" name="ph" value="<?php echo $ph; ?>">
                <input type="hidden" name="n" value="<?php echo $n; ?>">
                <input type="hidden" name="p" value="<?php echo $p; ?>">
                <input type="hidden" name="k" value="<?php echo $k; ?>">
                <input type="hidden" name="month" value="<?php echo $month; ?>">
                <input type="hidden" name="temperature" value="<?php echo $temperature; ?>">
                <input type="hidden" name="desired_crop" value="<?php echo $desired_crop; ?>">
                <button type="submit" class="btn btn-primary">Generate PDF</button>
            </form>
        </div>
    </div>
</body>
</html>
