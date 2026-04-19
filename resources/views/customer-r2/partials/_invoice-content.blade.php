@props(['invoice', 'customer', 'transaction' => null, 'details' => collect()])

<div class="invoice-content-wrapper bg-white">
    {{-- Header --}}
    <div class="invoice-header p-6 text-center text-white" style="background: linear-gradient(135deg, #8AB763 0%, #ABD36F 100%);">
        <h1 class="text-xl font-bold uppercase tracking-widest">Toko Sumbertani</h1>
        <p class="mt-1 text-[12px] text-gray-800">Jl. Trans Sulawesi, Motolohu, Kec. Randangan, Kab. Pohuwato, Gorontalo 96469</p>
        <p class="text-[12px] text-gray-800">Telp: <strong>+62 813-5674-5129</strong> | Email: sumbertani0209@gmail.com</p>
    </div>

    {{-- Customer & Date Info --}}
    <div class="invoice-info flex justify-between border-b border-gray-200 p-6 gap-4 flex-wrap">
        <div class="info-block">
            <h3 class="mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-400">Pelanggan</h3>
            <p class="text-sm font-bold text-gray-900">{{ $customer->name }}</p>
            <p class="text-xs text-gray-600">{{ $customer->address }}</p>
            <p class="text-xs text-gray-600">{{ $customer->phone_number }}</p>
        </div>
        <div class="info-block text-right">
            <h3 class="mb-1 text-[9px] font-bold uppercase tracking-widest text-gray-400">Detail Invoice</h3>
            <p class="text-xs text-gray-700"><strong>{{ $invoice->inv_code ?? '-' }}</strong></p>
            <p class="text-xs text-gray-700">{{ $invoice->created_at->translatedFormat('d F Y') }}, {{ $invoice->created_at->translatedFormat('H:i') }}</p>
            @if ($invoice->type === 'purchasement' && $transaction)
                <p class="text-xs text-gray-700">{{ strtoupper($transaction->payment_method) }}</p>
            @elseif ($invoice->type === 'debt_payment' && $invoice->debtPayment)
                <p class="text-xs text-gray-700">{{ strtoupper($invoice->debtPayment->payment_method) }}</p>
            @endif
        </div>
    </div>

    {{-- Nota Number --}}
    <div class="nota-box flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-3">
        @if ($invoice->type === 'debt_payment')
            <span class="text-sm font-bold text-gray-900">BUKTI PEMBAYARAN HUTANG</span>
        @else
            <span class="text-sm font-bold text-gray-900">NOTA PENJUALAN</span>
        @endif
        <span class="text-[10px] text-gray-500 uppercase tracking-wider">{{ $invoice->inv_code ?? 'ID: ' . $invoice->id }}</span>
    </div>

    @if ($invoice->type === 'debt_payment')
        {{-- ==============================
             DEBT PAYMENT CONTENT
             ============================== --}}
        @php
            $debtPayment = $invoice->debtPayment;
            $paymentDetails = $debtPayment ? $debtPayment->details : collect();
        @endphp

        <div class="invoice-body px-6 py-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-y border-gray-200 bg-gray-50 text-[9px] font-bold uppercase tracking-widest text-gray-500">
                        <th class="p-2 text-left w-[30%]">Invoice Asal</th>
                        <th class="p-2 text-right w-[25%]">Hutang Awal</th>
                        <th class="p-2 text-right w-[20%]">Dibayar</th>
                        <th class="p-2 text-right w-[25%]">Sisa Hutang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($paymentDetails as $detail)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-2.5 text-xs font-medium text-gray-700">
                                {{ $detail->invoice->inv_code ?? 'Invoice #' . $detail->invoice_id }}
                            </td>
                            <td class="p-2.5 text-right text-xs text-gray-700">
                                Rp {{ number_format($detail->debt_before, 0, ',', '.') }}
                            </td>
                            <td class="p-2.5 text-right text-xs font-bold text-green-600">
                                Rp {{ number_format($detail->amount_paid, 0, ',', '.') }}
                            </td>
                            <td class="p-2.5 text-right text-xs font-bold {{ $detail->debt_after > 0 ? 'text-red-600' : 'text-green-600' }}">
                                Rp {{ number_format($detail->debt_after, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-xs italic text-gray-400">Tidak ada rincian pembayaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Payment Summary --}}
        <div class="invoice-summary flex justify-end px-6 pb-6">
            <table class="w-[240px] border-collapse">
                <tr class="total-row border-t border-gray-800">
                    <td class="p-2 px-2 text-right text-xs font-bold text-gray-900 uppercase">TOTAL BAYAR</td>
                    <td class="p-2 px-2 text-right text-base font-bold text-green-600">
                        Rp {{ number_format($debtPayment?->amount ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            </table>
        </div>
    @else
        {{-- ==============================
             PURCHASE CONTENT (original)
             ============================== --}}
        <div class="invoice-body px-6 py-4">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-y border-gray-200 bg-gray-50 text-[9px] font-bold uppercase tracking-widest text-gray-500">
                        <th class="p-2 text-left w-[45%]">Nama</th>
                        <th class="p-2 text-left w-[25%]">Harga</th>
                        <th class="p-2 text-center w-[5%]">Jumlah</th>
                        <th class="p-2 text-right w-[25%]">Sub Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($details as $detail)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-2.5 text-xs text-gray-700">{{ $detail->product->name ?? 'Produk tidak diketahui' }}</td>
                            <td class="p-2.5 text-xs text-gray-700">Rp {{ number_format($detail->product_price, 0, ',', '.') }}</td>
                            <td class="p-2.5 text-center text-xs text-gray-700">{{ $detail->quantity }}</td>
                            <td class="p-2.5 text-right text-xs font-bold text-gray-900">Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-xs italic text-gray-400">Tidak ada data transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div class="invoice-summary flex justify-end px-6 pb-6">
            <table class="w-[240px] border-collapse">
                @if ($transaction && $transaction->discount > 0)
                    <tr>
                        <td class="p-1 px-2 text-right text-xs text-gray-500">Subtotal</td>
                        <td class="p-1 px-2 text-right text-xs font-bold text-gray-900">Rp {{ number_format($transaction->total_price + $transaction->discount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="p-1 px-2 text-right text-xs text-gray-500">Diskon</td>
                        <td class="p-1 px-2 text-right text-xs font-bold text-gray-900">-Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row border-t border-gray-800">
                    <td class="p-2 px-2 text-right text-xs font-bold text-gray-900 uppercase">TOTAL</td>
                    <td class="p-2 px-2 text-right text-base font-bold text-gray-900">Rp {{ number_format($transaction ? $transaction->total_price : 0, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    @endif

    {{-- Signatures --}}
    <div class="signatures-section flex justify-between border-t border-gray-200 p-6 px-10">
        <div class="sign-block text-center">
            <p class="text-xs text-gray-500">Tanda Terima,</p>
            <div class="h-[50px]"></div>
            <div class="mx-auto mb-1 w-[120px] border-t border-gray-800"></div>
            <p class="text-xs font-bold text-gray-900">( {{ $customer->name }} )</p>
        </div>
        <div class="sign-block text-center">
            <p class="text-xs text-gray-500">Hormat Kami,</p>
            <div class="h-[50px]"></div>
            <div class="mx-auto mb-1 w-[120px] border-t border-gray-800"></div>
            <p class="text-xs font-bold text-gray-900">( Admin Sumbertani )</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="invoice-footer border-t border-gray-200 bg-gray-50 p-4 text-center text-[10px] text-gray-400">
        Terima kasih atas kepercayaan Anda berbelanja di Toko Sumbertani.<br>
        <em class="font-medium">Nota ini sah sebagai bukti transaksi yang valid.</em>
    </div>
</div>
