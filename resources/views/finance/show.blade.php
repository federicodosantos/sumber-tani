@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Number;
@endphp

<x-app-layout>
    <div class="py-6 flex justify-center font-mont">
        <div class="mx-auto w-full sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('transactions.updateStatus', $transaction->id) }}" method="POST"
                id="trxForm">
                @csrf
                @method('PUT')
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex flex-col">
                        <h1 class="text-2xl font-bold text-gray-800">Detail Transaksi | #{{ $transaction->id }}</h1>
                        <p>Jam {{ $transaction->created_at->format('H:i:s, d F Y') }}</p>
                    </div>
                    <a href="{{ route('finance.index') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-md font-semibold text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 active:scale-95 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12l7.5-7.5M3 12h18" />
                        </svg>
                        Back
                    </a>
                </div>
                <div x-data="{
                    open: false,
                    initialState: {{ $transaction->is_paid ? 'true' : 'false' }},
                    isPaid: {{ $transaction->is_paid ? 'true' : 'false' }},
                
                    get label() { return this.isPaid ? 'Sudah Lunas' : 'Belum Lunas' },
                    get colorClasses() {
                        return this.isPaid ?
                            'bg-green-100 text-green-800 border-green-200 hover:bg-green-200' :
                            'bg-red-100 text-red-800 border-red-200 hover:bg-red-200'
                    },
                    get hasChanged() {
                        return this.initialState !== this.isPaid;
                    }
                }" class="relative mb-6 p-2">

                    <input type="hidden" name="is_paid" :value="isPaid ? 1 : 0">

                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button type="button" @click="open = !open" @click.outside="open = false"
                                class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-semibold transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="colorClasses">

                                <span class="h-2 w-2 rounded-full"
                                    :class="isPaid ? 'bg-green-500' : 'bg-red-500'"></span>
                                <span x-text="label"></span>

                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 transition-transform duration-300" :class="open ? 'rotate-180' : ''"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                                class="absolute left-0 z-50 mt-2 w-48 origin-top-left rounded-xl border border-gray-100 bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                style="display: none;">

                                <div class="p-1">
                                    <button type="button" @click="isPaid = true; open = false"
                                        class="flex w-full items-center gap-2 rounded-lg px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-green-50 hover:text-green-700">
                                        <span class="h-2 w-2 rounded-full bg-green-500"></span> Sudah Lunas
                                    </button>

                                    <button type="button" @click="isPaid = false; open = false"
                                        class="flex w-full items-center gap-2 rounded-lg px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-red-50 hover:text-red-700">
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span> Belum Lunas
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="hasChanged" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 -translate-x-2"
                            x-transition:enter-end="opacity-100 translate-x-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-x-0"
                            x-transition:leave-end="opacity-0 -translate-x-2">

                            <button type="submit"
                                class="flex items-center gap-2 rounded-lg bg-button-main hover:bg-button-hover px-3 py-1.5 text-md font-semibold text-white shadow-md transition-all hover:shadow-lg active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                <span class="font-bold">SIMPAN</span> 
                            </button>
                        </div>

                    </div>
                </div>

                <x-content.data-table :search="false">
                    <x-slot name="header">
                        @if ($transaction->discount > 0)
                            <h1 class="p-5 text-sm uppercase">
                                Harga Asli:
                                <span class="font-semibold">
                                    Rp
                                    {{ Number::format((float) $transaction->total_price + (float) $transaction->discount, null, 3, 'id') }}
                                </span>
                            </h1>

                            <h1 class="px-5 pb-5 text-sm uppercase">
                                Total Diskon:
                                <span class="font-semibold">
                                    Rp {{ Number::format((float) $transaction->discount, null, 3, 'id') }}
                                </span>
                            </h1>
                            <h1 class="px-5 pb-5 text-sm uppercase">
                                Total Transaksi:
                                <span class="font-semibold">
                                    Rp {{ Number::format((float) $transaction->total_price, null, 3, 'id') }}
                                </span>
                            </h1>
                        @else
                            <h1 class="p-5 pb-5 text-sm uppercase">
                                Total Transaksi:
                                <span class="font-semibold">
                                    Rp {{ Number::format((float) $transaction->total_price, null, 3, 'id') }}
                                </span>
                            </h1>
                        @endif


                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                            Nama Produk
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                            Harga Produk
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                            Jumlah
                        </th>
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                            Subtotal
                        </th>
                    </x-slot>

                    <x-slot name="body">
                        @forelse ($transactionDetails as $details)
                            <tr class="border-b last:border-0 hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ $details->product->name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    Rp {{ Number::format((float) $details->product_price, null, 3, 'id') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ Number::format((float) $details->quantity, null, 3, 'id') }} pcs
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    Rp {{ Number::format((float) $details->total_price, null, 3, 'id') }}
                                </td>
                            </tr>

                        @endforeach
                    </x-slot>
                </x-content.data-table>

                <x-slot name="actions">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('finance.pdf.invoice', $transaction->id) }}" target="_blank" rel="noopener"
                            x-data="{ loading: false }"
                            x-on:click="loading = true; setTimeout(() => loading = false, 4000)"
                            :class="loading ? 'opacity-70 pointer-events-none' : ''"
                            class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-300 px-4 py-2 text-md font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-button-hover focus:ring-offset-2 active:scale-95 transition-transform">
                            <template x-if="!loading">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-red-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </template>
                            <template x-if="loading">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    class="w-4 h-4 text-red-500 animate-spin">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                                        stroke-opacity="0.25" />
                                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3"
                                        stroke-linecap="round" />
                                </svg>
                            </template>
                            <span x-text="loading ? 'Menyiapkan...' : 'Download PDF'"></span>
                        </a>

                        <button type="button" onclick="ThermalPrinter.printCashierReceipt({{ $transaction->id }})"
                            class="inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-md font-semibold text-white hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-button-hover focus:ring-offset-2 active:scale-95 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
                            </svg>
                            Cetak Nota
                        </button>
                    </div>
                </x-slot>

            </x-content.form-card>

        </div>

        {{-- QZ Error Modal --}}
        <div x-data="{ qzErrorTitle: '', qzErrorMessage: '' }"
            @open-qz-error.window="qzErrorTitle = $event.detail.title; qzErrorMessage = $event.detail.message; $dispatch('open-modal', 'qz-error')">
            <x-modal name="qz-error" maxWidth="md" zIndex="z-[100]">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold leading-6 text-gray-900" x-text="qzErrorTitle"></h3>
                </div>
                <div class="mt-2 text-sm text-gray-600 whitespace-pre-wrap" x-text="qzErrorMessage"></div>
                <x-slot name="footer">
                    <button @click="$dispatch('close-modal', 'qz-error')"
                        class="rounded-lg bg-button-main px-4 py-2 text-white shadow-sm hover:bg-button-hover transition-colors font-bold">
                        OK
                    </button>
                </x-slot>
            </x-modal>
        </div>

        @push('scripts')
            <script src="{{ asset('qz/qz-tray.js') }}"></script>
            <script src="{{ asset('qz/qz-config.js') }}"></script>
            <script src="{{ asset('qz/printer-utils.js') }}"></script>
            <script src="{{ asset('qz/layouts/cashier-layout.js') }}"></script>
            <script src="{{ asset('qz/layouts/r2-layout.js') }}"></script>
            <script src="{{ asset('qz/printer-main.js') }}"></script>
        @endpush
</x-app-layout>
