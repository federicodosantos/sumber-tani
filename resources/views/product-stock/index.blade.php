<x-app-layout>

    <div class="hidden lg:flex justify-center font-mont">
        <div class="grid grid-cols-2 gap-6 max-w-4xl w-3/5">

            {{-- Total Stok --}}
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm" style="border: 1px solid #e5e7eb;">
                <div class="bg-button-main flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-white">
                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.25h16.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Total Stok</div>
                    <div class="text-xl font-semibold text-gray-900">
                        {{ number_format($totalStock) }}
                    </div>
                </div>
            </div>

            {{-- Produk Terbanyak --}}
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm" style="border: 1px solid #e5e7eb;">
                <div class="bg-button-main flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-white">
                    <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25L3 7.5m18 0v9l-9 5.25L3 16.5V7.5m9 14.25V12.75" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Produk Terbanyak</div>
                    <div class="text-xl font-semibold text-gray-900">
                        {{ $topProduct->name ?? 'Belum ada' }}
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="lg:hidden grid grid-cols-2 gap-4 w-full">

        {{-- Total Stok --}}
        <div class="rounded-lg flex flex-row items-center bg-white shadow-sm p-4 gap-2">
            <div class="bg-button-main flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-white">
                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.25h16.5" />
                </svg>
            </div>
            <div>
                <div class="text-xs text-gray-500">Total Stok</div>
                <div class="text-md font-semibold text-gray-900">
                    {{ number_format($totalStock) }}
                </div>
            </div>
        </div>

        {{-- Produk Terbanyak --}}
        <div class="rounded-lg flex flex-row items-center bg-white shadow-sm p-4 gap-2">
            <div class="bg-button-main flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-white">
                <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25L3 7.5m18 0v9l-9 5.25L3 16.5V7.5m9 14.25V12.75" />
                </svg>
            </div>
            <div>
                <div class="text-xs text-gray-500">Produk Terbanyak</div>
                <div class="text-md font-semibold text-gray-900">
                    {{ $topProduct->name ?? 'Belum ada' }}
                </div>
            </div>
        </div>

    </div>

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            'Product'
        </h2>
    </x-slot>

    <div class="py-12 font-mont">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-start gap-x-4">
                <x-button.add-button href="{{ route('stock.create') }}">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add" class="inline h-5 w-5">
                    </x-slot>
                    <span class="font-bold">TAMBAH STOK</span>
                </x-button.add-button>
            </div>

            <x-content.data-table>
                <x-slot name="sortOptions">
                    <option value="product_code_asc" {{ request('sort') == 'product_code_asc' ? 'selected' : '' }}>Kode
                        Produk (A → Z)</option>
                    <option value="product_code_desc" {{ request('sort') == 'product_code_desc' ? 'selected' : '' }}>
                        Kode Produk (Z → A)</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama (A → Z)</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama (Z → A)
                    </option>
                    <option value="stock_asc" {{ request('sort') == 'stock_asc' ? 'selected' : '' }}>Stok Tersedikit
                    </option>
                    <option value="stock_desc" {{ request('sort') == 'stock_desc' ? 'selected' : '' }}>Stok Terbanyak
                    </option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Harga Terendah
                    </option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Harga Tertinggi
                    </option>
                    <option value="expired_asc" {{ request('sort') == 'expired_asc' ? 'selected' : '' }}>Expired Terdekat
                    </option>
                </x-slot>
                <x-slot name="header">
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        KODE PRODUK
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        NAMA PRODUK
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        TOTAL STOK
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        HARGA KONSUMEN
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        HARGA R1
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        HARGA R2
                    </th>

                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        EXPIRY TERDEKAT
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Aksi
                    </th>

                </x-slot>

                <x-slot name="body">

                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-black">
                                {{ $product->code_id }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                {{ $product->name }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if (is_null($product->stock_total))
                                    <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-black">
                                        Jumlah Stok Belum Diatur
                                    </span>
                                @elseif ($product->stock_total == 0)
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                        0 (Habis)
                                    </span>
                                @else
                                    <span class="font-medium text-black">
                                        {{ number_format($product->stock_total) }}
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                @if (is_null($product->price_consument))
                                    <span class="text-black">-</span>
                                @else
                                    Rp {{ number_format($product->price_consument, 0, ',', '.') }}
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                @if (is_null($product->price_r1))
                                    <span class="text-black">-</span>
                                @else
                                    Rp {{ number_format($product->price_r1, 0, ',', '.') }}
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                @if (is_null($product->price_r2))
                                    <span class="text-black">-</span>
                                @else
                                    Rp {{ number_format($product->price_r2, 0, ',', '.') }}
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($product->expired_date)
                                    @php
                                        $daysLeft = \Carbon\Carbon::today()->diffInDays(
                                            \Carbon\Carbon::parse($product->expired_date),
                                            false,
                                        );

                                        if ($daysLeft <= 14) {
                                            $badge = 'bg-red-100 text-red-700';
                                        } elseif ($daysLeft <= 30) {
                                            $badge = 'bg-orange-100 text-orange-700';
                                        } elseif ($daysLeft <= 90) {
                                            $badge = 'bg-yellow-100 text-yellow-700';
                                        } else {
                                            $badge = 'bg-green-100 text-green-700';
                                        }
                                    @endphp

                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $badge }}">
                                            @if ($daysLeft < 0)
                                                Expired
                                            @else
                                                {{ $daysLeft }} hari lagi
                                            @endif
                                        </span>

                                        <span class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($product->expired_date)->locale('id')->translatedFormat('d M Y') }}
                                        </span>

                                        <span class="text-xs text-gray-500">
                                            Batch {{ $product->expiry_batch }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>



                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                @if (is_null($product->latest_stock_id))
                                    <a href="{{ route('stock.create', ['product_id' => $product->product_id]) }}"
                                        class="text-button-main hover:text-button-hover font-bold text-xs">
                                        Isi Stok Awal
                                    </a>
                                @else
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('stock.edit', $product->latest_stock_id) }}"
                                            class="text-button-main hover:text-button-hover" title="Edit">
                                            <img src="{{ asset('update-button.svg') }}" alt="Edit"
                                                class="inline h-5 w-5">
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-500">
                                Belum ada data produk.
                            </td>
                        </tr>
                    @endforelse

                </x-slot>
                <x-slot name="showing">
                    @if ($products->total() > 0)
                        Showing data <span class="font-medium">{{ $products->firstItem() }}</span>
                        to <span class="font-medium">{{ $products->lastItem() }}</span>
                        of <span class="font-medium">{{ $products->total() }}</span> entries
                    @else
                        <span class="text-gray-700">Tidak ada data untuk ditampilkan</span>
                    @endif
                </x-slot>
                <x-slot name="pagination">
                    {{ $products->links() }}
                </x-slot>
            </x-content.data-table>
        </div>
    </div>

</x-app-layout>
