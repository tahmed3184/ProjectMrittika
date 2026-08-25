<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Crop Recommendation Input</title>
</head>
<body>
    <div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded-lg mt-10">
        <h1 class="text-3xl font-bold text-center mb-6">Crop Recommendation System</h1>
        <p>Current Location: <span id="current_city">Loading...</span></p>
        <p>Temperature: <span id="current_temperature">Loading...</span></p>
        <p>Humidity: <span id="current_humidity">Loading...</span></p>

        <form method="POST" action="index.php">
            <label for="ph">Soil pH:</label>
            <input type="text" id="ph" name="ph" readonly><br>

            <label for="n">Nitrogen (N):</label>
            <input type="text" id="n" name="n" readonly><br>

            <label for="p">Phosphorus (P):</label>
            <input type="text" id="p" name="p" readonly><br>

            <label for="k">Potassium (K):</label>
            <input type="text" id="k" name="k" readonly><br>

            <label for="month">Select Month:</label>
            <select name="month" id="month" required>
                <option value="January">January</option>
                <option value="February">February</option>
                <option value="March">March</option>
                <option value="April">April</option>
                <option value="May">May</option>
                <option value="June">June</option>
                <option value="July">July</option>
                <option value="August">August</option>
                <option value="September">September</option>
                <option value="October">October</option>
                <option value="November">November</option>
                <option value="December">December</option>
            </select><br>

            <input type="hidden" name="temperature" id="temperature" value="">

            <label for="desired_crop">Desired Crop:</label>
            <select id="desired_crop" name="desired_crop" required>
                <?php
                if (($file = fopen("crop_dataset.csv", "r")) !== FALSE) {
                    $header = fgetcsv($file);
                    $nameIndex = array_search('Name', $header);
                    if ($nameIndex === false) {
                        echo "<option value=''>Name field not found in CSV</option>";
                    } else {
                        while (($row = fgetcsv($file)) !== FALSE) {
                            if (isset($row[$nameIndex]) && $row[$nameIndex] !== "") {
                                $crop_name = $row[$nameIndex];
                                echo "<option value='" . htmlspecialchars($crop_name) . "'>" . htmlspecialchars($crop_name) . "</option>";
                            }
                        }
                    }
                    fclose($file);
                } else {
                    echo "<option value=''>Error loading crops</option>";
                }
                ?>
            </select><br>

            <button type="submit" name="fetch">Fetch Data</button>
            <button type="submit" formaction="analyze.php" name="analyze">Analyze</button>
        </form>
    </div>

    <?php
    if (isset($_POST['fetch'])) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $sql = "SELECT * FROM nutrients ORDER BY id DESC LIMIT 1";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo "<script>
                    document.getElementById('ph').value = {$row['ph']};
                    document.getElementById('n').value = {$row['nitrogen']};
                    document.getElementById('p').value = {$row['phosphorus']};
                    document.getElementById('k').value = {$row['potassium']};
                    document.getElementById('temperature').innerText = '{$row['temperature']} °C';
                    document.getElementById('humidity').innerText = '{$row['humidity']} %';
                </script>";
        } else {
            echo "<div>No data found.</div>";
        }
        $conn->close();
    }
    ?>

    <script>
        async function fetchWeather(latitude, longitude) {
            const apiKey = "<?php echo OPENWEATHER_API_KEY; ?>";
            const url = `https://api.openweathermap.org/data/2.5/weather?lat=${latitude}&lon=${longitude}&units=metric&appid=${apiKey}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (data && data.main) {
                    const temperature = data.main.temp;
                    const humidity = data.main.humidity;
                    const city = data.name;

                    document.getElementById("current_city").innerText = city;
                    document.getElementById("current_temperature").innerText = `${temperature} °C`;
                    document.getElementById("current_humidity").innerText = `${humidity} %`;
                    document.getElementById("temperature").value = temperature;
                }
            } catch (error) {
                console.error("Error connecting to the weather API:", error);
            }
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                fetchWeather(position.coords.latitude, position.coords.longitude);
            });
        }
    </script>
</body>
</html>
