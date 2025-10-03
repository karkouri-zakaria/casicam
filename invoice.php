<?php
// Include dompdf library
require_once './dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check if form was submitted
if ($_POST && isset($_POST['full_name']) && isset($_POST['amount']) && isset($_POST['organization'])) {
    
    // Sanitize input data
    $full_name = htmlspecialchars($_POST['full_name']);
    $organization = htmlspecialchars($_POST['organization']);
    $amount = floatval($_POST['amount']);
    
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
        'email' => 'billing@casicam.org',
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
                margin: 20px;
                size: A4;
            }
            body {
                font-family: "Arial", sans-serif;
                font-size: 12px;
                line-height: 1.5;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .invoice-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 40px;
                border-bottom: 3px solid #2c3e50;
                padding-bottom: 20px;
            }
            .company-info {
                flex: 1;
            }
            .company-name {
                font-size: 24px;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 10px;
            }
            .company-details {
                color: #666;
                line-height: 1.6;
            }
            .invoice-title {
                text-align: right;
                flex: 1;
            }
            .invoice-title h1 {
                font-size: 36px;
                color: #e74c3c;
                margin: 0;
                font-weight: bold;
            }
            .invoice-meta {
                display: flex;
                justify-content: space-between;
                margin: 30px 0;
                gap: 40px;
            }
            .invoice-details, .client-details {
                flex: 1;
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
            }
            .section-title {
                font-size: 14px;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 15px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
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
                margin: 30px 0;
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .items-table th {
                background: #2c3e50;
                color: white;
                padding: 15px;
                text-align: left;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 1px;
                font-size: 11px;
            }
            .items-table td {
                padding: 15px;
                border-bottom: 1px solid #eee;
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
                margin-top: 30px;
                text-align: right;
            }
            .total-row {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 10px;
                font-size: 14px;
            }
            .total-label {
                width: 150px;
                text-align: right;
                padding-right: 20px;
                font-weight: bold;
            }
            .total-value {
                width: 100px;
                text-align: right;
                padding: 8px 15px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .grand-total {
                border-top: 2px solid #2c3e50;
                padding-top: 15px;
                margin-top: 15px;
            }
            .grand-total .total-label {
                font-size: 16px;
                color: #2c3e50;
            }
            .grand-total .total-value {
                font-size: 18px;
                font-weight: bold;
                background: #2c3e50;
                color: white;
            }
            .payment-info {
                margin-top: 40px;
                padding: 20px;
                background: #e8f5e8;
                border-radius: 8px;
                border-left: 5px solid #27ae60;
            }
            .payment-title {
                font-weight: bold;
                color: #27ae60;
                margin-bottom: 10px;
                font-size: 14px;
            }
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: center;
                color: #666;
                font-size: 11px;
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
                        <span class="detail-value">USD</span>
                    </div>
                </div>

                <div class="client-details">
                    <div class="section-title">Bill To</div>
                    <div style="line-height: 1.8;">
                        <strong>' . $full_name . '</strong><br>
                        ' . $organization . '<br>
                        CASICAM 2026 Participant<br>
                        Email: contact@' . strtolower(str_replace(' ', '', $organization)) . '.com
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
                        <td class="text-right">$' . number_format($amount, 2) . '</td>
                        <td class="text-right">$' . number_format($amount, 2) . '</td>
                    </tr>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    <div class="total-label">Subtotal:</div>
                    <div class="total-value">$' . number_format($amount, 2) . '</div>
                </div>
                <div class="total-row">
                    <div class="total-label">Tax (0%):</div>
                    <div class="total-value">$0.00</div>
                </div>
                <div class="total-row grand-total">
                    <div class="total-label">Total Amount:</div>
                    <div class="total-value">$' . number_format($amount, 2) . '</div>
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
    $filename = 'Invoice_' . $invoice_number . '_' . str_replace(' ', '_', $full_name) . '.pdf';
    
    // Output the PDF to browser
    $dompdf->stream($filename, ['Attachment' => true]);
    
} else {
    // If accessed directly without POST data, redirect to admin
    header('Location: admin.php');
    exit;
}
?>