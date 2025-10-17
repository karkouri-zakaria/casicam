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

require_once "PHPMailer/Exception.php";
require_once "PHPMailer/PHPMailer.php";
require_once "PHPMailer/SMTP.php";
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

function sendPdfAttachmentEmail($recipientEmail, $recipientName, $subject, $htmlBody, $pdfContent, $filename, &$error = null)
{
    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ziko2319@gmail.com';
        $mail->Password = 'ezwroeywzfcofwdo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

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
        //logEvent("Invoice email failure to {$recipientEmail}: {$error}");
        return false;
    }
}

function renderInvoiceStatusPage($title, $message, $isSuccess = true)
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

// Check if form was submitted
if ($_POST && isset($_POST['full_name']) && isset($_POST['amount']) && isset($_POST['organization'])) {
    
    // Validate CSRF token
    csrf_validate_token(true);
    
    $action = sanitizeText($_POST['action'] ?? 'download_invoice');
    $recipientEmail = sanitizeEmail($_POST['recipient_email'] ?? '');

    // Sanitize input data
    $full_name = sanitizeText($_POST['full_name']);
    $organization = sanitizeText($_POST['organization']);
    $amount = floatval($_POST['amount']);
    $currency = sanitizeText($_POST['currency'] ?? 'MAD');

    $allowedCurrencies = [
        'MAD' => ['symbol' => 'MAD', 'label' => 'Moroccan Dirham'],
        'USD' => ['symbol' => '$', 'label' => 'US Dollar'],
        'EUR' => ['symbol' => '€', 'label' => 'Euro'],
    ];

    if (!array_key_exists($currency, $allowedCurrencies)) {
        $currency = 'MAD';
    }

    $currencySymbol = $allowedCurrencies[$currency]['symbol'];
    $currencyLabel = $allowedCurrencies[$currency]['label'];

    $full_name_safe = htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8');
    $organization_safe = htmlspecialchars($organization, ENT_QUOTES, 'UTF-8');
    $currency_code_safe = htmlspecialchars($currency, ENT_QUOTES, 'UTF-8');
    $currency_label_safe = htmlspecialchars($currencyLabel, ENT_QUOTES, 'UTF-8');

    $organization_email_placeholder = 'contact@' . strtolower(str_replace(' ', '', $organization)) . '.com';
    $organization_email_placeholder_safe = htmlspecialchars($organization_email_placeholder, ENT_QUOTES, 'UTF-8');

    $formattedAmount = number_format($amount, 2);
    $amountDisplay = in_array($currencySymbol, ['$', '€'])
        ? $currencySymbol . $formattedAmount
        : $currencySymbol . ' ' . $formattedAmount;
    $amountDisplaySafe = htmlspecialchars($amountDisplay, ENT_QUOTES, 'UTF-8');
    $zeroAmountDisplay = in_array($currencySymbol, ['$', '€']) ? $currencySymbol . '0.00' : $currencySymbol . ' 0.00';
    $zeroAmountDisplaySafe = htmlspecialchars($zeroAmountDisplay, ENT_QUOTES, 'UTF-8');
    
    // Generate invoice data
    $invoice_number = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $current_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+30 days'));
    
    // Company information
    $company = [
        'name' => 'CASICAM Conference Services',
        'address' => 'ENSEM, University Hassan II',
        'city' => 'Casablanca, Morocco',
        'postal' => '20000',
        'phone' => '+212 522 230 686',
        'email' => 'contact@casicam.org',
        'website' => 'www.casicam.org',
        'tax_id' => 'MA-12345678'
    ];

    // Create HTML content for the invoice
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 15px;
                size: A4;
            }
            html, body {
                margin: 0;
                padding: 0;
                height: 100%;
            }
            body {
                font-family: "Arial", sans-serif;
                font-size: 11px;
                line-height: 1.4;
                color: #333;
            }
            .invoice-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 20px;
                page-break-after: avoid;
                page-break-before: avoid;
                page-break-inside: avoid;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 20px;
                border-bottom: 2px solid #2c3e50;
                padding-bottom: 12px;
            }
            .company-info {
                flex: 1;
            }
            .company-name {
                font-size: 20px;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 6px;
            }
            .company-details {
                color: #666;
                line-height: 1.4;
                font-size: 10px;
            }
            .invoice-title {
                text-align: right;
                flex: 1;
            }
            .invoice-title h1 {
                font-size: 28px;
                color: #e74c3c;
                margin: 0;
                font-weight: bold;
            }
            .invoice-meta {
                display: flex;
                justify-content: space-between;
                margin: 15px 0;
                gap: 20px;
            }
            .invoice-details, .client-details {
                flex: 1;
                background: #f8f9fa;
                padding: 12px;
                border-radius: 6px;
            }
            .section-title {
                font-size: 12px;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 8px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
                font-size: 10px;
            }
            .detail-label {
                font-weight: bold;
                color: #666;
            }
            .detail-value {
                color: #333;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
                background: white;
                border-radius: 6px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .items-table th {
                background: #2c3e50;
                color: white;
                padding: 8px;
                text-align: left;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 10px;
            }
            .items-table td {
                padding: 8px;
                border-bottom: 1px solid #eee;
                font-size: 10px;
            }
            .items-table tr:last-child td {
                border-bottom: none;
            }
            .items-table tr:nth-child(even) {
                background: #f8f9fa;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .total-section {
                margin-top: 15px;
                text-align: right;
            }
            .total-row {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 6px;
                font-size: 12px;
            }
            .total-label {
                width: 130px;
                text-align: right;
                padding-right: 15px;
                font-weight: bold;
            }
            .total-value {
                width: 90px;
                text-align: right;
                padding: 5px 10px;
                background: #f8f9fa;
                border-radius: 3px;
            }
            .grand-total {
                border-top: 2px solid #2c3e50;
                padding-top: 8px;
                margin-top: 8px;
            }
            .grand-total .total-label {
                font-size: 14px;
                color: #2c3e50;
            }
            .grand-total .total-value {
                font-size: 15px;
                font-weight: bold;
                background: #2c3e50;
                color: white;
            }
            .payment-info {
                margin-top: 15px;
                padding: 12px;
                background: #e8f5e8;
                border-radius: 6px;
                border-left: 4px solid #27ae60;
                font-size: 10px;
            }
            .payment-title {
                font-weight: bold;
                color: #27ae60;
                margin-bottom: 6px;
                font-size: 12px;
            }
            .footer {
                margin-top: 15px;
                padding-top: 10px;
                border-top: 1px solid #ddd;
                text-align: center;
                color: #666;
                font-size: 9px;
            }
            .status-badge {
                display: inline-block;
                padding: 5px 15px;
                background: #f39c12;
                color: white;
                border-radius: 20px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="header">
                <div class="company-info">
                    <div class="company-name">' . $company['name'] . '</div>
                    <div class="company-details">
                        ' . $company['address'] . '<br>
                        ' . $company['city'] . ' ' . $company['postal'] . '<br>
                        Phone: ' . $company['phone'] . '<br>
                        Email: ' . $company['email'] . '<br>
                        Website: ' . $company['website'] . '<br>
                        Tax ID: ' . $company['tax_id'] . '
                    </div>
                </div>
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                    <div class="status-badge">Pending</div>
                </div>
            </div>

            <div class="invoice-meta">
                <div class="invoice-details">
                    <div class="section-title">Invoice Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Invoice #:</span>
                        <span class="detail-value">' . $invoice_number . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Invoice Date:</span>
                        <span class="detail-value">' . date('F j, Y', strtotime($current_date)) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Due Date:</span>
                        <span class="detail-value">' . date('F j, Y', strtotime($due_date)) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Currency:</span>
                        <span class="detail-value">' . $currency_label_safe . ' (' . $currency_code_safe . ')</span>
                    </div>
                </div>

                <div class="client-details">
                    <div class="section-title">Bill To</div>
                    <div style="line-height: 1.8;">
                        <strong>' . $full_name_safe . '</strong><br>
                        ' . $organization_safe . '<br>
                        CASICAM 2026 Participant<br>
                        Email: ' . $organization_email_placeholder_safe . '
                    </div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Description</th>
                        <th style="width: 15%;" class="text-center">Qty</th>
                        <th style="width: 20%;" class="text-right">Unit Price</th>
                        <th style="width: 15%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>CASICAM 2026 Conference Services</strong><br>
                            <span style="color: #666; font-size: 11px;">Registration and participation fee for CASICAM 2026 conference</span>
                        </td>
                        <td class="text-center">1</td>
                        <td class="text-right">' . $amountDisplaySafe . '</td>
                        <td class="text-right">' . $amountDisplaySafe . '</td>
                    </tr>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <div class="total-label">Subtotal:</div>
                    <div class="total-value">' . $amountDisplaySafe . '</div>
                </div>
                <div class="total-row">
                    <div class="total-label">Tax (0%):</div>
                    <div class="total-value">' . $zeroAmountDisplaySafe . '</div>
                </div>
                <div class="total-row grand-total">
                    <div class="total-label">Total Amount:</div>
                    <div class="total-value">' . $amountDisplaySafe . '</div>
                </div>
            </div>

            <div class="payment-info">
                <div class="payment-title">Payment Information</div>
                <p style="margin: 0; line-height: 1.6;">
                    Payment is due within 30 days of invoice date. Please include invoice number <strong>' . $invoice_number . '</strong> 
                    with your payment. Late payments may incur additional charges of 2% per month.
                </p>
                <p style="margin: 10px 0 0 0; font-size: 11px; color: #666;">
                    <strong>Payment Methods:</strong> Bank Transfer, Credit Card, or Check payable to ' . $company['name'] . '
                </p>
            </div>

            <div class="footer">
                <p>Thank you for your business! For questions about this invoice, please contact us at ' . $company['email'] . '</p>
                <p style="margin-top: 10px;">This is a computer-generated invoice and is valid without signature.</p>
            </div>
        </div>
    </body>
    </html>';

    // Configure Dompdf
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    
    // Create Dompdf instance
    $dompdf = new Dompdf($options);
    
    // Load HTML content
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render the PDF
    $dompdf->render();
    
    // Generate filename
    $safeNameForFile = preg_replace('/[^A-Za-z0-9_-]+/', '_', $full_name);
    $safeNameForFile = trim($safeNameForFile, '_');
    if ($safeNameForFile === '') {
        $safeNameForFile = 'Recipient';
    }
    $filename = 'Invoice_' . $invoice_number . '_' . $safeNameForFile . '.pdf';

    if ($action === 'email_invoice') {
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            renderInvoiceStatusPage('Recipient Email Required', 'Please provide a valid recipient email address before sending the invoice.', false);
        }

        $pdfContent = $dompdf->output();
        $bodyRecipient = htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8');
        $emailSubject = "Invoice {$invoice_number} - CASICAM ({$currency})";
        $emailBody = "<p>Dear {$bodyRecipient},</p>"
            . "<p>Please find attached your invoice <strong>{$invoice_number}</strong> for CASICAM 2026 participation.</p>"
            . "<p>Total amount due: <strong>{$amountDisplaySafe}</strong> ({$currency_code_safe}).</p>"
            . "<p>If you have any questions, feel free to reach out to us at {$company['email']}.</p>"
            . "<p>Best regards,<br>CASICAM Organizing Committee</p>";

        $error = null;
        if (sendPdfAttachmentEmail($recipientEmail, $full_name, $emailSubject, $emailBody, $pdfContent, $filename, $error)) {
            renderInvoiceStatusPage('Invoice Sent', "The invoice was successfully emailed to {$recipientEmail}.", true);
        }

        $failureReason = $error ?: 'Unknown error.';
        renderInvoiceStatusPage('Email Delivery Failed', 'We were unable to send the invoice. Error: ' . $failureReason, false);
    }

    // Output the PDF to browser
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
    
} else {
    // If accessed directly without POST data, redirect to admin
    header('Location: admin.php');
    exit;
}


