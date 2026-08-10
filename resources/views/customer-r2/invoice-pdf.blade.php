<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
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
        .total-table { width: 300px; float: right; border-collapse: collapse; }
        .total-table td { padding: 5px 8px; text-align: right; font-size: 12px; }
        .total-row { font-weight: bold; border-top: 2px solid #333; font-size: 14px; }
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
                <div class="info-title">Pelanggan</div>
                <div style="font-size: 14px; font-weight: bold;">{{ $customer->name }}</div>
                <div>{{ $customer->address }}</div>
                <div>{{ $customer->phone_number }}</div>
            </td>
            <td style="width: 40%; text-align: right;">
                <div class="info-title">
                    @if ($invoice->type === 'debt_payment')
                        BUKTI PEMBAYARAN HUTANG
                    @elseif ($invoice->type === 'purchasement' && !$invoice->transaction)
                        PENCATATAN HUTANG
                    @else
                        NOTA PENJUALAN
                    @endif
                </div>
                <div style="font-weight: bold; font-size: 13px;">{{ $invoice->inv_code ?? '-' }}</div>
                <div>{{ $invoice->created_at->translatedFormat('d F Y, H:i') }}</div>
                <div>
                    @if ($invoice->type === 'purchasement' && $transaction)
                        {{ strtoupper($transaction->payment_method) }}
                    @elseif ($invoice->type === 'debt_payment' && $invoice->debtPayment)
                        {{ strtoupper($invoice->debtPayment->payment_method) }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if ($invoice->type === 'debt_payment')
        @php
            $debtPayment = $invoice->debtPayment;
            $paymentDetails = $debtPayment ? $debtPayment->details : collect();
        @endphp

        <table class="items-table">
            <thead>
                <tr>
                    <th>Invoice Asal</th>
                    <th class="text-right">Hutang Awal</th>
                    <th class="text-right">Dibayar</th>
                    <th class="text-right">Sisa Hutang</th>
                </tr>
            </thead>
            <tbody>
                @forelse($paymentDetails as $detail)
                    <tr>
                        <td>{{ $detail->invoice->inv_code ?? 'Invoice #' . $detail->invoice_id }}</td>
                        <td class="text-right">Rp {{ number_format($detail->debt_before, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #16a34a; font-weight: bold;">Rp {{ number_format($detail->amount_paid, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: bold; {{ $detail->debt_after > 0 ? 'color: #dc2626;' : 'color: #16a34a;' }}">Rp {{ number_format($detail->debt_after, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="font-style: italic; color: #999;">Tidak ada rincian pembayaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="total-table">
            <tr class="total-row">
                <td style="text-transform: uppercase;">Total Bayar</td>
                <td style="color: #16a34a;">Rp {{ number_format($debtPayment?->amount ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    @elseif ($invoice->type === 'purchasement' && !$invoice->transaction)
        @php
            $paidDebtSum = $invoice->debtPaymentDetails?->sum('amount_paid') ?? 0;
            $originalDebt = $invoice->debts + $paidDebtSum;
        @endphp
        <div style="margin-bottom: 20px; padding: 10px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px;">
            <div style="font-size: 10px; font-weight: bold; color: #666; text-transform: uppercase; margin-bottom: 4px;">Keterangan</div>
            <div style="font-size: 12px; color: #333; white-space: pre-wrap;">{{ $invoice->note ?: '(tanpa keterangan)' }}</div>
        </div>

        <table class="total-table">
            <tr>
                <td>Nominal Hutang</td>
                <td>Rp {{ number_format($originalDebt, 0, ',', '.') }}</td>
            </tr>
            @if ($paidDebtSum > 0)
                <tr>
                    <td>Sudah Dibayar</td>
                    <td style="color: #16a34a;">-Rp {{ number_format($paidDebtSum, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td style="text-transform: uppercase;">Sisa Hutang</td>
                <td style="{{ $invoice->debts > 0 ? 'color: #dc2626;' : 'color: #16a34a;' }}">Rp {{ number_format($invoice->debts, 0, ',', '.') }}</td>
            </tr>
        </table>
    @else
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
                @forelse($details as $detail)
                    <tr>
                        <td>{{ $detail->product->name ?? 'Produk tidak diketahui' }}</td>
                        <td class="text-right">Rp {{ number_format($detail->product_price, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $detail->quantity }}</td>
                        <td class="text-right">Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center" style="font-style: italic; color: #999;">Tidak ada data transaksi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="total-table">
            @if ($transaction && $transaction->discount > 0)
                <tr>
                    <td>Subtotal</td>
                    <td>Rp {{ number_format($transaction->total_price + $transaction->discount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Diskon</td>
                    <td>-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL</td>
                <td>Rp {{ number_format($transaction ? $transaction->total_price : 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div>Tanda Terima,</div>
                <div class="sign-line"></div>
                <div>( {{ $customer->name }} )</div>
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
