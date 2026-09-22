# Mrittika — IoT Soil Testing & Crop Recommendation System

Mrittika reads soil pH, NPK (nitrogen/phosphorus/potassium), temperature,
humidity, and electrical conductivity from an RS485 soil sensor via an
ESP32, and uses that data to recommend suitable crops and fertilizer
requirements.

![Mrittika hardware](docs/images/mrittika.png)
![Mrittika dashboard](docs/images/project_mrittika_device.jpeg)

## How it fits together

```
┌─────────────────┐      WiFi (HTTP POST)      ┌──────────────┐      ┌───────────────────┐
│ ESP32 + RS485    │ ─────────────────────────▶ │ PHP backend  │ ───▶ │ MySQL (nutrients)  │
│ NPK/pH/EC sensor  │                            │ (web/)       │      └───────────────────┘
└──────────────────┘                            │              │
                                                 │              │      ┌───────────────────┐
                                                 │              │ ───▶ │ Python KNN model   │
                                                 │              │      │ (ml/)              │
                                                 └──────────────┘      └───────────────────┘
```

- **`firmware/`** — Arduino/ESP32 sketches for the sensor node
  - `npk_sensor_wifi/` — reads the sensor over Modbus RS485 and POSTs
    readings to the PHP backend over WiFi
  - `npk_sensor_serial/` — same sensor read logic, printed to Serial only
    (useful for bench-testing the sensor without a network)
- **`web/`** — PHP dashboard: pulls the latest reading from MySQL,
  lets the user pick a month and desired crop, calls the Python model,
  and can export a PDF report
- **`ml/`** — `crop_recommendation.py` filters a crop dataset by pH/
  temperature/month and uses K-Nearest Neighbors on N/P/K levels to
  suggest crops and fertilizer amounts

## Setup

### 1. Firmware (`firmware/npk_sensor_wifi/`)
```
cp secrets.example.h secrets.h
# edit secrets.h with your WiFi SSID/password and server URL
```
Open `npk_sensor_wifi.ino` in the Arduino IDE (ESP32 board support +
`ModbusMaster` library required) and flash it.

For bench-testing the sensor without WiFi, use
`firmware/npk_sensor_serial/npk_sensor_serial.ino` instead.

### 2. Database
Create a MySQL database and a `nutrients` table with columns matching
`upload.php`'s insert (`ph, nitrogen, phosphorus, potassium, temperature,
humidity, conductivity`), plus an auto-increment `id`.

### 3. Web backend (`web/`)
```
cd web
cp config.example.php config.php
# edit config.php: DB credentials, PYTHON_PATH, OPENWEATHER_API_KEY
composer install   # installs dompdf for PDF export
```
Serve `web/` with PHP (e.g. XAMPP, or `php -S localhost:8000` for local
testing) and open `index.php`.

### 4. Python model (`ml/`)
```
pip install pandas scikit-learn
```
`crop_recommendation.py` is called by `analyze.php` / `generate_pdf.php`
via the `PYTHON_PATH` / `CROP_SCRIPT_PATH` set in `config.php` — no
manual run needed once configured.

## Known limitations
- `analyze.php` and `generate_pdf.php` pass the same request to
  `crop_recommendation.py` twice (no caching between "Analyze" and
  "Generate PDF").
- Sensor register scaling (`bulkDensity`, the mg/kg → kg/ha conversion)
  is hardcoded per the deployment site's soil and should be recalibrated
  for other locations.
- `ml/backup.py` is a leftover fragment from development, kept here for
  reference — it isn't wired into the app.

## Credits
Built by Tanvir and his Group, UIU.
