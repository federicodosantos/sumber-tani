<x-cashier-layout :categories="$categories" :products="$products">

    <div class="flex items-center justify-end gap-4 px-8 py-4">

        {{-- SORT BUTTONS --}}
        <div class="relative -space-x-2" x-data="{ open: false }">

            <button @click="open = !open" type="button"
                :class="sortType ? 'bg-button-main border-gray-700 text-gray-700' :
                    'border-gray-300 text-gray-400 hover:bg-gray-100'"
                class="rounded-lg border-2 p-1.5 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" style="display:none"
                class="absolute right-0 z-50 mt-2 w-48 rounded-lg border bg-white shadow-lg">
                <div class="flex flex-col p-1">
                    <button @click="sortType = 'name_az'; open = false"
                        :class="sortType === 'name_az' ? 'bg-green-100 text-button-main' : 'text-gray-700 hover:bg-gray-50'"
                        class="sort-btn rounded-md px-4 py-2 text-left text-sm transition-colors">
                        Nama (A-Z)
                    </button>

                    <button @click="sortType = 'name_za'; open = false"
                        :class="sortType === 'name_za' ? 'bg-green-100 text-button-main' : 'text-gray-700 hover:bg-gray-50'"
                        class="sort-btn rounded-md px-4 py-2 text-left text-sm transition-colors">
                        Nama (Z-A)
                    </button>

                    <div class="my-1 border-t border-gray-100"></div>

                    <button @click="sortType = 'price_low'; open = false"
                        :class="sortType === 'price_low' ? 'bg-green-100 text-button-main' : 'text-gray-700 hover:bg-gray-50'"
                        class="sort-btn rounded-md px-4 py-2 text-left text-sm transition-colors">
                        Harga Terendah
                    </button>

                    <button @click="sortType = 'price_high'; open = false"
                        :class="sortType === 'price_high' ? 'bg-green-100 text-button-main' : 'text-gray-700 hover:bg-gray-50'"
                        class="sort-btn rounded-md px-4 py-2 text-left text-sm transition-colors">
                        Harga Tertinggi
                    </button>

                    <div class="my-1 border-t border-gray-100"></div>

                    <button @click="sortType = 'stock_high'; open = false"
                        :class="sortType === 'stock_high' ? 'bg-green-100 text-button-main' : 'text-gray-700 hover:bg-gray-50'"
                        class="sort-btn rounded-md px-4 py-2 text-left text-sm transition-colors">
                        Stok Terbanyak
                    </button>
                    <button @click="sortType = 'stock_low'; open = false"
                        :class="sortType === 'stock_low' ? 'bg-green-100 text-button-main' : 'text-gray-700 hover:bg-gray-50'"
                        class="sort-btn rounded-md px-4 py-2 text-left text-sm transition-colors">
                        Stok Tersedikit
                    </button>
                </div>
            </div>
        </div>

        {{-- SEARCH INPUT (REALTIME) --}}
        <div class="relative w-80">
            <input type="text" x-model="search" placeholder="Cari Produk"
                class="focus:border-button-main w-full rounded-lg border-2 border-gray-300 py-1 pl-4 pr-4 focus:outline-none">
        </div>
    </div>

    <div class="flex flex-col px-8 pb-2">
        <div class="flex items-center gap-2">
            <p class="font-bold">Mode Harga: </p>

            <button @click="setPriceMode('consument')"
                :class="[
                    priceMode === 'consument' ?
                    'bg-button-main text-white shadow-md' :
                    'bg-white hover:bg-gray-100'
                ]"
                class="mode-btn border-button-hover rounded-lg border-2 px-3 py-1 text-sm font-medium transition-all duration-200">
                Konsumen
            </button>

            <button @click="setPriceMode('r1')"
                :class="[
                    priceMode === 'r1' ?
                    'bg-button-main text-white shadow-md' :
                    'bg-white hover:bg-gray-100'
                ]"
                class="mode-btn border-button-hover rounded-lg border-2 px-3 py-1 text-sm font-medium transition-all duration-200">
                R1
            </button>

            <button @click="openR2Modal()"
                :class="[
                    priceMode === 'r2' ?
                    'bg-button-main text-white shadow-md' :
                    'bg-white hover:bg-gray-100'
                ]"
                class="mode-btn border-button-hover rounded-lg border-2 px-3 py-1 text-sm font-medium transition-all duration-200">
                R2
            </button>

            {{-- Selected R2 Customer Badge --}}
            <template x-if="selectedCustomer">
                <div class="flex items-center gap-2 ml-2 rounded-lg border-2 border-button-main/50 bg-button-main/10 px-3 py-1">
                    <svg class="h-4 w-4 text-button-hover shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-sm font-semibold text-gray-800" x-text="'R2 — ' + selectedCustomer.name"></span>
                    <button @click="removeR2Customer()" type="button"
                        class="text-gray-400 hover:text-red-500 transition-colors" title="Hapus Pelanggan R2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>
        <p class="mt-1 pl-24 text-[10px] text-gray-500">
            *Jika mode harga diganti, harga item di keranjang akan otomatis mengikuti harga sistem terbaru.
        </p>
    </div>

    <div class="flex-1 overflow-y-auto px-8 py-6">

        <div x-show="filteredProducts.length === 0" class="flex h-64 flex-col items-center justify-center text-gray-500"
            style="display: none;">
            <p class="text-lg font-medium">Produk tidak ditemukan</p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
            <template x-for="product in filteredProducts" :key="product.id">
                <button @click="addToCart(product)" :disabled="product.stock_opname <= 0"
                    :class="cart.some(item => item.id === product.id) ?
                        'border-button-hover bg-button-main/20 scale-[1.02] shadow-md' :
                        'border-gray-200 bg-white hover:shadow-lg hover:scale-[1.01]'"
                    class="group relative w-full rounded-2xl border-2 p-4 text-left transition-all">

                    <div class="mb-3 flex items-start justify-between">
                        <h3 class="text-lg font-bold leading-tight text-gray-900" x-text="product.name"></h3>

                        <p class="whitespace-nowrap text-lg font-bold text-gray-900"
                            x-text="formatRupiah(getPrice(product))">
                        </p>
                    </div>

                    <p class="mb-4 truncate text-xs leading-relaxed text-gray-700" x-text="product.description || '-'">
                    </p>

                    <div class="flex items-end justify-between">
                        <div>
                            <p class="mb-0.5 text-xs font-semibold text-gray-900">Kategori:</p>
                            <p class="text-sm font-semibold text-gray-900" x-text="product.category_name"></p>
                        </div>

                        <div class="text-right text-xs text-gray-900">
                            <template x-if="product.stock_opname > 0">
                                <span>Sisa Stok: <span class="font-bold" x-text="product.stock_opname"></span></span>
                            </template>
                            <template x-if="product.stock_opname <= 0">
                                <span class="font-bold text-red-500">STOK HABIS</span>
                            </template>
                        </div>
                    </div>

                </button>
            </template>
        </div>
    </div>

    <style>
        .sort-btn {
            @apply w-full rounded px-2 py-2 text-sm hover:bg-button-main hover:text-white text-left;
        }
    </style>

</x-cashier-layout>
