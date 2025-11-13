<x-app-layout>

    <div class="mb-6 rounded-xl bg-white p-5 shadow-sm" style="border: 1px solid #e5e7eb;">

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:divide-x md:divide-gray-200">

            <div class="flex items-center justify-center gap-5 pt-6 md:pt-0">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
                    <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.25h16.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Total Stok</div>
                    <div class="text-2xl font-semibold text-gray-900"> {{ number_format($totalStock) }}
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center gap-5 pt-6 md:pt-0">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-cyan-600">
                    <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25L3 7.5m18 0v9l-9 5.25L3 16.5V7.5m9 14.25V12.75" />
                    </svg>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Produk Terbanyak</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $topProduct->name ?? 'Belum ada' }}</div>
                </div>
            </div>

        </div>
    </div>

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            'Product'
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-start gap-x-4">
                <x-button.add-button href="{{ route('stock.create') }}">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add" class="inline h-5 w-5">
                    </x-slot>
                    TAMBAH STOK
                </x-button.add-button>
            </div>

            <x-content.data-table>

                <x-slot name="header">
                    <x-sortable-th name="product_id">
                        KODE PRODUK
                    </x-sortable-th>

                    <x-sortable-th name="name">
                        NAMA PRODUK
                    </x-sortable-th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        DESKRIPSI PRODUK
                    </th>
                    <x-sortable-th name="stock_opname">
                        STOK
                    </x-sortable-th>

                    <x-sortable-th name="price">
                        HARGA PRODUK/UNIT
                    </x-sortable-th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Aksi
                    </th>

                </x-slot>

                <x-slot name="body">

                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50/50">

                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-black">
                                {{ $product->product_id }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                {{ $product->name }}
                            </td>

                            <td class="max-w-sm px-6 py-4 text-sm text-black">
                                <span class="line-clamp-2">
                                    {{ $product->description }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if (is_null($product->stock_opname))
                                    <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-black">
                                        Jumlah Stok Belum Diatur
                                    </span>
                                @elseif ($product->stock_opname == 0)
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                        0 (Habis)
                                    </span>
                                @else
                                    <span class="font-medium text-black">
                                        {{ number_format($product->stock_opname) }}
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                @if (is_null($product->price))
                                    <span class="text-black">-</span>
                                @else
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                @if (is_null($product->stock_opname))
                                    <a href="{{ route('stock.create', ['product_id' => $product->product_id]) }}"
                                        class="font-bold text-blue-600 hover:text-blue-800">
                                        Isi Stok Awal
                                    </a>
                                @else
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('stock.edit', $product->stock_id) }}"
                                            class="text-blue-600 hover:text-blue-800" title="Edit">
                                            <img src="{{ asset('update-button.svg') }}" alt="Edit"
                                                class="inline h-5 w-5">
                                        </a>

                                        <x-delete :module="'stok produk'" :name="$product->name" :action="route('stock.destroy', $product->stock_id)" />

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