function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}
function logEvent($message)
{
    $logFile = __DIR__ . "/email_errors.log";
    $time = date("Y-m-d H:i:s");
    file_put_contents($logFile, "[$time] $message" . PHP_EOL, FILE_APPEND);
}
function sendEmail($mail, $recipient, $subject, $body, $name)
{
    $mail->setFrom("Contact@casicam.ma", 'Support CASICAM\'26');
    $mail->addAddress(trim($recipient), $name);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->msgHTML($body);

    if ($mail->send()) {
        //logEvent("SUCCESS: Email sent to $name <$recipient>");
        return true;
    } else {
        //logEvent("ERROR: Failed to send email to $name <$recipient> - Error: " . $mail->ErrorInfo);
        return false;
    }
}

$message = "";
if (isset($_POST["send"])) {
    $name = sanitizeInput($_POST["full-name"]);
    $email = sanitizeInput($_POST["email"]);
    $company = sanitizeInput($_POST["company"]);
    $subject = sanitizeInput($_POST["subject"] ?? "");
    $msg = sanitizeInput($_POST["message"]);
    $mail = new PHPMailer(true);
    $mail->CharSet = "UTF-8";
    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "ziko2319@gmail.com"; // your Gmail
        $mail->Password = "ezwroeywzfcofwdo"; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $body =
            "
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Company:</strong> $company</p>
                <p><strong>Message:</strong><br>" .
            nl2br($msg) .
            "</p>
            ";
        if (sendEmail($mail, "zakaria.karkouri@outlook.com", $subject, $body, $name)) {
            $message = "✅ Message sent successfully!";
        } else {
            $message = "❌ Failed to send message.";
        }
    } catch (Exception $e) {
        //logEvent("ERROR: Failed to send email - Error: {$mail->ErrorInfo}");
        $message = "❌ Error: {$mail->ErrorInfo}";
    }
}
?>