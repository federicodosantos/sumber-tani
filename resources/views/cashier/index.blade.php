<x-cashier-layout :categories="$categories">
    <header class="flex items-center justify-end gap-4 px-8 py-4">
        <button id="filterBtn"
            class="rounded-lg border-2 border-gray-300 p-2.5 transition-colors hover:bg-gray-50">
            <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
        </button>

        <div class="relative w-80">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" placeholder="Cari Produk"
                class="focus:border-button-main w-full rounded-lg border-2 border-gray-300 py-2.5 pl-10 pr-4 transition-colors focus:outline-none">
        </div>
    </header>

    <div class="flex-1 overflow-y-auto px-8 py-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <button
                    @click="addToCart('{{ $product->id }}', '{{ $product->name }}', {{ $product->stock_opname }}, {{ $product->price ?? 0 }})"
                    class="border-3 border-button-main w-full rounded-2xl bg-white p-4 text-left transition-all hover:shadow-lg">
                    <div class="mb-3 flex items-start justify-between">
                        <h3 class="text-lg font-bold text-gray-900">{{ $product->name }}</h3>
                        <p class="whitespace-nowrap text-lg font-bold text-gray-900">
                            @if (!is_null($product->price))
                                Rp. {{ number_format($product->price, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </p>
                    </div>

                    <p class="mb-4 text-xs leading-relaxed text-gray-700">
                        {{ $product->description ?? '-' }}
                    </p>

                    <div class="flex items-end justify-between">
                        <div>
                            <p class="mb-0.5 text-xs font-semibold text-gray-900">Kategori:</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $product->category_name }}</p>
                        </div>
                        <p class="text-right text-xs text-gray-900">
                            Sisa Stok: <span class="font-bold">{{ $product->stock_opname }}</span>
                        </p>
                    </div>
                </button>
            @endforeach
        </div>
    </div>
</x-cashier-layout>