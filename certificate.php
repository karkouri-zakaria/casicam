<?php
// Include dompdf library
require_once './dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Function to read Excel/CSV file and extract names and organizations
function readDataFromFile($file) {
    $data = [];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($extension === 'csv') {
        // Handle CSV files
        $handle = fopen($file['tmp_name'], 'r');
        $isFirstRow = true;
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($isFirstRow) {
                // Check if first row looks like headers
                $firstCell = strtolower(trim($row[0]));
                if ($firstCell === 'name' || $firstCell === 'full name' || $firstCell === 'names' || $firstCell === 'fullname') {
                    $isFirstRow = false;
                    continue; // Skip header row
                }
                $isFirstRow = false;
            }
            
            // Extract name and organization
            $name = isset($row[0]) ? trim($row[0]) : '';
            $organization = isset($row[1]) ? trim($row[1]) : '';
            
            if (!empty($name)) {
                $data[] = [
                    'name' => $name,
                    'organization' => $organization
                ];
            }
        }
        fclose($handle);
    } else if ($extension === 'xlsx' || $extension === 'xls') {
        // For Excel files, we'll ask users to save as CSV for now
        // In production, you could use PhpSpreadsheet library
        return ['error' => 'Please save your Excel file as CSV format and upload again. Use "Save As" > "CSV (Comma delimited)"'];
    }
    
    return $data;
}

