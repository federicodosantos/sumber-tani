<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @php use Illuminate\Support\Number; @endphp
    <title>Nota Transaksi #{{ $transaction->id }}</title>
    <style>
        body { font-family: helvetica, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #ddd; }
        .header h1 { font-size: 20px; margin: 0; color: #8AB763; }
        .header p { margin: 2px 0; font-size: 11px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { vertical-align: top; }
        .info-title { font-size: 10px; font-weight: bold; color: #666; text-transform: uppercase; }
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items-table th, table.items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table.items-table th { background-color: #f5f5f5; font-size: 10px; text-transform: uppercase; }
        table.items-table td { font-size: 11px; }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .total-table { width: 320px; float: right; border-collapse: collapse; }
        .total-table td { padding: 5px 8px; text-align: right; font-size: 12px; }
        .total-row { font-weight: bold; border-top: 2px solid #333; font-size: 14px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-unpaid { background: #fee2e2; color: #b91c1c; }
        .signatures { width: 100%; margin-top: 50px; clear: both; }
        .signatures td { width: 50%; text-align: center; }
        .sign-line { width: 150px; border-top: 1px solid #333; margin: 60px auto 5px auto; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TOKO SUMBERTANI</h1>
        <p>Jl. Trans Sulawesi, Motolohu, Kec. Randangan, Kab. Pohuwato, Gorontalo 96469</p>
        <p>Telp: +62 813-5674-5129 | Email: sumbertani0209@gmail.com</p>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 60%;">
                <div class="info-title">Nota Penjualan</div>
                <div style="font-size: 16px; font-weight: bold;">#{{ $transaction->id }}</div>
                <div>{{ $transaction->created_at->translatedFormat('d F Y, H:i') }}</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="info-title">Metode Pembayaran</div>
                <div style="font-weight: bold; font-size: 13px;">{{ strtoupper($transaction->payment_method ?? '-') }}</div>
                <div style="margin-top: 6px;">
                    <span class="status-badge {{ $transaction->is_paid ? 'badge-paid' : 'badge-unpaid' }}">
                        {{ $transaction->is_paid ? 'Sudah Lunas' : 'Belum Lunas' }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th class="text-right">Harga</th>
                <th class="text-center">Jumlah</th>
                <th class="text-right">Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaction->transactionDetails as $detail)
                <tr>
                    <td>{{ $detail->product->name ?? 'Produk tidak diketahui' }}</td>
                    <td class="text-right">Rp {{ Number::format((float) $detail->product_price, null, 3, 'id') }}</td>
                    <td class="text-center">{{ Number::format((float) $detail->quantity, null, 3, 'id') }}</td>
                    <td class="text-right">Rp {{ Number::format((float) $detail->total_price, null, 3, 'id') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="font-style: italic; color: #999;">Tidak ada data transaksi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="total-table">
        @if ($transaction->discount > 0)
            <tr>
                <td>Subtotal</td>
                <td>Rp {{ Number::format((float) $transaction->total_price + (float) $transaction->discount, null, 3, 'id') }}</td>
            </tr>
            <tr>
                <td>Diskon</td>
                <td>-Rp {{ Number::format((float) $transaction->discount, null, 3, 'id') }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL</td>
            <td>Rp {{ Number::format((float) $transaction->total_price, null, 3, 'id') }}</td>
        </tr>
        @if ($transaction->payment_method === 'Cash' && $transaction->cash_received !== null)
            <tr>
                <td>Tunai</td>
                <td>Rp {{ Number::format((float) $transaction->cash_received, null, 3, 'id') }}</td>
            </tr>
            <tr>
                <td>Kembalian</td>
                <td>Rp {{ Number::format((float) ($transaction->change_amount ?? 0), null, 3, 'id') }}</td>
            </tr>
        @endif
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div>Tanda Terima,</div>
                <div class="sign-line"></div>
                <div>( Pelanggan )</div>
            </td>
            <td>
                <div>Hormat Kami,</div>
                <div class="sign-line"></div>
                <div>( Admin Sumbertani )</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Terima kasih atas kepercayaan Anda berbelanja di Toko Sumbertani.<br>
        <i>Nota ini sah sebagai bukti transaksi yang valid.</i>
    </div>
</body>
</html>
