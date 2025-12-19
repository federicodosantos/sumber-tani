<x-cashier-layout :categories="$categories" :products="$products">

    {{-- =========================
        FILTER + SEARCH (CLIENT SIDE)
    ========================= --}}
    <div class="flex items-center justify-end gap-4 px-8 py-4">
        
        {{-- SORT BUTTONS --}}
        <div class="relative -space-x-2" x-data="{ open: false }">
            <button @click="open = !open" type="button" class="rounded-lg border-2 p-1.5 transition-colors border-gray-300 text-gray-400 hover:bg-gray-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 4h18M3 8h12m-8 8h6" stroke-width="2" stroke-linecap="round"/></svg>
            </button>

            <div x-show="open" @click.outside="open = false" style="display:none" class="absolute right-0 z-50 mt-2 w-48 rounded-lg border bg-white shadow-lg">
                <div class="p-1 flex flex-col">
                    <button @click="sortType = 'name_az'; open=false" class="sort-btn">Nama (A-Z)</button>
                    <button @click="sortType = 'name_za'; open=false" class="sort-btn">Nama (Z-A)</button>
                    <div class="my-1 border-t"></div>
                    <button @click="sortType = 'price_low'; open=false" class="sort-btn">Harga Terendah</button>
                    <button @click="sortType = 'price_high'; open=false" class="sort-btn">Harga Tertinggi</button>
                    <div class="my-1 border-t"></div>
                    <button @click="sortType = 'stock_high'; open=false" class="sort-btn">Stok Terbanyak</button>
                </div>
            </div>
        </div>

        {{-- SEARCH INPUT (REALTIME) --}}
        <div class="relative w-80">
            <input type="text" x-model="search" placeholder="Cari Produk (Nama)"
                class="focus:border-button-main w-full rounded-lg border-2 border-gray-300 py-1 pl-4 pr-4 focus:outline-none">
        </div>
    </div>

    {{-- =========================
        MODE HARGA
    ========================= --}}
    <div class="flex gap-2 px-8 pb-2">
        <p class="font-bold">Mode Harga: </p>
        
        <button @click="priceMode = 'consument'" 
            :class="priceMode === 'consument' ? 'bg-button-main text-white' : 'bg-white hover:bg-gray-100'"
            class="mode-btn border-2 border-button-hover rounded-lg px-2 py-1">
            Konsumen
        </button>

        <button @click="priceMode = 'r1'" 
            :class="priceMode === 'r1' ? 'bg-button-main text-white' : 'bg-white hover:bg-gray-100'"
            class="mode-btn border-2 border-button-hover rounded-lg px-2 py-1">
            R1
        </button>

        <button @click="priceMode = 'r2'" 
            :class="priceMode === 'r2' ? 'bg-button-main text-white' : 'bg-white hover:bg-gray-100'"
            class="mode-btn border-2 border-button-hover rounded-lg px-2 py-1">
            R2
        </button>
    </div>

    {{-- =========================
        PRODUCT GRID (ALPINE LOOP)
    ========================= --}}
    <div class="flex-1 overflow-y-auto px-8 py-6">
        
        <div x-show="filteredProducts.length === 0" class="flex h-64 flex-col items-center justify-center text-gray-500" style="display: none;">
            <p class="text-lg font-medium">Produk tidak ditemukan</p>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
            <template x-for="product in filteredProducts" :key="product.id">
                <button @click="addToCart(product)"
                    class="product-card border-2 rounded-2xl p-4 text-left bg-white transition hover:shadow-lg hover:border-button-main">

                    <div class="mb-2 flex justify-between">
                        <h3 class="text-lg font-bold" x-text="product.name"></h3>
                        <p class="text-lg font-bold" x-text="formatRupiah(getPrice(product))"></p>
                    </div>

                    <p class="mb-3 text-xs text-gray-600 truncate" x-text="product.description || '-'"></p>

                    <div class="flex justify-between text-xs text-gray-500">
                        <span x-text="product.category_name"></span>
                        <span>Sisa: <b x-text="product.stock_opname"></b></span>
                    </div>
                </button>
            </template>
        </div>
    </div>

    <style>
        .sort-btn { @apply w-full rounded px-2 py-2 text-sm hover:bg-button-main hover:text-white text-left; }
    </style>

</x-cashier-layout>