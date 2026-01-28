<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Receipt - Payment #{{ $payment->id }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #3B60C9;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #3B60C9;
            font-size: 24px;
            margin: 10px 0;
            font-weight: bold;
        }
        .header h2 {
            color: #666;
            font-size: 16px;
            margin: 5px 0;
            font-weight: normal;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #3B60C9;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .receipt-info {
            margin: 25px 0;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .info-label {
            display: table-cell;
            width: 35%;
            font-weight: bold;
            color: #555;
            vertical-align: top;
        }
        .info-value {
            display: table-cell;
            width: 65%;
            color: #333;
        }
        .amount-section {
            background-color: #f5f5f5;
            border: 2px solid #3B60C9;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        .amount-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .amount-value {
            font-size: 28px;
            font-weight: bold;
            color: #3B60C9;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
        }
        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 20px;
        }
        .signature-line {
            border-top: 2px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
        .receipt-number {
            text-align: right;
            color: #666;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .date-section {
            text-align: right;
            color: #666;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>JAHAPUR SECONDARY SCHOOL</h1>
        <h2>ALUMNI ASSOCIATION</h2>
    </div>

    <div class="receipt-title">Money Receipt</div>

    <div class="receipt-number">
        Receipt No: {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
    </div>

    <div class="date-section">
        Date: {{ $payment->approved_at ? \Carbon\Carbon::parse($payment->approved_at)->setTimezone('Asia/Dhaka')->format('d F Y') : \Carbon\Carbon::now()->setTimezone('Asia/Dhaka')->format('d F Y') }}
    </div>

    <div class="receipt-info">
        <div class="info-row">
            <div class="info-label">Member ID:</div>
            <div class="info-value">{{ $payment->member_id ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Name:</div>
            <div class="info-value">{{ $payment->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Address:</div>
            <div class="info-value">{{ $payment->address }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Mobile Number:</div>
            <div class="info-value">{{ $payment->mobile_number }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Payment Purpose:</div>
            <div class="info-value">{{ $paymentPurposeLabel }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Payment Method:</div>
            <div class="info-value">{{ $payment->payment_method ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="amount-section">
        <div class="amount-label">Amount Received</div>
        <div class="amount-value">BDT {{ number_format($payment->payment_amount, 2) }}</div>
    </div>

    <div class="footer">
        <p style="text-align: center; color: #666; font-size: 11px;">
            This is a computer-generated receipt. No signature required.
        </p>
        {{-- @if($payment->approved_by)
        <p style="text-align: center; color: #666; font-size: 11px; margin-top: 10px;">
            Approved by: {{ $payment->approvedBy->name ?? 'Admin' }}
        </p>
        @endif --}}
    </div>
</body>
</html>
