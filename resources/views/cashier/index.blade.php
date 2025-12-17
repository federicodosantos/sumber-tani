<x-cashier-layout :categories="$categories">

    {{-- =========================
        FILTER + SEARCH
    ========================= --}}
    <form action="{{ route('cashier') }}" method="GET" class="flex items-center justify-end gap-4 px-8 py-4">
        <input type="hidden" name="category" value="{{ request('category') }}">

        {{-- SORT --}}
        <div class="relative -space-x-2" x-data="{ open: false }">
            <button @click="open = !open" type="button"
                class="rounded-lg border-2 p-1.5 transition-colors
                {{ request('sort') ? 'bg-button-main text-white' : 'border-gray-300 text-gray-400 hover:bg-gray-100' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" style="display:none"
                class="absolute right-0 z-50 mt-2 w-48 rounded-lg border bg-white shadow-lg">
                <div class="p-1">
                    <button type="submit" name="sort" value="name_az" class="sort-btn">Nama (A-Z)</button>
                    <button type="submit" name="sort" value="name_za" class="sort-btn">Nama (Z-A)</button>
                    <div class="my-1 border-t"></div>
                    <button type="submit" name="sort" value="price_low" class="sort-btn">Harga Terendah</button>
                    <button type="submit" name="sort" value="price_high" class="sort-btn">Harga Tertinggi</button>
                    <div class="my-1 border-t"></div>
                    <button type="submit" name="sort" value="stock_high" class="sort-btn">Stok Terbanyak</button>
                    <button type="submit" name="sort" value="stock_low" class="sort-btn">Stok Sedikit</button>
                </div>
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="relative w-80">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Produk"
                class="focus:border-button-main w-full rounded-lg border-2 border-gray-300 py-1 pl-10 pr-4 focus:outline-none">
        </div>
    </form>

    {{-- =========================
        MODE HARGA
    ========================= --}}
    <div class="flex gap-2 px-8 pb-2">
        <p class="font-bold">Mode Harga: </p>
        <button onclick="setPriceMode('consument')" data-mode="consument"
            class="mode-btn price-mode-btn bg-white hover:bg-gray-100 px-2 py-1 rounded-lg font-md border-2 border-button-hover">
            Konsumen
        </button>

        <button onclick="setPriceMode('r1')" data-mode="r1"
            class="mode-btn price-mode-btn bg-white hover:bg-gray-100 px-2 py-1 rounded-lg font-md border-2 border-button-hover">
            R1
        </button>

        <button onclick="setPriceMode('r2')" data-mode="r2"
            class="mode-btn price-mode-btn bg-white hover:bg-gray-100 px-2 py-1 rounded-lg font-md border-2 border-button-hover">
            R2
        </button>
    </div>

    {{-- =========================
        PRODUCT GRID
    ========================= --}}
    <div class="flex-1 overflow-y-auto px-8 py-6">
        @if ($products->isEmpty())
            <div class="flex h-64 flex-col items-center justify-center text-gray-500">
                <p class="text-lg font-medium">Produk tidak ditemukan</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                @foreach ($products as $product)
                    <button data-product-id="{{ $product->id }}"
                        data-price-consument="{{ $product->price_consument ?? 0 }}"
                        data-price-r1="{{ $product->price_r1 ?? 0 }}" data-price-r2="{{ $product->price_r2 ?? 0 }}"
                        @click="addToCart('{{ $product->id }}', '{{ $product->name }}', {{ $product->stock_opname }}, getActivePrice('{{ $product->id }}'))"
                        class="product-card border-2 rounded-2xl p-4 text-left bg-white transition hover:shadow-lg">

                        <div class="mb-2 flex justify-between">
                            <h3 class="text-lg font-bold">{{ $product->name }}</h3>
                            <p class="price-display text-lg font-bold">
                                Rp {{ number_format($product->price_consument ?? 0, 0, ',', '.') }}
                            </p>
                        </div>

                        <p class="mb-3 text-xs text-gray-600">
                            {{ $product->description ?? '-' }}
                        </p>

                        <div class="flex justify-between text-xs">
                            <span>{{ $product->category_name }}</span>
                            <span>Sisa: <b>{{ $product->stock_opname }}</b></span>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- =========================
        SCRIPT
    ========================= --}}
    <script>
        let priceMode = 'consument';

        function setPriceMode(mode) {
            priceMode = mode;

            // reset semua button
            document.querySelectorAll('.mode-btn').forEach(btn => {
                btn.classList.remove('bg-button-main/50');
                btn.classList.add('bg-white');
            });

            // aktifkan button terpilih
            const activeBtn = document.querySelector(`.mode-btn[data-mode="${mode}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('bg-white');
                activeBtn.classList.add('bg-button-main/50');
            }

            updateDisplayedPrices(); // 🔥 INI SEKARANG ADA
        }

        function getActivePrice(productId) {
            const card = document.querySelector(`[data-product-id="${productId}"]`);
            if (!card) return 0;

            switch (priceMode) {
                case 'r1':
                    return parseInt(card.dataset.priceR1) || 0;
                case 'r2':
                    return parseInt(card.dataset.priceR2) || 0;
                default:
                    return parseInt(card.dataset.priceConsument) || 0;
            }
        }

        function updateDisplayedPrices() {
            document.querySelectorAll('.product-card').forEach(card => {
                let price = card.dataset.priceConsument;

                if (priceMode === 'r1') price = card.dataset.priceR1;
                if (priceMode === 'r2') price = card.dataset.priceR2;

                const priceEl = card.querySelector('.price-display');
                if (priceEl) {
                    priceEl.innerText =
                        'Rp ' + new Intl.NumberFormat('id-ID').format(price);
                }
            });
        }

        // default aktif saat load
        document.addEventListener('DOMContentLoaded', () => {
            setPriceMode(priceMode);
        });
    </script>
    <script>
        function lockPriceModeButtons(lock = true) {
            document.querySelectorAll('.price-mode-btn').forEach(btn => {
                btn.disabled = lock;

                if (lock) {
                    btn.classList.add(
                        'opacity-50',
                        'cursor-not-allowed'
                    );
                } else {
                    btn.classList.remove(
                        'opacity-50',
                        'cursor-not-allowed'
                    );
                }
            });
        }
    </script>



    <style>
        .sort-btn {
            @apply w-full rounded px-2 py-2 text-sm hover:bg-blue-500 hover:text-white text-left;
        }

        .mode-btn {
            @apply rounded px-3 py-1 text-sm font-semibold;
        }
    </style>

</x-cashier-layout>
