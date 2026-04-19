<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Nota #{{ $invoice->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Helvetica', 'Arial', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            line-height: 1.6;
            padding: 24px;
        }

        /* Top Bar */
        .top-bar {
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #1e293b;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ABD36F;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .print-btn:hover {
            background: #8AB763;
        }

        /* Invoice Card */
        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        /* Header */
        .invoice-header {
            background: linear-gradient(135deg, #8AB763 0%, #ABD36F 100%);
            color: #fff;
            padding: 32px 40px;
            text-align: center;
        }

        .invoice-header h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .invoice-header p {
            font-size: 12px;
            opacity: 0.85;
            margin: 2px 0;
        }

        /* Info Section */
        .invoice-info {
            display: flex;
            justify-content: space-between;
            padding: 28px 40px;
            border-bottom: 1px solid #e2e8f0;
            gap: 20px;
            flex-wrap: wrap;
        }

        .info-block h3 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .info-block p {
            font-size: 14px;
            margin: 3px 0;
        }

        .info-block .name {
            font-weight: 700;
            color: #0f172a;
            font-size: 16px;
        }

        /* Nota Number Box */
        .nota-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .nota-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
        }

        .nota-meta {
            font-size: 13px;
            color: #64748b;
        }

        /* Items Table */
        .invoice-body {
            padding: 0 40px 28px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        .items-table thead th {
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        .items-table tbody td {
            padding: 14px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            vertical-align: middle;
        }

        .items-table tbody tr:hover {
            background: #fafbfc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .empty-row td {
            text-align: center;
            padding: 32px;
            color: #94a3b8;
            font-style: italic;
        }

        /* Summary */
        .invoice-summary {
            padding: 0 40px 32px;
            display: flex;
            justify-content: flex-end;
        }

        .summary-table {
            width: 280px;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 6px 10px;
            font-size: 14px;
        }

        .summary-table .label-col {
            text-align: right;
            color: #64748b;
        }

        .summary-table .value-col {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-table .total-row td {
            border-top: 2px solid #334155;
            padding-top: 12px;
            font-size: 18px;
        }

        .summary-table .debt-row td {
            color: #dc2626;
        }

        .summary-table .paid-row td {
            color: #16a34a;
        }

        /* Signatures */
        .signatures-section {
            display: flex;
            justify-content: space-between;
            padding: 40px 60px 32px;
            border-top: 1px solid #e2e8f0;
        }

        .sign-block {
            text-align: center;
        }

        .sign-label {
            font-size: 13px;
            color: #64748b;
        }

        .sign-space {
            height: 70px;
        }

        .sign-line {
            width: 160px;
            border-top: 1px solid #334155;
            margin: 0 auto 4px;
        }

        .sign-name {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
        }

        /* Footer */
        .invoice-footer {
            text-align: center;
            padding: 20px 40px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 12px;
            color: #94a3b8;
            border-radius: 0 0 16px 16px;
        }

        /* Print Styles */
        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .top-bar {
                display: none !important;
            }

            .invoice-card {
                box-shadow: none;
                border-radius: 0;
            }

            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .items-table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .invoice-info {
                flex-direction: column;
                padding: 20px 20px;
            }

            .invoice-header,
            .nota-box,
            .invoice-body,
            .invoice-summary,
            .signatures-section,
            .invoice-footer {
                padding-left: 20px;
                padding-right: 20px;
            }

            .signatures-section {
                flex-direction: column;
                gap: 32px;
                align-items: center;
            }
        }
    </style>
</head>

<body>
    {{-- Top Bar --}}
    <div class="top-bar">
        <a href="{{ route('customer-r2.show', $customer->id) }}" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" style="width: 16px; height: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Kembali
        </a>
        <button class="print-btn" onclick="ThermalPrinter.printR2Invoice('{{ $invoice->id }}')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" style="width: 16px; height: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
            </svg>
            Cetak Struk
        </button>
    </div>

    {{-- Invoice Card --}}
    <div class="invoice-card">
        @include('customer-r2.partials._invoice-content', [
            'invoice' => $invoice, 
            'customer' => $customer, 
            'transaction' => $transaction, 
            'details' => $details
        ])
    </div>
    <script src="{{ asset('qz/qz-tray.js') }}"></script>
    <script src="{{ asset('qz/qz-config.js') }}"></script>
    <script src="{{ asset('qz/printer-utils.js') }}"></script>
    <script src="{{ asset('qz/layouts/cashier-layout.js') }}"></script>
    <script src="{{ asset('qz/layouts/r2-layout.js') }}"></script>
    <script src="{{ asset('qz/printer-main.js') }}"></script>
</body>

</html>
