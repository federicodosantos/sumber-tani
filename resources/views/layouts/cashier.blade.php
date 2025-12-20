@props(['categories', 'products' => []])

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cashier - {{ config('app.name', 'SUMBER TANI') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}">

    <script src="{{ asset('qz/qz-tray.js') }}"></script>
    <script src="{{ asset('qz/config.js') }}"></script>
    {{-- <button onclick="listPrinters()">Cek Printer QZ</button> --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased">
    <div class="flex h-screen overflow-hidden bg-gray-50" x-data="cashierHandler({{ Js::from($products) }}, {{ Js::from($categories) }})">
        <aside class="flex w-64 flex-col border-r border-gray-200 bg-white">
            <div class="border-b border-gray-200 p-6">
                <img src="{{ asset('images/logo-kasir.svg') }}" alt="Sumber Tani">
            </div>
            <div x-data="{
                count: 0,
                async checkDB() {
                    try {
                        // Cek apakah global db sudah siap
                        if (window.db) {
                            // Update variable count (gunakan 'this' untuk akses data sendiri)
                            this.count = await window.db.offline_transactions
                                .where('is_synced').equals(0)
                                .count();
                        }
                    } catch (e) {
                        // Silent error, mungkin db belum load, biarkan saja
                    }
                }
            }" x-init="// Panggil sekali saat load
            checkDB();
            
            // Panggil ulang setiap 2 detik
            setInterval(() => checkDB(), 2000);" x-show="count > 0" style="display: none;"
                class="fixed left-0 top-0 z-50 w-full animate-pulse bg-yellow-500 py-2 text-center font-bold text-white shadow-md">

                <div class="flex items-center justify-center gap-2">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>
                        ⚠️ PERHATIAN: Ada <span x-text="count"
                            class="mx-1 rounded-full bg-white px-2 text-yellow-600"></span> Transaksi Belum Tersimpan!
                    </span>
                </div>
                <span class="mt-1 block text-sm font-normal text-yellow-100 sm:inline">Jangan tutup browser atau hapus
                    cache.</span>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <div x-show="isOffline"
                    class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-2 text-center text-xs font-bold text-red-700">
                    MODE OFFLINE
                </div>
                <div class="mb-3 flex items-center gap-2">
                    <img src="{{ asset('images/logo-kategori-kasir.svg') }}" alt="">
                    <h2 class="font-semibold text-gray-700">KATEGORI</h2>
                </div>

                <nav class="space-y-1">
                    <button @click="selectedCategory = null"
                        :class="selectedCategory === null ? 'bg-button-main text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="block w-full rounded-lg px-4 py-2.5 text-left font-medium transition-colors">
                        Semua Kategori
                    </button>

                    <template x-for="item in categories" :key="item.id">
                        <button @click="selectedCategory = item.id"
                            :class="selectedCategory === item.id ? 'bg-button-main text-white' :
                                'text-gray-700 hover:bg-gray-100'"
                            class="block w-full rounded-lg px-4 py-2.5 text-left font-medium transition-colors">
                            <span x-text="item.name"></span>
                        </button>
                    </template>
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

        <aside class="flex w-80 flex-col border-l border-gray-200 bg-white">
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
                                class="bg-button-main hover:bg-button-hover flex h-8 w-8 items-center justify-center rounded-lg text-white transition-colors"
                                type="button">
                                -
                            </button>

                            <input type="number" :value="item.qty" @input="setQty(item.id, $event.target.value)"
                                @blur="handleQtyBlur(item.id, $event)" min="1" :max="item.stock"
                                class="focus:border-button-main focus:ring-button-main h-8 w-16 rounded-lg border border-gray-300 bg-white text-center focus:outline-none focus:ring-2">

                            <button @click="updateQty(item.id, 1)"
                                class="bg-button-main hover:bg-button-hover flex h-8 w-8 items-center justify-center rounded-lg text-white transition-colors"
                                type="button">
                                +
                            </button>

                            <span class="ml-2 text-xs text-gray-500">
                                / <span x-text="item.stock"></span>
                            </span>
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
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-700 opacity-70">
                                Total Bayar
                                <span x-show="manualTotal !== null"
                                    class="ml-1 font-bold text-red-500">(Manual)</span>
                            </p>

                            <div x-data="{ isEditing: false }" class="relative">
                                <div x-show="!isEditing" class="group flex items-center justify-end gap-2">
                                    <p class="text-xl font-black text-gray-900" x-text="formatRupiah(totalPrice)"></p>

                                    <button x-show="cart.length > 0"
                                        @click="isEditing = true; 
                manualTotal = manualTotal || totalPrice; 
                $nextTick(() => $refs.totalInput.focus());"
                                        class="transition-opacity hover:text-blue-500"
                                        title="Edit Harga Total Manual">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                </div>

                                <div x-show="isEditing" class="mt-1 flex items-center justify-end gap-1">
                                    <div class="relative w-32">
                                        <span class="absolute left-2 top-1.5 text-xs font-bold text-gray-500">Rp</span>
                                        <input x-ref="totalInput" type="text" x-model="manualTotal"
                                            @blur="
                        let autoTotal = cart.reduce((t, i) => t + (i.price * i.qty), 0);
                        if(Number(manualTotal) === autoTotal) { manualTotal = null; }
                        isEditing = false;
                    "
                                            @keydown.enter.prevent="$el.blur();"
                                            class="focus:ring-button-hover w-full rounded border py-1 pl-6 pr-2 text-right text-sm font-bold outline-none focus:bg-white focus:ring-2">
                                    </div>
                                    <button @click="manualTotal = null; isEditing = false;"
                                        class="rounded p-1 text-red-500 hover:bg-red-50"
                                        title="Reset ke Harga Otomatis">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <template x-if="manualTotal !== null && cart.length > 0">
<<<<<<< Updated upstream
                                <p class="mt-0.5 text-xs font-medium text-gray-400 line-through"
                                    title="Harga Asli Sebelum Edit">
=======
                                <p class="text-xs text-gray-900 mt-0.5 "
                                    title="Harga Asli Sebelum Edit">Harga Sistem: 
>>>>>>> Stashed changes
                                    <span
                                        x-text="formatRupiah(cart.reduce((t, i) => t + (i.price * i.qty), 0))" class="font-bold"></span>
                                </p>
                            </template>

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
