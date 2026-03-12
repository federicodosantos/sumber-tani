<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota #{{ $invoice->id }}</title>
    <style>
        /* Pengaturan Margin Halaman (Padding Kanan-Kiri Luar) */
        @page {
            margin: 1.5cm 8cm;
        }

        * {
            margin: 0;
            padding: 2;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.6;
            background-color: #fff;
        }

        /* Container utama untuk padding tambahan jika diperlukan */
        .container {
            width: 100%;
        }

        /* Header Styling */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #444;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            color: #555;
            margin: 1px 0;
        }

        /* Info Section (Customer & Date) */
        .info-section {
            margin-bottom: 25px;
            display: table;
            width: 100%;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            vertical-align: top;
            padding: 3px 0;
        }

        .info-table .label {
            font-weight: bold;
            width: 80px;
            color: #555;
        }

        .info-table .separator {
            width: 15px;
            text-align: center;
        }

        /* Nomor Nota */
        .nota-number-box {
            margin-bottom: 15px;
            padding: 8px 0;
            border-bottom: 1px double #ccc;
        }

        .nota-title {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #f8f9fa;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 10px 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Summary Section */
        .summary-wrapper {
            width: 100%;
            margin-top: 10px;
        }

        .summary-table {
            float: right;
            width: 250px;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 8px;
        }

        .summary-table .label-col {
            text-align: right;
            color: #555;
        }

        .summary-table .value-col {
            text-align: right;
            font-weight: bold;
        }

        .total-row td {
            border-top: 1px solid #333;
            padding-top: 8px;
            font-size: 14px;
            color: #000;
        }

        .debt-row td {
            color: #d32f2f;
        }

        .paid-row td {
            color: #2e7d32;
        }

        /* Signatures */
        .signatures {
            margin-top: 50px;
            width: 100%;
            clear: both;
        }

        .signatures td {
            width: 50%;
            text-align: center;
        }

        .sign-box {
            display: inline-block;
            width: 150px;
        }

        .sign-space {
            height: 60px;
        }

        .sign-line {
            border-top: 1px solid #333;
            margin-top: 5px;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>TOKO SUMBERTANI</h1>
            <p>Jl. Trans Sulawesi, Motolohu, Kec. Randangan, Kab. Pohuwato, Gorontalo 96469</p>
            <p>Telp: <strong>+62 813-5674-5129</strong> | Email: sumbertani0209@gmail.com</p>
        </div>

        {{-- Customer Info --}}
        <div class="info-section">
            <table class="info-table">
                <tr>
                    <td style="width: 60%;">
                        <table>
                            <tr>
                                <td class="label">Pelanggan</td>
                                <td class="separator">:</td>
                                <td><strong>{{ $customer->name }}</strong></td>
                            </tr>
                            <tr>
                                <td class="label">Alamat</td>
                                <td class="separator">:</td>
                                <td>{{ $customer->address }}</td>
                            </tr>
                            <tr>
                                <td class="label">No. HP</td>
                                <td class="separator">:</td>
                                <td>{{ $customer->phone_number }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 40%; text-align: right; vertical-align: bottom;">
                        <div style="font-size: 10px; color: #666;">
                            Tanggal Cetak: {{ $invoice->created_at->translatedFormat('d F Y') }}<br>
                            Waktu: {{ $invoice->created_at->translatedFormat('H:i') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="nota-number-box">
            <span class="nota-title">NOTA PENJUALAN #{{ $invoice->id }}</span>
            @if ($transaction)
                <span style="float: right; color: #777;">ID Transaksi: {{ $transaction->id }}</span>
            @endif
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 8%;">QTY</th>
                    <th style="width: 52%;">DESKRIPSI BARANG</th>
                    <th class="text-right" style="width: 20%;">HARGA SATUAN</th>
                    <th class="text-right" style="width: 20%;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($details as $detail)
                    <tr>
                        <td class="text-center">{{ $detail->quantity }}</td>
                        <td>{{ $detail->product->name ?? 'Produk tidak diketahui' }}</td>
                        <td class="text-right">Rp {{ number_format($detail->product_price, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="padding: 20px; color: #999;">
                            Tidak ada data transaksi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Summary --}}
        <div class="summary-wrapper">
            <table class="summary-table">
                @if ($transaction && $transaction->discount > 0)
                    <tr>
                        <td class="label-col">Subtotal</td>
                        <td class="value-col">Rp
                            {{ number_format($transaction->total_price + $transaction->discount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">Diskon</td>
                        <td class="value-col">-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="label-col">TOTAL</td>
                    <td class="value-col">Rp
                        {{ number_format($transaction ? $transaction->total_price : 0, 0, ',', '.') }}</td>
                </tr>
                @if ($invoice->debts > 0)
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
        </div>

        {{-- Signatures --}}
        <table class="signatures">
            <tr>
                <td>
                    <div class="sign-box">
                        <p>Tanda Terima,</p>
                        <div class="sign-space"></div>
                        <div class="sign-line"></div>
                        <p>( {{ $customer->name }} )</p>
                    </div>
                </td>
                <td>
                    <div class="sign-box">
                        <p>Hormat Kami,</p>
                        <div class="sign-space"></div>
                        <div class="sign-line"></div>
                        <p>( Admin Sumbertani )</p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Terima kasih atas kepercayaan Anda berbelanja di Toko Sumbertani.<br>
            <em>Nota ini sah sebagai bukti transaksi yang valid.</em>
        </div>
    </div>
</body>

</html>
