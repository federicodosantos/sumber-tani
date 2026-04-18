<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back Button --}}
            <div>
                <a href="{{ route('customer-r2.index') }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke Daftar Pelanggan
                </a>
            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-green-500 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="text-sm font-medium text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- Customer Info Card --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm" style="border: 1px solid #e5e7eb;">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        {{-- Avatar --}}
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-7 w-7 text-blue-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>

                        {{-- Customer Details --}}
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">{{ $customer->name }}</h2>
                            <div class="mt-2 space-y-1">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    {{ $customer->address }}
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                    </svg>
                                    {{ $customer->phone_number }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Debt Summary --}}
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 sm:min-w-[220px]">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Hutang</p>
                        <p class="mt-1 text-2xl font-bold {{ $totalDebt > 0 ? 'text-red-600' : 'text-green-600' }}">
                            Rp {{ number_format($totalDebt, 0, ',', '.') }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">{{ $customer->invoices->count() }} invoice</p>

                        @if ($totalDebt > 0)
                            <button @click="$dispatch('open-modal', 'pay-debt')"
                                class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-button-main px-4 py-3 text-xs font-bold text-gray-800 transition-colors hover:bg-button-hover cursor-pointer shadow-sm active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                                BAYAR HUTANG
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Invoice List Section --}}
            <div x-data="{ currentType: '{{ request('type', 'all') }}' }">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Invoice</h3>
                    
                    {{-- Toggle Switch / Segmented Control --}}
                    <div class="flex items-center gap-1 rounded-xl bg-gray-100 p-1 self-start sm:self-auto">
                        <a href="{{ route('customer-r2.show', $customer->id) }}"
                            class="rounded-lg px-4 py-2 text-[10px] font-bold tracking-wider transition-all {{ !request('type') ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            SEMUA
                        </a>
                        <a href="{{ route('customer-r2.show', [$customer->id, 'type' => 'purchasement']) }}"
                            class="rounded-lg px-4 py-2 text-[10px] font-bold tracking-wider transition-all {{ request('type') === 'purchasement' ? 'bg-button-main text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            PEMBELIAN
                        </a>
                        <a href="{{ route('customer-r2.show', [$customer->id, 'type' => 'debt_payment']) }}"
                            class="rounded-lg px-4 py-2 text-[10px] font-bold tracking-wider transition-all {{ request('type') === 'debt_payment' ? 'bg-button-main text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            PEMBAYARAN
                        </a>
                    </div>
                </div>

                @if ($invoices->count() > 0)
                    <div class="rounded-2xl bg-white shadow-sm overflow-hidden" style="border: 1px solid #e5e7eb;">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Invoice</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Metode</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-center">Tanggal</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Total</th>
                                        @if (!request('type') || request('type') === 'purchasement')
                                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Sisa</th>
                                        @endif
                                        @if (!request('type') || request('type') === 'debt_payment')
                                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-center">Invoice Terlibat</th>
                                        @endif
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-center">Status</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $invoice)
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                            <td class="px-5 py-3.5">
                                                <div class="flex items-center gap-3">
                                                    @if ($invoice->type === 'debt_payment')
                                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $invoice->debts > 0 ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-500' }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <span class="text-sm font-bold text-gray-900">{{ $invoice->inv_code ?? '#' . $invoice->id }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                @if ($invoice->type === 'debt_payment' && $invoice->debtPayment)
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                                                {{ $invoice->debtPayment->payment_method === 'Cash' ? 'bg-green-100 text-green-700' : ($invoice->debtPayment->payment_method === 'Transfer' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700') }}">
                                                        {{ $invoice->debtPayment->payment_method }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700 uppercase tracking-wider">KREDIT</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <p class="text-sm font-medium text-gray-900">{{ $invoice->created_at->translatedFormat('d M Y') }}</p>
                                                <p class="text-[10px] text-gray-400">{{ $invoice->created_at->translatedFormat('H:i') }}</p>
                                            </td>
                                            <td class="px-5 py-3.5 text-right font-bold text-gray-900 text-sm">
                                                Rp {{ number_format($invoice->type === 'debt_payment' ? ($invoice->debtPayment?->amount ?? 0) : ($invoice->transaction?->total_price ?? 0), 0, ',', '.') }}
                                            </td>
                                            
                                            @if (!request('type') || request('type') === 'purchasement')
                                                <td class="px-5 py-3.5 text-right font-medium text-sm">
                                                    @if ($invoice->type === 'purchasement')
                                                        <span class="{{ $invoice->debts > 0 ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                                            Rp {{ number_format($invoice->debts, 0, ',', '.') }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-300">-</span>
                                                    @endif
                                                </td>
                                            @endif

                                            @if (!request('type') || request('type') === 'debt_payment')
                                                <td class="px-5 py-3.5">
                                                    @if ($invoice->type === 'debt_payment' && $invoice->debtPayment)
                                                        <div class="flex flex-wrap justify-center gap-1">
                                                            @foreach ($invoice->debtPayment->details as $detail)
                                                                <span class="inline-flex items-center rounded bg-gray-50 border border-gray-200 px-1.5 py-0.5 text-[10px] font-medium text-gray-600">
                                                                    {{ $detail->invoice->inv_code ?? '#' . $detail->invoice_id }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="text-center text-gray-300">-</div>
                                                    @endif
                                                </td>
                                            @endif

                                            <td class="px-5 py-3.5 text-center">
                                                @if ($invoice->type === 'purchasement')
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $invoice->debts > 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                                        {{ $invoice->debts > 0 ? 'BELUM LUNAS' : 'LUNAS' }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-[10px] font-bold text-green-700 uppercase tracking-wider">SUKSES</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <button @click="$dispatch('open-modal', 'preview-invoice-{{ $invoice->id }}')"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50 hover:border-gray-400">
                                                    Lihat
                                                </button>

                                                <x-modal name="preview-invoice-{{ $invoice->id }}" title="PREVIEW {{ $invoice->inv_code ?? 'NOTA #' . $invoice->id }}" maxWidth="2xl">
                                                    <div class="p-1">
                                                        @include('customer-r2.partials._invoice-content', [
                                                            'invoice' => $invoice,
                                                            'customer' => $customer,
                                                            'transaction' => $invoice->transaction,
                                                            'details' => $invoice->transaction ? $invoice->transaction->transactionDetails : collect()
                                                        ])
                                                    </div>
                                                    <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                                                        <x-button.remove-button x-on:click="$dispatch('close-modal', 'preview-invoice-{{ $invoice->id }}')" type="button">
                                                            <span class="font-bold text-gray-800">TUTUP</span>
                                                        </x-button.remove-button>
                                                        
                                                        <a href="{{ route('customer-r2.invoice.preview', $invoice->id) }}" target="_blank"
                                                            class="inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-bold text-gray-800 hover:bg-button-hover transition-colors shadow-sm">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
                                                            </svg>
                                                            CETAK NOTA
                                                        </a>
                                                    </div>
                                                </x-modal>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl bg-white p-12 text-center shadow-sm" style="border: 1px solid #e5e7eb;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 italic">Tidak ada invoice untuk kriteria ini.</p>
                    </div>
                @endif

                <div class="mt-4 flex justify-end">
                    {{ $invoices->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Pay Debt Modal --}}
    <x-modal name="pay-debt" title="PEMBAYARAN HUTANG PELANGGAN" maxWidth="xl" x-init="if ($errors->any()) $dispatch('open-modal', 'pay-debt')">
        <div class="p-6">
            {{-- Customer Summary Info within Modal --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $customer->name }}</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $customer->phone_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Hutang</p>
                        <p class="mt-0.5 text-xl font-bold text-red-600">
                            Rp {{ number_format($totalDebt, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            @include('customer-r2.partials._payment-form', ['customer' => $customer, 'totalDebt' => $totalDebt, 'isModal' => true])
        </div>
    </x-modal>
</x-app-layout>
