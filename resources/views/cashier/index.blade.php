<x-cashier-layout :categories="$categories">

    <form action="{{ route('cashier') }}" method="GET" class="flex items-center justify-end gap-4 px-8 py-4">

        <input type="hidden" name="category" value="{{ request('category') }}">

        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" type="button"
                class="{{ request('sort') ? 'border-button-main bg-blue-50 text-button-main' : 'border-gray-300 text-gray-600' }} rounded-lg border-2 p-2.5 transition-colors hover:bg-gray-50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" style="display: none;"
                class="absolute right-0 z-50 mt-2 w-48 rounded-lg border border-gray-100 bg-white shadow-lg">
                <div class="p-1">
                    <button type="submit" name="sort" value="name_az"
                        class="group flex w-full items-center rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        Nama (A-Z)
                    </button>
                    <button type="submit" name="sort" value="name_za"
                        class="group flex w-full items-center rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        Nama (Z-A)
                    </button>
                    <div class="my-1 border-t border-gray-100"></div>
                    <button type="submit" name="sort" value="price_low"
                        class="group flex w-full items-center rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        Harga Terendah
                    </button>
                    <button type="submit" name="sort" value="price_high"
                        class="group flex w-full items-center rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        Harga Tertinggi
                    </button>
                    <div class="my-1 border-t border-gray-100"></div>
                    <button type="submit" name="sort" value="stock_high"
                        class="group flex w-full items-center rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        Stok Terbanyak
                    </button>
                    <button type="submit" name="sort" value="stock_low"
                        class="group flex w-full items-center rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-blue-500 hover:text-white">
                        Stok Sedikit
                    </button>
                </div>
            </div>
        </div>

        <div class="relative w-80">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Produk"
                class="focus:border-button-main w-full rounded-lg border-2 border-gray-300 py-2.5 pl-10 pr-4 transition-colors focus:outline-none">
        </div>
    </form>

    <div class="flex-1 overflow-y-auto px-8 py-6">
        @if ($products->isEmpty())
            <div class="flex h-64 flex-col items-center justify-center text-center text-gray-500">
                <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p class="text-lg font-medium">Produk tidak ditemukan</p>
                <p class="text-sm">Coba cari dengan kata kunci lain.</p>
            </div>
        @else
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
        @endif
    </div>
</x-cashier-layout>
