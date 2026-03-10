<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full max-w-2xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back Button --}}
            <div>
                <a href="{{ route('pelanggan-r2.show', $customer->id) }}"
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
                        </div>
                    </div>
                </div>

                {{-- Payment Form --}}
                <form action="{{ route('pelanggan-r2.process', $customer->id) }}" method="POST">
                    @csrf

                    <div class="mb-6" x-data="{
                        displayAmount: '{{ old('amount') ? number_format(old('amount'), 0, '', '.') : '' }}',
                        rawAmount: '{{ old('amount') }}',
                        formatNumber(value) {
                            let digits = value.toString().replace(/[^0-9]/g, '');
                            this.rawAmount = digits;
                            if (!digits) return '';
                            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }
                    }">
                        <label for="amount_display" class="mb-1.5 block text-sm font-semibold text-black">
                            Nominal Pembayaran
                        </label>
                        {{-- Hidden input for form submission --}}
                        <input type="hidden" name="amount" x-model="rawAmount">
                        {{-- Visible formatted input --}}
                        <input type="text" id="amount_display" x-model="displayAmount" inputmode="numeric"
                            @input="displayAmount = formatNumber($event.target.value)"
                            placeholder="Masukkan nominal setoran"
                            class="block w-full rounded-lg border-1 lg:border-2 focus:border-button-hover focus:outline-none px-4 py-3 text-sm transition-all duration-100"
                            required>
                        @error('amount')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('general')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Info Box --}}
                    <div class="mb-6 rounded-lg bg-blue-50 border border-blue-200 p-4">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="h-5 w-5 text-blue-500 shrink-0 mt-0.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <p class="text-xs text-blue-700">
                                Pembayaran akan dialokasikan secara otomatis menggunakan metode <strong>FIFO</strong>
                                (First In, First Out). Invoice yang paling lama akan dilunasi terlebih dahulu.
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                        <x-button.remove-button href="{{ route('pelanggan-r2.show', $customer->id) }}">
                            <span class="font-bold">BATAL</span>
                        </x-button.remove-button>

                        <x-button.add-button type="submit">
                            <x-slot name="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                            </x-slot>
                            <span class="font-bold">PROSES PEMBAYARAN</span>
                        </x-button.add-button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
