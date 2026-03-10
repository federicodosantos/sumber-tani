<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota #{{ $invoice->id }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 2px;
        }

        .header p {
            font-size: 10px;
            color: #333;
        }

        .info-section {
            margin-bottom: 16px;
        }

        .info-table {
            width: 100%;
        }

        .info-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .info-table .label {
            font-weight: bold;
            width: 100px;
        }

        .info-table .separator {
            width: 10px;
            text-align: center;
        }

        .nota-number {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 12px;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .items-table th {
            background-color: #f3f3f3;
            border-top: 2px solid #111;
            border-bottom: 2px solid #111;
            padding: 6px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #111;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 24px;
        }

        .summary-table td {
            padding: 3px 8px;
            font-size: 11px;
        }

        .summary-table .label-col {
            text-align: right;
            font-weight: bold;
            width: 80%;
        }

        .summary-table .value-col {
            text-align: right;
            width: 20%;
        }

        .summary-table .total-row td {
            border-top: 2px solid #111;
            font-size: 13px;
            font-weight: bold;
            padding-top: 6px;
        }

        .summary-table .debt-row td {
            color: #b91c1c;
            font-weight: bold;
        }

        .summary-table .paid-row td {
            color: #15803d;
            font-weight: bold;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }

        .signatures .sign-line {
            margin-top: 50px;
            border-top: 1px solid #111;
            display: inline-block;
            width: 120px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>

<body>

    {{-- Header --}}
    <div class="header">
        <h1>TOKO SUMBERTANI</h1>
        <p>Jl. Trans Sulawesi, Motolohu, Kec. Randangan,</p>
        <p>Kab. Pohuwato, Gorontalo 96469</p>
        <p>Telp: +6281356745129 | Email: sumbertani0209@gmail.com</p>
    </div>

    {{-- Customer & Invoice Info --}}
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Kepada</td>
                <td class="separator">:</td>
                <td>{{ $customer->name }}</td>
                <td style="text-align: right; font-size: 10px; color: #666;">
                    {{ $invoice->created_at->translatedFormat('d M Y, H:i') }}
                </td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="separator">:</td>
                <td colspan="2">{{ $customer->address }}</td>
            </tr>
            <tr>
                <td class="label">No. HP</td>
                <td class="separator">:</td>
                <td colspan="2">{{ $customer->phone_number }}</td>
            </tr>
        </table>
    </div>

    {{-- Nota Number --}}
    <div class="nota-number">
        NOTA NO. {{ $invoice->id }}
        @if($transaction)
            <span style="float: right; font-size: 10px; font-weight: normal; color: #666;">
                Transaksi #{{ $transaction->id }}
            </span>
        @endif
    </div>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;" class="text-center">Banyaknya</th>
                <th style="width: 50%;">Nama Barang</th>
                <th style="width: 20%;" class="text-right">Harga</th>
                <th style="width: 20%;" class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @if($transaction && $details->count() > 0)
                @foreach($details as $detail)
                    <tr>
                        <td class="text-center">{{ $detail->quantity }}</td>
                        <td>{{ $detail->product->name ?? 'Produk tidak diketahui' }}</td>
                        <td class="text-right">Rp {{ number_format($detail->product_price, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" style="text-align: center; color: #999; padding: 16px;">
                        Data transaksi tidak tersedia
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Summary --}}
    <table class="summary-table">
        @if($transaction && $transaction->discount > 0)
            <tr>
                <td class="label-col">Subtotal</td>
                <td class="value-col">Rp {{ number_format($transaction->total_price + $transaction->discount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label-col">Diskon</td>
                <td class="value-col">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td class="label-col">Jumlah Rp.</td>
            <td class="value-col">Rp {{ number_format($transaction ? $transaction->total_price : 0, 0, ',', '.') }}</td>
        </tr>
        @if($invoice->debts > 0)
            <tr class="debt-row">
                <td class="label-col">Sisa Hutang</td>
                <td class="value-col">Rp {{ number_format($invoice->debts, 0, ',', '.') }}</td>
            </tr>
        @else
            <tr class="paid-row">
                <td class="label-col">Status</td>
                <td class="value-col">LUNAS</td>
            </tr>
        @endif
    </table>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>
                <p>Tanda Terima</p>
                <div class="sign-line"></div>
            </td>
            <td>
                <p>Hormat Kami,</p>
                <div class="sign-line"></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->translatedFormat('d M Y, H:i') }}
    </div>

</body>

</html>
