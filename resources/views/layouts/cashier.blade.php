@props([
    'categories',
    'products' => [],
    'customers_r2' => [],
    'r2_custom_prices' => [],
    'customers' => null,
    'custom_prices' => null,
])

@php
    $customersForJs = $customers ?? $customers_r2;
    $customPricesForJs = $custom_prices ?? $r2_custom_prices;
@endphp

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
    <script src="{{ asset('qz/qz-config.js') }}"></script>
    <script src="{{ asset('qz/printer-utils.js') }}"></script>
    <script src="{{ asset('qz/layouts/cashier-layout.js') }}"></script>
    <script src="{{ asset('qz/layouts/r2-layout.js') }}"></script>
    <script src="{{ asset('qz/printer-main.js') }}"></script>
    {{-- <button onclick="listPrinters()">Cek Printer QZ</button> --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
        .cart-qty-input::-webkit-outer-spin-button,
        .cart-qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .cart-qty-input {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="antialiased font-mont">
    <div class="flex h-screen flex-col overflow-hidden bg-gray-50" x-data="cashierHandler({{ Js::from($products) }}, {{ Js::from($categories) }}, {{ Js::from($customersForJs) }}, {{ Js::from($customPricesForJs) }})">
        <!-- WARNING BANNER OFFLINE -->
        <div x-data="{
                count: 0,
                async checkDB() {
                    try {
                        if (window.db) {
                            this.count = await window.db.offline_transactions
                                .where('is_synced').equals(0)
                                .count();
                        }
                    } catch (e) {
                    }
                }
            }" x-init="checkDB(); setInterval(() => checkDB(), 2000);" x-show="count > 0" style="display: none;"
            class="z-50 shrink-0 w-full animate-pulse bg-yellow-500 py-2 text-center font-bold text-white shadow-md">

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
        
        <!-- MAIN APP WRAPPER -->
        <div class="flex flex-1 overflow-hidden">
            <aside class="flex flex-col border-r border-gray-200 bg-white transition-all duration-300"
                :class="leftSidebarCollapsed ? 'w-16' : 'w-64'">
                <div class="border-b border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <img x-show="!leftSidebarCollapsed" src="{{ asset('images/logo-kasir.svg') }}" alt="Sumber Tani"
                            class="h-10">
                        <img x-show="leftSidebarCollapsed" x-cloak src="{{ asset('favicon.svg') }}" alt="Sumber Tani"
                            class="h-8 w-8">
                        <button type="button" @click="toggleLeftSidebar()"
                            class="hidden lg:flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100"
                            :title="leftSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'">
                            <svg class="h-4 w-4 transition-transform duration-300"
                                :class="leftSidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
                

            <div class="flex-1 overflow-y-auto p-4">
                <div x-show="isOffline && !leftSidebarCollapsed"
                    class="mb-4 rounded border border-red-400 bg-red-100 px-4 py-2 text-center text-xs font-bold text-red-700">
                    MODE OFFLINE
                </div>
                <div class="mb-3 flex items-center gap-2" :class="leftSidebarCollapsed ? 'justify-center' : ''">
                    <img src="{{ asset('images/logo-kategori-kasir.svg') }}" alt="">
                    <h2 x-show="!leftSidebarCollapsed" x-cloak class="font-semibold text-gray-700">KATEGORI</h2>
                </div>

                <nav class="space-y-1">
                    <button @click="selectedCategory = null"
                        :class="selectedCategory === null ? 'bg-button-main text-white' : 'text-gray-700 hover:bg-gray-100'"
                        class="block w-full rounded-lg px-4 py-2.5 text-left font-medium transition-colors"
                        :title="leftSidebarCollapsed ? 'Semua Kategori' : ''">
                        <span x-show="!leftSidebarCollapsed">Semua Kategori</span>
                        <span x-show="leftSidebarCollapsed" x-cloak>S</span>
                    </button>

                    <template x-for="item in categories" :key="item.id">
                        <button @click="selectedCategory = item.id"
                            :class="selectedCategory === item.id ? 'bg-button-main text-white' :
                                'text-gray-700 hover:bg-gray-100'"
                            class="block w-full rounded-lg px-4 py-2.5 text-left font-medium transition-colors"
                            :title="leftSidebarCollapsed ? item.name : ''">
                            <span x-text="leftSidebarCollapsed ? item.name.charAt(0) : item.name"></span>
                        </button>
                    </template>
                </nav>

                @if (request('search'))
                    <div class="mt-4 px-4">
                        <a href="{{ route('cashier') }}" x-show="!leftSidebarCollapsed"
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

            <div class="border-t border-gray-200 bg-white p-4" :class="leftSidebarCollapsed ? 'p-2' : 'p-4'">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-gray-700 transition-colors hover:bg-gray-100"
                    :class="leftSidebarCollapsed ? 'justify-center gap-0 px-0 py-2' : ''"
                    :title="leftSidebarCollapsed ? 'Dashboard' : ''">
                    <svg class="h-5 w-5 shrink-0" :class="leftSidebarCollapsed ? 'h-6 w-6' : ''" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span x-show="!leftSidebarCollapsed" x-cloak class="font-medium">Dashboard</span>
                </a>
            </div>
        </aside>

        <main class="flex flex-1 flex-col overflow-hidden">
            {{ $slot }}
        </main>

        <aside class="relative flex flex-col border-l border-gray-200 bg-white"
            :style="`width:${orderPanelWidth}px; min-width:${panelMinWidth}px; max-width:50vw;`">
            <button type="button" @mousedown="startOrderPanelResize($event)"
                class="absolute -left-1 top-0 z-30 hidden h-full w-2 cursor-col-resize bg-transparent hover:bg-button-main/20 lg:block"
                title="Resize panel pemesanan">
            </button>
            <div class="border-b border-gray-200 bg-white">
                <div class="flex overflow-x-auto p-3 gap-2 border-b border-gray-100 scrollbar-hide">
                    <template x-for="(tab, index) in tabs" :key="tab.id">
                        <div class="relative group flex items-center shrink-0 min-w-[100px]">
                            <button @click="switchTab(tab.id)" 
                                :class="activeTabId === tab.id ? 'bg-button-main text-white shadow-md' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100 border'"
                                class="flex-1 rounded-lg px-3 py-2 text-sm text-left relative transition-all pr-8">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-bold whitespace-nowrap" x-text="'Transaksi ' + (index + 1)"></span>
                                    <template x-if="tab.pendingSync">
                                        <span class="text-xs" :class="activeTabId === tab.id ? 'text-white' : 'text-yellow-600'" title="Pending Sync">⏳</span>
                                    </template>
                                </div>
                            </button>
                            <button @click.stop="closeTab(tab.id)" title="Tutup Transaksi"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 p-1 rounded-full transition-colors opacity-70 hover:opacity-100 focus:opacity-100"
                                :class="activeTabId === tab.id ? 'text-white hover:bg-white/20' : 'text-gray-400 hover:text-red-500 hover:bg-red-50'">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                    <button @click="openNewTab()" 
                        class="shrink-0 flex items-center justify-center w-[100px] h-10 rounded-lg border-2 border-dashed border-gray-300 text-gray-400 hover:border-button-main hover:text-button-main transition-colors bg-gray-50 cursor-pointer">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span class="text-xs font-bold uppercase">Baru</span>
                    </button>
                </div>
                
                <div class="p-4 pb-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 line-clamp-1">Data Pemesanan</h2>
                            <template x-if="selectedCustomer">
                                <div
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-button-main/30 bg-button-main/10 px-2.5 py-1 text-xs font-bold text-button-hover shadow-sm transition-all duration-200">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span x-text="selectedCustomer.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto p-4 min-h-0">
                <div x-show="cart.length === 0" class="mt-10 text-center text-gray-400">
                    Belum ada produk dipilih
                </div>
                <template x-for="item in cart" :key="item.id">
                    <div x-data="{ isEditingPrice: false }"
                        @click.outside="isEditingPrice = false"
                        class="group rounded-xl border bg-white p-3.5 transition-all duration-200"
                        :class="item.isManualPrice
                            ? 'border-amber-300 bg-amber-50/40 shadow-[0_1px_0_0_rgba(217,119,6,0.04)]'
                            : 'border-button-main/75 hover:border-gray-300 hover:shadow-sm'">

                        {{-- Header: Name + Price + Actions --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-[15px] font-bold leading-snug text-gray-900" x-text="item.name"></h3>

                                {{-- Price line (default view) --}}
                                <div x-show="!isEditingPrice" class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="text-sm font-bold text-gray-900 tabular-nums" x-text="formatRupiah(item.price)"></span>
                                    <template x-if="item.isManualPrice">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-200/60 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.08em] text-amber-800">
                                            <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            Manual
                                        </span>
                                    </template>
                                    <template x-if="item.isManualPrice">
                                        <span class="text-[11px] text-gray-400 line-through tabular-nums" x-text="formatRupiah(item.basePrice ?? item.price)"></span>
                                    </template>
                                    <template x-if="item.isManualPrice">
                                        <button type="button" @click.stop="resetItemPrice(item.id)"
                                            :title="'Kembalikan ke harga sistem ' + formatRupiah(item.basePrice ?? item.price)"
                                            class="group/reset inline-flex items-center gap-1 rounded-full border border-amber-200 bg-white/60 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700 transition-all hover:border-amber-400 hover:bg-amber-100 hover:text-amber-900 cursor-pointer">
                                            <svg class="h-2.5 w-2.5 transition-transform group-hover/reset:-rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                            </svg>
                                            Reset
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Action icons --}}
                            <div class="-mr-1 -mt-1 flex shrink-0 items-center">
                                <button x-show="!isEditingPrice" type="button" @click="isEditingPrice = true"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-blue-600 cursor-pointer"
                                    title="Edit Harga Satuan">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>
                                <button x-show="isEditingPrice" type="button" @click="isEditingPrice = false"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-600 cursor-pointer"
                                    title="Selesai">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </button>
                                <button type="button" @click="removeItem(item.id)"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 cursor-pointer"
                                    title="Hapus">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Inline Price Editor --}}
                        <div x-show="isEditingPrice"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="mt-2.5">
                            <div class="flex items-stretch gap-2">
                                <div class="flex-1">
                                    <x-input-rupiah
                                        value="0"
                                        placeholder="0"
                                        containerClass="w-full mb-0"
                                        decimals="3"
                                        @rupiah-change="setItemManualPrice(item.id, $event.detail.value)"
                                        x-init="$watch('isEditingPrice', v => v && $el.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { value: item.price } })))"
                                    />
                                </div>
                                <button type="button" @click="resetItemPrice(item.id); isEditingPrice = false"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-[11px] font-bold uppercase tracking-wider text-gray-600 transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 cursor-pointer">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                    </svg>
                                    Reset
                                </button>
                            </div>
                            <p class="mt-1.5 text-[11px] text-gray-500">
                                Harga sistem:
                                <span class="font-semibold text-gray-700 tabular-nums" x-text="formatRupiah(item.basePrice ?? item.price)"></span>
                            </p>
                        </div>

                        {{-- Quantity Stepper + Subtotal --}}
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <div class="inline-flex items-center gap-1.5">
                                <button @click="updateQty(item.id, -1)" type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-base font-bold text-gray-700 transition-all hover:bg-button-main hover:text-white active:scale-95 cursor-pointer">
                                    −
                                </button>

                                <input type="text"
                                    :value="String(item.qty).replace('.', ',')"
                                    @input="setQty(item.id, $event.target.value, $event)" @blur="handleQtyBlur(item.id, $event)"
                                    @keydown="const k=$event.key; const nav=['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End','Enter']; const ok=/[0-9]/.test(k)||nav.includes(k)||$event.ctrlKey||$event.metaKey||(k===','&&!$el.value.includes(',')); if(!ok) $event.preventDefault();"
                                    class="h-8 w-14 rounded-lg border border-gray-200 bg-white text-center text-sm font-bold tabular-nums text-gray-900 focus:border-button-main focus:outline-none focus:ring-2 focus:ring-button-main/20 cart-qty-input">

                                <button @click="updateQty(item.id, 1)" type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-base font-bold text-gray-700 transition-all hover:bg-button-main hover:text-white active:scale-95 cursor-pointer">
                                    +
                                </button>

                                <span class="ml-1 text-[11px] font-medium text-gray-400 tabular-nums">
                                    / <span x-text="formatStock(item.stock)"></span>
                                </span>
                            </div>

                            <div class="text-right">
                                <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-gray-400">Subtotal</p>
                                <p class="text-base font-black leading-tight text-gray-900 tabular-nums" x-text="formatRupiah(item.price * item.qty)"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Checkout Section Toggle -->
            <div class="border-t border-gray-200 bg-white shrink-0">
                <button @click="toggleCheckoutExpansion()" 
                    class="group flex w-full items-center justify-between px-4 py-2 transition-colors hover:bg-gray-50">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 transition-transform duration-300 group-hover:bg-button-main/20"
                            :class="isCheckoutExpanded ? 'rotate-180' : ''">
                            <svg class="h-4 w-4 text-gray-600 transition-colors group-hover:text-button-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Informasi Pembayaran</span>
                    </div>

                    <!-- Summary when collapsed -->
                    <div x-show="!isCheckoutExpanded" x-transition:enter="transition ease-out duration-300 delay-100"
                        x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                        class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-[10px] font-bold uppercase tracking-tighter text-gray-400">Total Harga</p>
                            <p class="text-sm font-black text-gray-900" x-text="formatRupiah(totalPrice)"></p>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Collapsible Section with Smooth Grid Transition -->
            <div class="grid transition-all duration-500 ease-in-out shrink-0" 
                style="display: grid;"
                :style="isCheckoutExpanded ? 'grid-template-rows: 1fr; border-top-width: 1px;' : 'grid-template-rows: 0fr; border-top-width: 0px;'"
                class="border-gray-100 bg-white">
                <div :class="isCheckoutExpanded ? 'overflow-visible' : 'overflow-hidden'" class="min-h-0 flex flex-col">
                    <div class="w-full flex justify-between items-center pt-3 px-4 mb-2">
                        <div>
                            <h3 class="font-bold text-gray-700">Metode Pembayaran</h3>
                        </div>

                        <div x-data="{ open: false }" class="relative">

                            <button @click="open = !open"
                                class="flex items-center gap-2 border border-gray-300 rounded-lg px-3 py-1.5 text-sm font-bold text-gray-700 bg-white hover:border-button-main hover:text-button-main transition-all shadow-sm active:scale-95">

                                <template x-if="paymentMethod === 'Cash'">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                </template>
                                <template x-if="paymentMethod === 'QRIS'">
                                    <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                        </path>
                                    </svg>
                                </template>
                                <template x-if="paymentMethod === 'Transfer'">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                        </path>
                                    </svg>
                                </template>
                                <template x-if="paymentMethod === 'Kredit'">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                                        </path>
                                    </svg>
                                </template>

                                <span x-text="paymentMethod" class="uppercase tracking-wide"></span>

                                <svg class="w-4 h-4 ml-1 text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                    </path>
                                </svg>
                            </button>

                            <div x-show="open" @click.outside="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95 translate-y-2"
                                x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="transform opacity-0 scale-95 translate-y-2"
                                class="absolute bottom-full right-0 mb-2 w-48 bg-white rounded-xl shadow-xl border border-gray-300 py-1 z-20 overflow-hidden">

                                <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Pilih Metode
                                </p>

                                <button @click="setPaymentMethod('Cash'); open = false"
                                    class="w-full text-left px-4 py-2.5 text-sm font-semibold hover:bg-gray-50 flex items-center gap-3"
                                    :class="paymentMethod === 'Cash' ? 'text-green-600 bg-green-50' : 'text-gray-700'">
                                    <span>💵</span> CASH
                                </button>

                                <button @click="setPaymentMethod('QRIS'); open = false"
                                    class="w-full text-left px-4 py-2.5 text-sm font-semibold hover:bg-gray-50 flex items-center gap-3"
                                    :class="paymentMethod === 'QRIS' ? 'text-gray-900 bg-gray-100' : 'text-gray-700'">
                                    <span>📱</span> QRIS
                                </button>

                                <button @click="setPaymentMethod('Transfer'); open = false"
                                    class="w-full text-left px-4 py-2.5 text-sm font-semibold hover:bg-gray-50 flex items-center gap-3"
                                    :class="paymentMethod === 'Transfer' ? 'text-blue-600 bg-blue-50' : 'text-gray-700'">
                                    <span>💳</span> TRANSFER
                                </button>

                                <div class="border-t border-gray-100 my-1"></div>

                                <button @click="setPaymentMethod('Kredit'); open = false"
                                    class="w-full text-left px-4 py-2.5 text-sm font-semibold hover:bg-red-50 flex items-center gap-3"
                                    :class="paymentMethod === 'Kredit' ? 'text-red-600 bg-red-50' : 'text-gray-700'">
                                    <span>📝</span> KREDIT / BON
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="paymentMethod === 'Cash'" class="px-4 pb-3" style="display: none;">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-[0_1px_0_0_rgba(0,0,0,0.02)]">
                            {{-- Cash Received Input --}}
                            <div class="p-3">
                                <div class="mb-1.5 flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-500">
                                        Uang Diterima
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" x-show="totalPrice > 0"
                                            @click="$refs.cashRupiahWrapper.firstElementChild.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { value: totalPrice } }))"
                                            :class="cashReceived === totalPrice
                                                ? 'border-emerald-400 bg-emerald-100 text-emerald-800 shadow-[inset_0_-1px_0_0_rgba(5,150,105,0.2)]'
                                                : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400 hover:bg-gray-50 hover:text-gray-800'"
                                            class="rounded-md border px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider transition-all active:scale-95 cursor-pointer">
                                            Pas
                                        </button>
                                        <button type="button" x-show="totalPrice > 0 && totalPrice < 50000"
                                            @click="$refs.cashRupiahWrapper.firstElementChild.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { value: 50000 } }))"
                                            :class="cashReceived === 50000
                                                ? 'border-emerald-400 bg-emerald-100 text-emerald-800 shadow-[inset_0_-1px_0_0_rgba(5,150,105,0.2)]'
                                                : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400 hover:bg-gray-50 hover:text-gray-800'"
                                            class="rounded-md border px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider transition-all active:scale-95 cursor-pointer">
                                            50K
                                        </button>
                                        <button type="button" x-show="totalPrice > 0 && totalPrice < 100000"
                                            @click="$refs.cashRupiahWrapper.firstElementChild.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { value: 100000 } }))"
                                            :class="cashReceived === 100000
                                                ? 'border-emerald-400 bg-emerald-100 text-emerald-800 shadow-[inset_0_-1px_0_0_rgba(5,150,105,0.2)]'
                                                : 'border-gray-200 bg-white text-gray-600 hover:border-gray-400 hover:bg-gray-50 hover:text-gray-800'"
                                            class="rounded-md border px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider transition-all active:scale-95 cursor-pointer">
                                            100K
                                        </button>
                                    </div>
                                </div>
                                <div x-ref="cashRupiahWrapper">
                                    <x-input-rupiah
                                        value=""
                                        placeholder="0"
                                        containerClass="mb-0"
                                        decimals="3"
                                        @rupiah-change="cashReceivedInput = $event.detail.value"
                                        x-init="$watch('cashReceivedInput', v => v === '' && $el.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { value: '' } })))"
                                    />
                                </div>
                            </div>

                            {{-- Status Strip: Change / Shortage --}}
                            <div class="border-t px-3.5 py-2.5 transition-colors duration-200"
                                :class="cashReceived === 0
                                    ? 'border-gray-100 bg-gray-50/60'
                                    : (cashReceived >= totalPrice
                                        ? 'border-emerald-100 bg-emerald-50/70'
                                        : 'border-rose-100 bg-rose-50/70')">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full transition-colors"
                                            :class="cashReceived === 0
                                                ? 'bg-gray-300'
                                                : (cashReceived >= totalPrice ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse')"></span>
                                        <span class="text-[10px] font-bold uppercase tracking-[0.14em]"
                                            :class="cashReceived === 0
                                                ? 'text-gray-500'
                                                : (cashReceived >= totalPrice ? 'text-emerald-700' : 'text-rose-700')"
                                            x-text="cashReceived > 0 && cashReceived < totalPrice ? 'Kurang Bayar' : 'Kembalian'"></span>
                                    </div>
                                    <span class="text-base font-black leading-none tabular-nums transition-colors"
                                        :class="cashReceived === 0
                                            ? 'text-gray-700'
                                            : (cashReceived >= totalPrice ? 'text-emerald-700' : 'text-rose-600')"
                                        x-text="cashReceived > 0 && cashReceived < totalPrice ? formatRupiah(totalPrice - cashReceived) : formatRupiah(changeAmount)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 pt-1 pb-4">
                        <div class="bg-button-main rounded-2xl p-4 shadow-xl">
                            <div class="mb-3 flex items-center justify-between px-0">
                                <div class="flex flex-col">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-700 opacity-70">Total
                                        Item</p>
                                    <p class="font-bold text-gray-900"><span x-text="totalQty"></span></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-700 opacity-70">
                                        Total Bayar
                                        <span x-show="manualTotal !== null"
                                            class="ml-1 font-bold text-red-500">(Manual)</span>
                                    </p>

                                    <div x-data="{ isEditing: false }" @click.outside="isEditing = false" class="relative">
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

                                        <div x-show="isEditing" class="mt-1 flex items-center justify-end gap-2">
                                            <x-input-rupiah 
                                                value="0"
                                                placeholder="0"
                                                containerClass="w-36"
                                                decimals="3"
                                                @rupiah-change="manualTotal = $event.detail.value"
                                                x-init="$watch('isEditing', v => v && $el.dispatchEvent(new CustomEvent('update-rupiah-value', { detail: { value: manualTotal || totalPrice } })))"
                                                @keydown.enter.prevent="isEditing = false"
                                            />
                                            <div class="flex flex-col gap-1 mb-6">
                                                <button @click="isEditing = false"
                                                    class="rounded bg-gray-100 p-1.5 text-gray-600 hover:bg-gray-200"
                                                    title="Simpan">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                                <button @click="manualTotal = null; isEditing = false;"
                                                    class="rounded bg-red-50 p-1.5 text-red-500 hover:bg-red-100"
                                                    title="Reset ke Harga Otomatis">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <template x-if="manualTotal !== null && cart.length > 0">
                                        <p class="text-xs text-gray-900 mt-0.5 " title="Harga Asli Sebelum Edit">Harga Sistem:
                                            <span x-text="formatRupiah(systemCartTotal)" class="font-bold"></span>
                                        </p>
                                    </template>

                                </div>
                            </div>
                            <button @click="processCheckout()"
                                :disabled="cart.length === 0 || (paymentMethod === 'Cash' && cashReceived < totalPrice)"
                                :class="(cart.length === 0 || (paymentMethod === 'Cash' && cashReceived < totalPrice)) ?
                                'opacity-50 cursor-not-allowed' : ''"
                                class="text-button-main flex w-full items-center justify-center gap-2 rounded-xl bg-white py-3 font-bold shadow-sm transition-all hover:bg-gray-50 hover:shadow-md active:scale-95">
                                <span>BAYAR</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        {{-- R2 Customer Selection Modal --}}
        <x-modal name="r2-customer" title="Cari Pelanggan R1/R2" maxWidth="2xl" zIndex="z-[100]"
            x-on:modal-closed.window="if ($event.detail === 'r2-customer') closeR2Modal()">

            {{-- Type filter chips --}}
            <div class="mb-3 inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                <template x-for="opt in [{key:'all',label:'Semua'},{key:'r1',label:'R1'},{key:'r2',label:'R2'}]" :key="opt.key">
                    <button type="button" @click="setCustomerTypeFilter(opt.key)"
                        :class="customerTypeFilter === opt.key ? 'bg-button-main text-white shadow-sm' : 'text-gray-600 hover:bg-white'"
                        class="rounded-md px-3 py-1 text-xs font-bold uppercase tracking-wide transition-colors"
                        x-text="opt.label"></button>
                </template>
            </div>

            {{-- Search Input --}}
            <div class="mb-4">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" x-model="r2SearchQuery" @input="searchR2Customers()"
                        id="r2-customer-search-input"
                        placeholder="Cari nama, nomor HP, atau alamat..."
                        class="w-full rounded-lg border-2 border-gray-300 py-2.5 pl-10 pr-4 text-sm focus:border-button-main focus:outline-none focus:ring-2 focus:ring-button-main/20">
                </div>
            </div>

            {{-- Results Table --}}
            <div class="max-h-72 overflow-y-auto">
                <div x-show="isSearchingR2" class="py-8 text-center text-gray-400">
                    <svg class="mx-auto h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <p class="mt-2 text-sm">Mencari...</p>
                </div>

                <div x-show="!isSearchingR2 && r2SearchResults.length === 0"
                    class="py-8 text-center text-gray-400">
                    <p class="text-sm">Pelanggan tidak ditemukan</p>
                </div>

                <table x-show="!isSearchingR2 && r2SearchResults.length > 0" class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Nama</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Tipe</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Kontak</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Alamat</th>
                            <th class="px-4 py-2.5 text-center font-semibold text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(cust, index) in r2SearchResults" :key="cust.id">
                            <tr :id="'r2-row-' + index"
                                :class="{ 'bg-button-main/10 ring-2 ring-button-main ring-inset': r2HighlightedIndex === index }"
                                class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="cust.name"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                                        :class="(cust.type || 'r2') === 'r1' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700'"
                                        x-text="(cust.type || 'r2').toUpperCase()"></span>
                                </td>
                                <td class="px-4 py-3 text-gray-600" x-text="cust.phone_number"></td>
                                <td class="px-4 py-3 text-gray-600" x-text="cust.address"></td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="selectCustomer(cust); $dispatch('close-modal', 'r2-customer')" type="button"
                                        class="rounded-lg bg-button-main px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-button-hover transition-colors active:scale-95">
                                        Pilih
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-modal>

        {{-- Confirm Checkout Modal --}}
        <x-modal name="confirm-checkout" title="Konfirmasi" maxWidth="sm" zIndex="z-[100]">
            <div class="mt-2 text-sm text-gray-600">
                Apakah Anda yakin ingin memproses transaksi ini?
            </div>
            <x-slot name="footer">
                <button @click="$dispatch('close-modal', 'confirm-checkout')" 
                    class="rounded-lg bg-gray-500 px-4 py-1 text-white shadow-sm hover:bg-gray-600 transition-colors font-bold">
                    Cancel
                </button>
                <button @click="$dispatch('close-modal', 'confirm-checkout'); executeCheckout()" 
                    class="rounded-lg bg-button-main px-4 py-1 text-white shadow-sm hover:bg-button-hover transition-colors font-bold">
                    OK
                </button>
            </x-slot>
        </x-modal>

        {{-- Success Checkout Modal --}}
        <x-modal name="success-checkout" title="Informasi" maxWidth="sm" zIndex="z-[100]">
            <div class="mt-2 text-sm text-gray-600">
                Transaksi Berhasil!
            </div>
            <x-slot name="footer">
                <button @click="$dispatch('close-modal', 'success-checkout')" 
                    class="rounded-lg bg-button-main px-4 py-2 text-white shadow-sm hover:bg-button-hover transition-colors font-bold">
                    OK
                </button>
            </x-slot>
        </x-modal>

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
        </div>
    </div>

</body>

</html>
