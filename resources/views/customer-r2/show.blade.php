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
                            <a href="{{ route('customer-r2.pay', $customer->id) }}"
                                class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                                BAYAR HUTANG
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Invoice List --}}
            <div>
                <h3 class="mb-4 text-lg font-bold text-gray-900">Daftar Invoice</h3>

                @forelse($invoices as $invoice)
                    <div class="mb-3 rounded-xl bg-white p-5 shadow-sm transition-all hover:shadow-md"
                        style="border: 1px solid #e5e7eb;">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-start gap-3">
                                {{-- Invoice Icon --}}
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $invoice->debts > 0 ? 'bg-red-50' : 'bg-green-50' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor"
                                        class="h-5 w-5 {{ $invoice->debts > 0 ? 'text-red-500' : 'text-green-500' }}">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>

                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        Invoice #{{ $invoice->id }}
                                    </p>
                                    @if ($invoice->transaction)
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            Transaksi #{{ $invoice->transaction->id }}
                                            • {{ $invoice->created_at->translatedFormat('d M Y, H:i') }}
                                        </p>
                                    @else
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $invoice->created_at->translatedFormat('d M Y, H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right flex flex-col items-end gap-2">
                                <div>
                                    <p
                                        class="text-sm font-bold {{ $invoice->debts > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        Rp {{ number_format($invoice->debts, 0, ',', '.') }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs {{ $invoice->debts > 0 ? 'text-red-400' : 'text-green-400' }}">
                                        {{ $invoice->debts > 0 ? 'Belum Lunas' : 'Lunas' }}
                                    </p>
                                </div>
                                <a href="{{ route('customer-r2.invoice.preview', $invoice->id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50 hover:border-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl bg-white p-8 text-center shadow-sm" style="border: 1px solid #e5e7eb;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mx-auto h-12 w-12 text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 italic">Belum ada invoice untuk pelanggan ini.</p>
                    </div>
                @endforelse

                <div class="mt-4 flex justify-end">
                    {{ $invoices->withQueryString()->links() }}
                </div>
            </div>

            {{-- Payment History (Audit Trail) --}}
            <div>
                <h3 class="mb-4 text-lg font-bold text-gray-900">Riwayat Pembayaran</h3>

                @if ($debtPayments->count() > 0)
                    <div class="rounded-2xl bg-white shadow-sm overflow-hidden" style="border: 1px solid #e5e7eb;">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th
                                            class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            No</th>
                                        <th
                                            class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            No. Invoice</th>
                                        <th
                                            class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            Tanggal Pembayaran</th>
                                        <th
                                            class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">
                                            Jumlah Bayar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($debtPayments as $index => $payment)
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                            <td class="px-5 py-3.5 text-sm text-gray-500">{{ $index + 1 }}</td>
                                            <td class="px-5 py-3.5 text-sm text-gray-500">{{ $payment->invoice_id }}
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <div class="flex items-center">

                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">
                                                            {{ $payment->payment_date->translatedFormat('d M Y') }}
                                                        </p>
                                                        <p class="text-xs text-gray-400">
                                                            {{ $payment->payment_date->translatedFormat('H:i') }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <span class="text-sm font-bold text-green-600">
                                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        {{ $debtPayments->withQueryString()->links() }}
                    </div>
                @else
                    <div class="rounded-xl bg-white p-8 text-center shadow-sm" style="border: 1px solid #e5e7eb;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 italic">Belum ada riwayat pembayaran untuk pelanggan ini.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-app-layout>
