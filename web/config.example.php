<?php
// Copy this file to config.php and fill in your own values.
// config.php is gitignored and will never be committed.

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cropPrediction');

// --- Python (used by analyze.php / generate_pdf.php to run crop_recommendation.py) ---
// Windows example: 'C:\\Path\\To\\python.exe'
// Linux/Mac example: '/usr/bin/python3'
define('PYTHON_PATH', '/usr/bin/python3');
define('CROP_SCRIPT_PATH', __DIR__ . '/../ml/crop_recommendation.py');

// --- OpenWeatherMap (used client-side in index.php) ---
// Get a free key at https://openweathermap.org/api
define('OPENWEATHER_API_KEY', 'your-openweathermap-api-key');
