@props(['categories'])

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cashier - {{ config('app.name', 'SUMBER TANI') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <div class="flex h-screen overflow-hidden bg-gray-50" x-data="cashierHandler()">
        <aside class="flex w-64 flex-col border-r border-gray-200 bg-white">
            <div class="border-b border-gray-200 p-6">
                <img src="{{ asset('images/logo-kasir.svg') }}" alt="Sumber Tani">
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <div class="mb-3 flex items-center gap-2">
                    <img src="{{ asset('images/logo-kategori-kasir.svg') }}" alt="">
                    <h2 class="font-semibold text-gray-700">KATEGORI</h2>
                </div>

                <nav class="space-y-1">
                    @foreach ($categories as $item)
                        @php
                            // Cek apakah ada request search?
                            $isSearching = request()->has('search') && request('search') != '';

                            // Item aktif jika ID cocok DAN tidak sedang searching
                            $isActive = request('category') == $item->id && !$isSearching;
                        @endphp

                        <a href="{{ route('cashier', ['category' => $item->id]) }}"
                            class="{{ $isActive ? 'bg-button-main text-white font-medium' : 'text-gray-700 hover:bg-gray-100' }} block rounded-lg px-4 py-2.5 transition-colors">
                            {{ $item->name }}
                        </a>
                    @endforeach
                </nav>

                @if (request('search'))
                    <div class="mt-4 px-4">
                        <a href="{{ route('cashier') }}"
                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 py-2 text-sm text-red-600 hover:bg-red-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Reset Pencarian
                        </a>
                    </div>
                @endif
            </div>

            <div class="border-t border-gray-200 bg-white p-4">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-700 transition-colors hover:bg-gray-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
            </div>
        </aside>

        <main class="flex flex-1 flex-col overflow-hidden">
            {{ $slot }}
        </main>

        <aside class="flex w-96 flex-col border-l border-gray-200 bg-white">
            <div class="border-b border-gray-200 p-6">
                <h2 class="text-2xl font-bold text-gray-900">Data Pemesanan</h2>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto p-6">
                <div x-show="cart.length === 0" class="mt-10 text-center text-gray-400">
                    Belum ada produk dipilih
                </div>
                <template x-for="item in cart" :key="item.id">
                    <div class="border-button-main mb-3 rounded-2xl border-2 bg-gray-50 p-4">
                        <div class="mb-3 flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="mb-1 font-bold text-gray-900" x-text="item.name"></h3>
                                <p class="text-sm text-gray-600" x-text="formatRupiah(item.price)"></p>
                            </div>
                            <button @click="removeItem(item.id)" class="text-red-500 hover:text-red-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="updateQty(item.id, -1)"
                                class="bg-button-main flex h-8 w-8 items-center justify-center rounded-lg text-white">
                                -
                            </button>
                            <input type="number" :value="item.qty" readonly
                                class="h-8 w-12 rounded-lg border bg-white text-center">
                            <button @click="updateQty(item.id, 1)"
                                class="bg-button-main flex h-8 w-8 items-center justify-center rounded-lg text-white">
                                +
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="p-6">
                <div class="bg-button-main rounded-3xl p-5 shadow-xl">
                    <div class="mb-4 flex items-center justify-between px-1">
                        <div class="flex flex-col">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-700 opacity-70">Total
                                Item</p>
                            <p class="font-bold text-gray-900"><span x-text="totalQty"></span> Pcs</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-700 opacity-70">Total
                                Bayar</p>
                            <p class="text-xl font-black text-gray-900" x-text="formatRupiah(totalPrice)"></p>
                        </div>
                    </div>
                    <button @click="processCheckout()"
                        class="text-button-main flex w-full items-center justify-center gap-2 rounded-2xl bg-white py-3.5 font-bold shadow-sm transition-all hover:bg-gray-50 hover:shadow-md active:scale-95">
                        <span>BAYAR</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>

</body>

</html>
