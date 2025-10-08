<?php
// Authentication check - must be logged in as admin

// Configure secure session cookie parameters before starting session
session_set_cookie_params([
    'lifetime' => 0,              // Session cookie (expires when browser closes)
    'path' => '/',                // Available throughout domain
    'domain' => '',               // Current domain
    'secure' => true,             // Only transmit over HTTPS
    'httponly' => true,           // Not accessible via JavaScript
    'samesite' => 'Strict'        // Strict same-site policy (strongest CSRF protection)
]);

session_start();

// Load admin configuration for session timeout
$admin_config = require_once __DIR__ . '/config/admin_config.php';

// Check if user is authenticated
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(403);
    die('Access Denied: Authentication required. Please <a href="admin.php">login</a> to access this resource.');
}

// Check session timeout
if (isset($_SESSION['admin_login_time'])) {
    if (time() - $_SESSION['admin_login_time'] > $admin_config['session_timeout']) {
        session_destroy();
        http_response_code(403);
        die('Session Expired: Please <a href="admin.php">login</a> again.');
    }
    // Update last activity time
    $_SESSION['admin_login_time'] = time();
}

// Load CSRF protection library
require_once __DIR__ . '/includes/csrf.php';

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
// Include dompdf library
require_once './dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\{PHPMailer, SMTP, Exception};
use Dompdf\Dompdf;
use Dompdf\Options;

function sanitizeText($value)
{
    return trim($value ?? '');
}

function sanitizeEmail($email)
{
    return filter_var(trim($email ?? ''), FILTER_SANITIZE_EMAIL);
}

function logEvent($message)
{
    $logFile = __DIR__ . '/email_errors.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $message" . PHP_EOL, FILE_APPEND);
}

function createMailer()
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'ziko2319@gmail.com';
    $mail->Password = 'ezwroeywzfcofwdo';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    return $mail;
}

function sendPdfEmail($recipientEmail, $recipientName, $subject, $htmlBody, $pdfContent, $filename, &$error = null)
{
    $mail = createMailer();

    try {
        $mail->setFrom('Contact@casicam.ma', "Support CASICAM'26");
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->addStringAttachment($pdfContent, $filename, 'base64', 'application/pdf');
        $mail->send();
        return true;
    } catch (Exception $e) {
        $error = $mail->ErrorInfo ?: $e->getMessage();
        //logEvent("Certificate email failure to {$recipientEmail}: {$error}");
        return false;
    }
}

function renderStatusPage($title, $message, $isSuccess = true)
{
    $titleSafe = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $messageSafe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $statusColor = $isSuccess ? '#16a34a' : '#dc2626';

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titleSafe}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0a0a0a; color: #e5e7eb; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 32px; max-width: 420px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.55); }
        h1 { margin-bottom: 16px; color: {$statusColor}; font-size: 1.5rem; }
        p { margin-bottom: 24px; line-height: 1.5; }
        a { display: inline-block; padding: 10px 24px; border-radius: 9999px; background: #2563eb; color: #fff; text-decoration: none; font-weight: 600; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{$titleSafe}</h1>
        <p>{$messageSafe}</p>
        <a href="admin.php">Return to Admin Panel</a>
    </div>
</body>
</html>
HTML;

    exit;
}

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
                page-break-after: avoid;
                page-break-inside: avoid;
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

    // Validate CSRF token
    csrf_validate_token(true);

    $cert_type = sanitizeText($_POST['cert_type']);
    $custom_cert_type = sanitizeText($_POST['custom_cert_type'] ?? '');
    $mode = sanitizeText($_POST['mode']);
    $action = sanitizeText($_POST['action'] ?? 'download_certificate');

    if ($mode === 'single') {
        if (isset($_POST['full_name']) && isset($_POST['organization'])) {
            $full_name = sanitizeText($_POST['full_name']);
            $organization = sanitizeText($_POST['organization']);
            $recipientEmail = sanitizeEmail($_POST['recipient_email'] ?? '');

            $html = generateSingleCertificate($cert_type, $full_name, $organization, $custom_cert_type);

            $options = new Options();
            $options->set('defaultFont', 'Times');
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $safeNameForFile = preg_replace('/[^A-Za-z0-9_-]+/', '_', $full_name);
            $safeNameForFile = trim($safeNameForFile, '_');
            if ($safeNameForFile === '') {
                $safeNameForFile = 'Recipient';
            }
            $filename = 'Certificate_' . $safeNameForFile . '_' . date('Y-m-d') . '.pdf';

            if ($action === 'email_certificate') {
                if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                    renderStatusPage('Recipient Email Required', 'Please provide a valid recipient email address before sending the certificate.', false);
                }

                $pdfContent = $dompdf->output();
                $certDisplayNameRaw = $cert_type === 'others' && $custom_cert_type !== '' ? $custom_cert_type : $cert_type;
                $certDisplayNameText = ucfirst(trim($certDisplayNameRaw ?: 'Certificate'));
                $certDisplayNameSafe = htmlspecialchars($certDisplayNameText, ENT_QUOTES, 'UTF-8');
                $recipientNameSafe = htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8');
                $emailSubject = "Your CASICAM Certificate - {$certDisplayNameText}";
                $emailBody = "<p>Dear {$recipientNameSafe},</p>"
                    . "<p>Thank you for being part of CASICAM 2026. Attached you will find your certificate of {$certDisplayNameSafe}.</p>"
                    . "<p>Best regards,<br>CASICAM Organizing Committee</p>";

                $error = null;
                if (sendPdfEmail($recipientEmail, $full_name, $emailSubject, $emailBody, $pdfContent, $filename, $error)) {
                    renderStatusPage('Certificate Sent', "The certificate was successfully emailed to {$recipientEmail}.", true);
                }

                $failureReason = $error ?: 'Unknown error.';
                renderStatusPage('Email Delivery Failed', 'We were unable to send the certificate. Error: ' . $failureReason, false);
            }

            $dompdf->stream($filename, ['Attachment' => true]);
            exit;
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
            
            if ($action === 'email_certificates') {
                renderStatusPage('Bulk Email Not Supported', 'Email delivery for bulk certificates is not yet available. Please download the generated PDF instead.', false);
            }

            $dompdf->stream($filename, ['Attachment' => true]);
            exit;
        }
    }
    
} else {
    // If accessed directly without POST data, redirect to admin
    header('Location: admin.php');
    exit;
}
?>