// Function to generate a single certificate
function generateSingleCertificate($cert_type, $full_name, $organization, $custom_cert_type = '') {
    // Get current date
    $current_date = date('F j, Y');
    
    // Determine the certificate type display name
    $cert_display_name = $cert_type;
    if ($cert_type === 'others' && !empty($custom_cert_type)) {
        $cert_display_name = $custom_cert_type;
    }
    
    // Define certificate text based on type
    $certificate_texts = [
        'participation' => 'This is to certify that',
        'presentation' => 'This certificate is awarded to',
        'honor' => 'This certificate of honor is presented to',
        'organization' => 'This certificate is awarded to the organization',
        'others' => 'This certificate is presented to'
    ];
    
    $cert_text = isset($certificate_texts[$cert_type]) ? $certificate_texts[$cert_type] : $certificate_texts['others'];
    
    // Create HTML content for the certificate
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 0;
                size: landscape;
            }
            body {
                font-family: "Times New Roman", serif;
                margin: 0;
                padding: 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #333;
                height: 100vh;
                box-sizing: border-box;
            }
            .certificate {
                background: white;
                border: 8px solid #2c3e50;
                border-radius: 20px;
                padding: 60px;
                text-align: center;
                height: calc(100% - 80px);
                position: relative;
                box-shadow: 0 0 30px rgba(0,0,0,0.3);
                page-break-after: always;
            }
            .certificate::before {
                content: "";
                position: absolute;
                top: 20px;
                left: 20px;
                right: 20px;
                bottom: 20px;
                border: 3px solid #34495e;
                border-radius: 10px;
            }
            .header {
                margin-bottom: 40px;
                position: relative;
                z-index: 1;
            }
            .title {
                font-size: 48px;
                font-weight: bold;
                color: #2c3e50;
                text-transform: uppercase;
                letter-spacing: 8px;
                margin-bottom: 10px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            }
            .subtitle {
                font-size: 24px;
                color: #7f8c8d;
                text-transform: uppercase;
                letter-spacing: 4px;
                margin-bottom: 50px;
            }
            .content {
                margin: 60px 0;
                position: relative;
                z-index: 1;
            }
            .cert-text {
                font-size: 22px;
                color: #2c3e50;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .recipient-name {
                font-size: 42px;
                color: #e74c3c;
                font-weight: bold;
                margin: 30px 0;
                text-transform: uppercase;
                letter-spacing: 3px;
                border-bottom: 3px solid #e74c3c;
                display: inline-block;
                padding-bottom: 10px;
            }
            .organization-name {
                font-size: 26px;
                color: #27ae60;
                font-weight: bold;
                margin: 30px 0;
                text-transform: uppercase;
                letter-spacing: 2px;
            }
            .type-badge {
                background: #3498db;
                color: white;
                padding: 10px 30px;
                border-radius: 25px;
                font-size: 18px;
                text-transform: uppercase;
                letter-spacing: 2px;
                margin: 20px 0;
                display: inline-block;
            }
            .footer {
                position: absolute;
                bottom: 60px;
                left: 60px;
                right: 60px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .date {
                font-size: 16px;
                color: #7f8c8d;
                border-top: 2px solid #bdc3c7;
                padding-top: 10px;
                min-width: 200px;
                text-align: center;
            }
            .signature {
                font-size: 16px;
                color: #7f8c8d;
                border-top: 2px solid #bdc3c7;
                padding-top: 10px;
                min-width: 200px;
                text-align: center;
            }
            .logo-area {
                position: absolute;
                top: 40px;
                right: 40px;
                width: 80px;
                height: 80px;
                background: #ecf0f1;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                color: #2c3e50;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="certificate">
            <div class="logo-area">CASICAM</div>
            
            <div class="header">
                <div class="title">Certificate</div>
                <div class="subtitle">of ' . ucfirst($cert_display_name) . '</div>
            </div>
            
            <div class="content">
                <div class="cert-text">' . $cert_text . '</div>
                
                <div class="recipient-name">' . htmlspecialchars($full_name) . '</div>
                
                <div class="type-badge">' . ucfirst($cert_display_name) . ' Certificate</div>
                
                <div class="organization-name">From: ' . htmlspecialchars($organization) . '</div>
                
                <div class="cert-text">
                    for outstanding contribution and dedication in our program.
                    <br>This certificate serves as recognition of your valuable participation.
                </div>
            </div>
            
            <div class="footer">
                <div class="date">
                    Date<br>' . $current_date . '
                </div>
                <div class="signature">
                    Authorized Signature<br>CASICAM Administration
                </div>
            </div>
        </div>
    </body>
    </html>';
}

// Check if form was submitted
if ($_POST && isset($_POST['cert_type']) && isset($_POST['mode'])) {
    
    $cert_type = htmlspecialchars($_POST['cert_type']);
    $custom_cert_type = isset($_POST['custom_cert_type']) ? htmlspecialchars($_POST['custom_cert_type']) : '';
    $mode = htmlspecialchars($_POST['mode']);
    
    if ($mode === 'single') {
        // Single certificate generation
        if (isset($_POST['full_name']) && isset($_POST['organization'])) {
            $full_name = htmlspecialchars($_POST['full_name']);
            $organization = htmlspecialchars($_POST['organization']);
            
            $html = generateSingleCertificate($cert_type, $full_name, $organization, $custom_cert_type);
            
            // Configure Dompdf
            $options = new Options();
            $options->set('defaultFont', 'Times');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            
            // Create Dompdf instance
            $dompdf = new Dompdf($options);
            
            // Load HTML content
            $dompdf->loadHtml($html);
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'landscape');
            
            // Render the PDF
            $dompdf->render();
            
            // Generate filename
            $filename = 'Certificate_' . str_replace(' ', '_', $full_name) . '_' . date('Y-m-d') . '.pdf';
            
            // Output the PDF to browser
            $dompdf->stream($filename, ['Attachment' => true]);
        }
    } elseif ($mode === 'bulk') {
        // Bulk certificate generation
        if (isset($_FILES['excel_file'])) {
            $file = $_FILES['excel_file'];
            
            // Read data from uploaded file
            $participantData = readDataFromFile($file);
            
            if (isset($participantData['error'])) {
                // Handle error
                echo '<script>alert("' . $participantData['error'] . '"); window.history.back();</script>';
                exit;
            }
            
            if (empty($participantData)) {
                echo '<script>alert("No data found in the uploaded file. Please check the file format and ensure it has names in column A and organizations in column B."); window.history.back();</script>';
                exit;
            }
            
            // Generate combined HTML for all certificates
            $combined_html = '';
            foreach ($participantData as $participant) {
                $name = $participant['name'];
                $organization = !empty($participant['organization']) ? $participant['organization'] : 'CASICAM 2026';
                $combined_html .= generateSingleCertificate($cert_type, $name, $organization, $custom_cert_type);
            }
            
            // Configure Dompdf
            $options = new Options();
            $options->set('defaultFont', 'Times');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            
            // Create Dompdf instance
            $dompdf = new Dompdf($options);
            
            // Load HTML content
            $dompdf->loadHtml($combined_html);
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'landscape');
            
            // Render the PDF
            $dompdf->render();
            
            // Generate filename
            $filename = 'Certificates_Bulk_' . date('Y-m-d') . '_' . count($participantData) . '_certificates.pdf';
            
            // Output the PDF to browser
            $dompdf->stream($filename, ['Attachment' => true]);
        }
    }
    
} else {
    // If accessed directly without POST data, redirect to admin
    header('Location: admin.php');
    exit;
}
?>
