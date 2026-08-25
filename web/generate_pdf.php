<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;

if (isset($_POST['ph']) && isset($_POST['n']) && isset($_POST['p']) && isset($_POST['k']) && isset($_POST['month']) && isset($_POST['temperature']) && isset($_POST['desired_crop'])) {
    $ph = $_POST['ph'];
    $n = $_POST['n'];
    $p = $_POST['p'];
    $k = $_POST['k'];
    $month = $_POST['month'];
    $temperature = $_POST['temperature'];
    $desired_crop = $_POST['desired_crop'];

    // Prepare HTML content (excluding footer)
    $html = '
    <html>
    <head>
        <title>Crop Recommendation Report</title>
        <style>
            body { 
                font-family: sans-serif; 
                margin: 0; 
                padding: 20px; 
                box-sizing: border-box;
            }
            h1 { 
                text-align: center; 
                margin-bottom: 20px; 
            }
            .container { 
                width: 100%; 
                max-width: 800px; 
                margin: 0 auto; 
                overflow: hidden; 
            }
            .section { 
                margin-bottom: 20px; 
                border: 1px solid #ccc; 
                padding: 10px; 
                border-radius: 5px;
            }
            pre { 
                white-space: pre-wrap; 
                word-wrap: break-word; 
            }
            @page {
                size: A4;
                margin: 20mm; /* Adjust margins as needed */
            }
        </style>
    </head>
    <body>
    
        <h1 style="font-size: 20px; margin-bottom: 10px;">Crop Recommendation Results</h1>
        <div class="container">
            <div class="section">
                <h2 style="font-size: 16px; margin-bottom: 10px;">Input Data:</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; font-size: 14px; line-height: 1.5;">
            <p style="margin: 0; width: 33%;"><strong>pH:</strong> ' . $ph . '</p>
            <p style="margin: 0; width: 33%;"><strong>Nitrogen (N):</strong> ' . $n . '</p>
            <p style="margin: 0; width: 33%;"><strong>Phosphorus (P):</strong> ' . $p . '</p>
            <p style="margin: 0; width: 33%;"><strong>Potassium (K):</strong> ' . $k . '</p>
            <p style="margin: 0; width: 33%;"><strong>Temperature:</strong> ' . $temperature . '</p>
            <p style="margin: 0; width: 33%;"><strong>Month:</strong> ' . $month . '</p>
        </div>
            </div>';

    // Call your Python script to get recommendations
    $command = escapeshellarg(PYTHON_PATH) . " " . escapeshellarg(CROP_SCRIPT_PATH) .
        " " . escapeshellarg($ph) . " " . escapeshellarg($n) . " " . escapeshellarg($p) .
        " " . escapeshellarg($k) . " " . escapeshellarg($temperature) . " " .
        escapeshellarg($month) . " " . escapeshellarg($desired_crop);
    $output = shell_exec($command);

    // Append recommendations and additional data to HTML
    $html .= '
            <div class="section">
                <h2 style="font-size: 20px; margin-bottom: 10px;">Recommended Crops and Fertilizer Requirements:</h2>
                <pre>' . nl2br($output) . '</pre>
            </div>
        </div>
    </body>
    </html>';

    // Initialize Dompdf
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait'); // Set paper size to A4
    $dompdf->render();

    // Output the generated PDF (force download)
    $dompdf->stream("crop_recommendation_report.pdf", array("Attachment" => 1));
} else {
    echo "Invalid input data. Please go back and try again.";
}
