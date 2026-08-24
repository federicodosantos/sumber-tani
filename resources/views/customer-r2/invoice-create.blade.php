<x-app-layout>
    <div x-data="manualInvoiceForm()" class="py-4 lg:py-6 font-mont">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back Button --}}
            <div>
                <a href="{{ route('customer-r2.show', $customer->id) }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke Detail Pelanggan
                </a>
            </div>

            {{-- Header --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm" style="border: 1px solid #e5e7eb;">
                <div class="flex items-start justify-between flex-wrap gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 border border-amber-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-amber-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487 18.549 2.799a2.121 2.121 0 1 1 3 3L5.12 22.227a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Tambah Nota Manual</h1>
                            <p class="mt-1 text-sm text-gray-500 max-w-2xl">
                                Pencatatan transaksi historis untuk
                                <span class="font-semibold text-gray-700">{{ $customer->name }}</span>.
                                Nota manual <span class="font-semibold text-amber-700">tidak mengurangi stok sistem</span>.
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-amber-700 border border-amber-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Mode Manual
                    </span>
                </div>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-red-500 shrink-0 mt-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-red-700 mb-1">Periksa kembali isian Anda</p>
                            <ul class="text-xs text-red-700 list-disc pl-5 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('customer-r2.invoice.store', $customer->id) }}" @submit.prevent="confirmSave($event)">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    {{-- LEFT: Product Picker + Cart --}}
                    <div class="lg:col-span-2 space-y-6">

                        {{-- Product Picker Card --}}
                        <div class="rounded-2xl bg-white shadow-sm overflow-hidden" style="border: 1px solid #e5e7eb;">
                            {{-- Toolbar --}}
                            <div class="border-b border-gray-100 p-5 space-y-4">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Pilih Produk</h2>
                                    <div class="relative w-full sm:w-72">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                        </svg>
                                        <input type="text" x-model="search" @input="page = 1" placeholder="Cari nama produk..."
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-3 py-2 text-sm focus:outline-none focus:border-button-main focus:bg-white transition-colors">
                                    </div>
                                </div>

                                {{-- Category Chips --}}
                                <div class="flex items-center gap-2 overflow-x-auto pb-1 -mx-1 px-1" style="scrollbar-width: thin;">
                                    <button type="button" @click="categoryId = null; page = 1"
                                        :class="categoryId === null ? 'bg-button-main text-gray-900 border-button-main shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                        class="shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer">
                                        Semua
                                    </button>
                                    @foreach ($categories as $category)
                                        <button type="button" @click="categoryId = {{ $category->id }}; page = 1"
                                            :class="categoryId === {{ $category->id }} ? 'bg-button-main text-gray-900 border-button-main shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                            class="shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer">
                                            {{ $category->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Product Grid --}}
                            <div class="p-5">
                                <template x-if="paginatedProducts.length === 0">
                                    <div class="py-16 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-10 w-10 text-gray-300">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                        </svg>
                                        <p class="mt-3 text-sm text-gray-500">Tidak ada produk yang cocok.</p>
                                        <button type="button" @click="search = ''; categoryId = null; page = 1" class="mt-2 text-xs font-semibold text-button-main hover:underline cursor-pointer">Reset filter</button>
                                    </div>
                                </template>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <template x-for="product in paginatedProducts" :key="product.id">
                                        <button type="button" @click="addToCart(product)"
                                            :class="cart.some(i => i.id === product.id) ? 'border-button-main bg-button-main/5 ring-1 ring-button-main/30' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50/60'"
                                            class="group relative text-left rounded-xl border p-3.5 transition-all cursor-pointer">
                                            <div class="flex items-start justify-between gap-2 mb-2">
                                                <p class="text-sm font-bold text-gray-900 leading-snug" x-text="product.name"></p>
                                                <p class="text-xs font-bold text-gray-900 whitespace-nowrap shrink-0" x-text="formatRupiah(getBasePrice(product))"></p>
                                            </div>
                                            <div class="flex items-center justify-between text-[10px]">
                                                <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 font-semibold uppercase tracking-wider text-gray-600" x-text="product.category_name"></span>
                                                <template x-if="cart.some(i => i.id === product.id)">
                                                    <span class="inline-flex items-center gap-1 font-bold text-button-hover">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3 w-3">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                        </svg>
                                                        DI KERANJANG
                                                    </span>
                                                </template>
                                                <template x-if="!cart.some(i => i.id === product.id)">
                                                    <span class="text-gray-400" x-text="'Stok: ' + (product.stock_opname || 0)"></span>
                                                </template>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Pagination Bar --}}
                            <div x-show="filteredProducts.length > 0" class="border-t border-gray-100 bg-gray-50/50 px-5 py-3 flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-2 text-xs text-gray-600">
                                    <label for="per-page" class="font-semibold uppercase tracking-wider text-[10px] text-gray-500">Per halaman</label>
                                    <select id="per-page" x-model.number="perPage" @change="page = 1"
                                        class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-semibold focus:outline-none focus:border-button-main">
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-[11px] text-gray-500">
                                        <span x-text="rangeStart"></span>–<span x-text="rangeEnd"></span> dari <span x-text="filteredProducts.length"></span>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="page = Math.max(1, page - 1)" :disabled="page === 1"
                                            :class="page === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-white hover:shadow-sm border-gray-200 cursor-pointer'"
                                            class="rounded-md border border-transparent p-1.5 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                            </svg>
                                        </button>
                                        <span class="text-xs font-bold text-gray-700 px-2"><span x-text="page"></span> / <span x-text="totalPages"></span></span>
                                        <button type="button" @click="page = Math.min(totalPages, page + 1)" :disabled="page === totalPages"
                                            :class="page === totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-white hover:shadow-sm border-gray-200 cursor-pointer'"
                                            class="rounded-md border border-transparent p-1.5 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CART --}}
                        <div class="rounded-2xl bg-white shadow-sm overflow-hidden" style="border: 1px solid #e5e7eb;">
                            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Keranjang</h2>
                                    <span class="inline-flex items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600 min-w-[20px]" x-text="cart.length"></span>
                                </div>
                                <button x-show="cart.length > 0" type="button" @click="$dispatch('open-modal', 'confirm-clear-cart')"
                                    class="text-[10px] font-semibold uppercase tracking-wider text-red-400 hover:text-red-600 transition-colors cursor-pointer">
                                    Kosongkan
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-[10px] font-semibold uppercase tracking-wider text-gray-500">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left">Produk</th>
                                            <th class="px-3 py-2.5 text-right">Harga (Rp)</th>
                                            <th class="px-3 py-2.5 text-center">Qty</th>
                                            <th class="px-4 py-2.5 text-right">Subtotal</th>
                                            <th class="px-3 py-2.5"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, idx) in cart" :key="item.id">
                                            <tr class="border-t border-gray-100 hover:bg-gray-50/40 transition-colors">
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-gray-900" x-text="item.name"></p>
                                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                                        <span>Harga sistem: </span>
                                                        <span x-text="formatRupiah(item.basePrice)"></span>
                                                        <template x-if="Number(item.price) !== Number(item.basePrice)">
                                                            <span class="ml-1 inline-flex items-center rounded bg-amber-50 border border-amber-200 px-1 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700">DIUBAH</span>
                                                        </template>
                                                    </p>
                                                </td>
                                                <td class="px-3 py-3 text-right">
                                                    <x-input-rupiah 
                                                        containerClass="w-28 inline-block"
                                                        class="!py-1 text-sm tabular-nums"
                                                        @rupiah-change="item.price = $event.detail.value"
                                                        x-init="updateValues(item.price)"
                                                    />
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    <div class="inline-flex items-center gap-1">
                                                        <button type="button" @click="item.qty = Math.max(0.001, (Number(item.qty) || 0.001) - 1)" class="h-7 w-7 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center cursor-pointer">−</button>
                                                        <input type="number" min="0.001" step="0.001" x-model.number="item.qty"
                                                            class="w-12 rounded-md border border-gray-200 px-1 py-1 text-center text-sm tabular-nums focus:outline-none focus:border-button-main focus:bg-white">
                                                        <button type="button" @click="item.qty = (Number(item.qty) || 0) + 1" class="h-7 w-7 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors flex items-center justify-center cursor-pointer">+</button>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold text-gray-900 tabular-nums" x-text="formatRupiah((item.price || 0) * (item.qty || 0))"></td>
                                                <td class="px-3 py-3 text-center">
                                                    <button type="button" @click="removeFromCart(idx)" class="text-gray-300 hover:text-red-500 transition-colors cursor-pointer" title="Hapus">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="cart.length === 0">
                                            <td colspan="5" class="py-12 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-8 w-8 text-gray-300">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                                                </svg>
                                                <p class="mt-2 text-sm text-gray-400 italic">Keranjang kosong. Pilih produk di atas.</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Settings & Summary --}}
                    <div class="space-y-5 lg:sticky lg:top-4 lg:self-start">

                        {{-- Date --}}
                        <div class="rounded-2xl bg-white p-5 shadow-sm space-y-3" style="border: 1px solid #e5e7eb;">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Tanggal Nota</h2>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <input type="date" x-model="createdAt" :max="maxDate"
                                @click="$el.showPicker()"
                                onkeydown="return false"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:border-button-main focus:bg-white transition-colors cursor-pointer">
                            <p class="text-[10px] text-gray-400">Boleh diisi tanggal lampau untuk pencatatan historis.</p>
                        </div>

                        {{-- Payment Method --}}
                        <div class="rounded-2xl bg-white p-5 shadow-sm space-y-4" style="border: 1px solid #e5e7eb;">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Metode Bayar</h2>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-for="m in ['Cash','Transfer','QRIS','Kredit']" :key="m">
                                    <button type="button" @click="paymentMethod = m"
                                        :class="paymentMethod === m ? 'bg-button-main text-gray-900 border-button-main shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                        class="rounded-lg border px-3 py-2.5 text-xs font-bold uppercase tracking-wider transition-colors cursor-pointer"
                                        x-text="m"></button>
                                </template>
                            </div>

                            {{-- Lunas Toggle --}}
                            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">Status</p>
                                    <p class="text-[10px] text-gray-400">Bisa Lunas walau metode Kredit.</p>
                                </div>
                                <button type="button" @click="isPaid = !isPaid"
                                    :class="isPaid ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
                                    class="rounded-full border px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider transition-colors cursor-pointer">
                                    <span x-text="isPaid ? 'LUNAS' : 'BELUM LUNAS'"></span>
                                </button>
                            </div>

                            {{-- Cash specific fields --}}
                            <template x-if="paymentMethod === 'Cash' && isPaid">
                                <div class="space-y-2 pt-3 border-t border-gray-100">
                                    <x-input-rupiah 
                                        label="Uang Diterima" 
                                        value="{{ old('cash_received', 0) }}"
                                        @rupiah-change="cashReceived = $event.detail.value"
                                        placeholder="0"
                                    />
                                    <p class="text-[10px] text-gray-500">Kembalian: <span class="font-bold text-gray-700" x-text="formatRupiah(Math.max(0, (cashReceived || 0) - grandTotal))"></span></p>
                                </div>
                            </template>
                        </div>

                        {{-- Discount & Note --}}
                        <div class="rounded-2xl bg-white p-5 shadow-sm space-y-3" style="border: 1px solid #e5e7eb;">
                            <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Lainnya</h2>
                            <x-input-rupiah 
                                label="Diskon (Rp)" 
                                value="{{ old('discount', 0) }}"
                                @rupiah-change="discount = $event.detail.value"
                                placeholder="0"
                            />
                            <div>
                                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Catatan (opsional)</label>
                                <textarea x-model="note" rows="2"
                                    placeholder="Contoh: nota susulan periode Januari"
                                    class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:border-button-main focus:bg-white transition-colors"></textarea>
                            </div>
                        </div>

                        {{-- Summary --}}
                        <div class="rounded-2xl bg-white shadow-sm overflow-hidden" style="border: 1px solid #e5e7eb;">
                            <div class="px-5 py-4 space-y-2 text-sm">
                                <div class="flex justify-between text-gray-500">
                                    <span>Subtotal (<span x-text="totalQty"></span> item)</span>
                                    <span class="tabular-nums" x-text="formatRupiah(subtotal)"></span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>Diskon</span>
                                    <span class="tabular-nums" x-text="(discount > 0 ? '- ' : '') + formatRupiah(discount || 0)"></span>
                                </div>
                                <div class="flex justify-between border-t border-gray-100 pt-3 mt-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-gray-700">Total</span>
                                    <span class="text-lg font-bold text-gray-900 tabular-nums" x-text="formatRupiah(grandTotal)"></span>
                                </div>
                                <template x-if="!isPaid && grandTotal > 0">
                                    <div class="flex justify-between border-t border-gray-100 pt-2 mt-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-red-600">Akan jadi hutang</span>
                                        <span class="text-sm font-bold text-red-600 tabular-nums" x-text="formatRupiah(grandTotal)"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="bg-gray-50 px-5 py-4 border-t border-gray-100">
                                <button type="submit" :disabled="cart.length === 0 || submitting"
                                    :class="cart.length === 0 || submitting ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-button-main hover:bg-button-hover text-gray-900 cursor-pointer shadow-sm active:scale-[0.99]'"
                                    class="w-full rounded-lg px-4 py-3 text-sm font-bold uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer">
                                    <template x-if="!submitting">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    </template>
                                    <template x-if="submitting">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-4 w-4 animate-spin">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.25" />
                                            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                        </svg>
                                    </template>
                                    <span x-text="submitting ? 'MENYIMPAN...' : 'SIMPAN NOTA'"></span>
                                </button>
                                <p x-show="cart.length === 0" class="mt-2 text-center text-[10px] text-gray-400 italic">Tambahkan minimal 1 produk.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Modal Konfirmasi Simpan Nota --}}
        <x-modal name="confirm-save-trx" title="KONFIRMASI SIMPAN NOTA" maxWidth="md">
            <div class="p-6 space-y-3">
                <div class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Pelanggan</span>
                        <span class="font-semibold text-gray-900 text-right">
                            {{ $customer->name }}
                            <span class="ml-1 inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-blue-700">{{ strtoupper($customer->type ?? 'r2') }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Tanggal Nota</span>
                        <span class="font-semibold text-gray-900" x-text="formatDate(createdAt)"></span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Item</span>
                        <span class="font-semibold text-gray-900"><span x-text="cart.length"></span> jenis (<span x-text="totalQty"></span> qty)</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Total</span>
                        <span class="font-bold text-gray-900 text-lg tabular-nums" x-text="formatRupiah(grandTotal)"></span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Metode</span>
                        <span class="font-semibold text-gray-900" x-text="paymentMethod"></span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Status</span>
                        <span :class="isPaid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                            x-text="isPaid ? 'LUNAS' : 'BELUM LUNAS'"></span>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                <button type="button" @click="$dispatch('close-modal', 'confirm-save-trx')"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-50 cursor-pointer">
                    Batal
                </button>
                <button type="button" @click="actuallySubmit()"
                    class="rounded-lg bg-button-main hover:bg-button-hover px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-900 shadow-sm cursor-pointer">
                    Ya, Simpan
                </button>
            </div>
        </x-modal>

        {{-- Modal Konfirmasi Kosongkan Keranjang --}}
        <x-modal name="confirm-clear-cart" title="Kosongkan Keranjang" maxWidth="sm">
            <div class="p-1">
                <p class="text-sm text-gray-500">
                    Apakah Anda yakin ingin menghapus semua item dari keranjang? Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <x-slot name="footer">
                <button type="button" @click="$dispatch('close-modal', 'confirm-clear-cart')"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="button" @click="cart = []; $dispatch('close-modal', 'confirm-clear-cart')"
                    class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white hover:bg-red-700 shadow-sm transition-colors cursor-pointer">
                    Ya, Kosongkan
                </button>
            </x-slot>
        </x-modal>
    </div>

    @push('scripts')
        <script>
            function manualInvoiceForm() {
                return {
                    products: @json($products),
                    customPrices: @json($customPrices),
                    search: '',
                    categoryId: null,
                    page: 1,
                    perPage: 20,
                    cart: [],
                    createdAt: new Date().toISOString().slice(0, 10),
                    maxDate: new Date().toISOString().slice(0, 10),
                    paymentMethod: 'Cash',
                    isPaid: true,
                    cashReceived: 0,
                    discount: 0,
                    note: '',
                    submitting: false,
                    _formAction: '',
                    _csrfToken: '',

                    get filteredProducts() {
                        const s = this.search.trim().toLowerCase();
                        return this.products.filter(p => {
                            if (this.categoryId !== null && Number(p.item_category_id) !== Number(this.categoryId)) return false;
                            if (s && !p.name.toLowerCase().includes(s)) return false;
                            return true;
                        });
                    },

                    get totalPages() {
                        return Math.max(1, Math.ceil(this.filteredProducts.length / this.perPage));
                    },

                    get paginatedProducts() {
                        if (this.page > this.totalPages) this.page = this.totalPages;
                        const start = (this.page - 1) * this.perPage;
                        return this.filteredProducts.slice(start, start + this.perPage);
                    },

                    get rangeStart() {
                        if (this.filteredProducts.length === 0) return 0;
                        return (this.page - 1) * this.perPage + 1;
                    },

                    get rangeEnd() {
                        return Math.min(this.page * this.perPage, this.filteredProducts.length);
                    },

                    getBasePrice(product) {
                        const customId = product.id;
                        if (this.customPrices && this.customPrices[customId]) {
                            return Number(this.customPrices[customId]);
                        }
                        return Number(product.price_r2 || product.price_r1 || product.price_consument || 0);
                    },

                    addToCart(product) {
                        const existing = this.cart.find(i => i.id === product.id);
                        if (existing) {
                            existing.qty = (Number(existing.qty) || 0) + 1;
                            return;
                        }
                        const basePrice = this.getBasePrice(product);
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            basePrice: basePrice,
                            price: basePrice,
                            qty: 1,
                        });
                    },

                    removeFromCart(idx) {
                        this.cart.splice(idx, 1);
                    },

                    get subtotal() {
                        return this.cart.reduce((sum, i) => sum + (Number(i.price) || 0) * (Number(i.qty) || 0), 0);
                    },

                    get grandTotal() {
                        return Math.max(0, this.subtotal - (Number(this.discount) || 0));
                    },

                    get totalQty() {
                        return this.cart.reduce((sum, i) => sum + (Number(i.qty) || 0), 0);
                    },

                    formatRupiah(n) {
                        return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
                    },

                    formatDate(s) {
                        if (!s) return '-';
                        const [y, m, d] = s.split('-');
                        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        return `${parseInt(d,10)} ${months[parseInt(m,10)-1]} ${y}`;
                    },

                    confirmSave(e) {
                        if (this.cart.length === 0 || this.submitting) return;
                        if (this.paymentMethod === 'Cash' && this.isPaid && Number(this.cashReceived || 0) < this.grandTotal) {
                            alert('Uang diterima kurang dari total transaksi.');
                            return;
                        }
                        this._formAction = e.target.action;
                        this._csrfToken = e.target.querySelector('input[name="_token"]').value;
                        this.$dispatch('open-modal', 'confirm-save-trx');
                    },

                    actuallySubmit() {
                        this.$dispatch('close-modal', 'confirm-save-trx');
                        this.submitting = true;

                        const append = (f, name, value) => {
                            const inp = document.createElement('input');
                            inp.type = 'hidden';
                            inp.name = name;
                            inp.value = value === null || value === undefined ? '' : value;
                            f.appendChild(inp);
                        };

                        const f = document.createElement('form');
                        f.method = 'POST';
                        f.action = this._formAction;
                        append(f, '_token', this._csrfToken);
                        this.cart.forEach((it, i) => {
                            append(f, `items[${i}][id]`, it.id);
                            append(f, `items[${i}][price]`, Number(it.price) || 0);
                            append(f, `items[${i}][qty]`, Number(it.qty) || 0);
                            append(f, `items[${i}][basePrice]`, Number(it.basePrice) || 0);
                        });
                        append(f, 'totalQty', this.totalQty);
                        append(f, 'totalAmount', this.grandTotal);
                        append(f, 'discount', Number(this.discount) || 0);
                        append(f, 'payment_method', this.paymentMethod);
                        append(f, 'is_paid', this.isPaid ? 1 : 0);
                        if (this.paymentMethod === 'Cash') {
                            append(f, 'cash_received', Number(this.cashReceived) || 0);
                            if (this.isPaid) {
                                append(f, 'change_amount', Math.max(0, (Number(this.cashReceived) || 0) - this.grandTotal));
                            }
                        }
                        append(f, 'created_at', this.createdAt);
                        if (this.note) append(f, 'note', this.note);

                        document.body.appendChild(f);
                        f.submit();
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
