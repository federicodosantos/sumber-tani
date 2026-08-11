<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back Button --}}
            <div>
                <a href="{{ route('customer-r2.show', $customer->id) }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke Detail Pelanggan
                </a>
            </div>

            {{-- Payment Card --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm" style="border: 1px solid #e5e7eb;">
                <h2 class="text-lg font-bold text-gray-900 mb-6">Pembayaran Hutang</h2>

                {{-- Customer Summary --}}
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
                            @if (($creditBalance ?? 0) > 0)
                                <p class="mt-1 text-xs font-medium text-green-600">
                                    Sisa Saldo: Rp {{ number_format($creditBalance, 0, ',', '.') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Payment Form --}}
                @include('customer-r2.partials._payment-form', [
                    'customer'      => $customer,
                    'totalDebt'     => $totalDebt,
                    'creditBalance' => $creditBalance ?? 0,
                    'isModal'       => false,
                ])
            </div>

        </div>
    </div>
</x-app-layout>
