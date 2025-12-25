<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            <!-- Header dengan button -->
            <div class="mb-4 flex justify-start">
                <x-button.add-button href="purchase/create" class="w-full sm:w-auto">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add Icon" class="h-5 w-5">
                    </x-slot>
                    <span class="font-bold">TAMBAH PEMBELIAN PRODUK</span>
                </x-button.add-button>
            </div>

            <form method="GET" class="mb-4 flex flex-wrap items-end gap-4">
                {{-- From Date --}}
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-gray-600">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- To Date --}}
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-gray-600">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Sort --}}
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-gray-600">Urutkan</label>
                    <select name="sort"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Default</option>
                        <option value="purchase_date_asc"
                            {{ request('sort') == 'purchase_date_asc' ? 'selected' : '' }}>
                            Tanggal Terlama
                        </option>
                        <option value="purchase_date_desc"
                            {{ request('sort') == 'purchase_date_desc' ? 'selected' : '' }}>
                            Tanggal Terbaru
                        </option>
                        <option value="method_asc" {{ request('sort') == 'method_asc' ? 'selected' : '' }}>
                            Metode Pembayaran
                        </option>
                        <option value="total_asc" {{ request('sort') == 'total_asc' ? 'selected' : '' }}>
                            Total (⇧)
                        </option>
                        <option value="total_desc" {{ request('sort') == 'total_desc' ? 'selected' : '' }}>
                            Total (⇩)
                        </option>
                        <option value="paid" {{ request('sort') == 'paid' ? 'selected' : '' }}>
                            Lunas
                        </option>
                        <option value="unpaid" {{ request('sort') == 'unpaid' ? 'selected' : '' }}>
                            Belum Lunas
                        </option>
                    </select>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="rounded-md bg-button-main px-4 py-2 text-sm font-semibold text-white hover:bg-button-hover">
                    Cari
                </button>

                {{-- Reset --}}
                <a href="{{ route('purchase.index') }}"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                    Reset
                </a>
            </form>


            <x-content.data-table :search="false">
                <x-slot name="header">
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Tanggal Pembelian
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Jumlah Total Pembelian
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Metode Pembayaran
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Diskon
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        PPN
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Total Pembayaran
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Sudah Lunas
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Dibuat Pada
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Diubah Pada
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Action
                    </th>
                </x-slot>

                <x-slot name="body">
                    @forelse($purchases as $purchase)
                        <tr class="hover:bg-gray-50/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $purchase->purchase_date->translatedFormat('l, d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $purchase->total_items }}
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-2">
                                    {{ strtoupper($purchase->payment_method) }}
                                </span>
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-2">
                                    {{ rtrim(rtrim(number_format($purchase->discount_percent, 2), '0'), '.') }}%
                                </span>
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-2">
                                    {{ rtrim(rtrim(number_format($purchase->ppn_percent, 2), '0'), '.') }}%
                                </span>
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-2">
                                    Rp {{ number_format($purchase->grand_total, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @if ($purchase->is_paid)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                        Lunas
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                        Belum Lunas
                                    </span>
                                @endif
                            </td>

                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-2">
                                    {{ $purchase->created_at->translatedFormat('l, d M Y | H:i') }}
                                </span>
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-2">
                                    {{ $purchase->updated_at->translatedFormat('l, d M Y | H:i') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('purchase.edit', $purchase->id) }}"
                                        class="text-blue-600 hover:text-blue-800" title="Edit">
                                        <img src="{{ asset('update-button.svg') }}" alt="Edit"
                                            class="inline h-5 w-5">
                                    </a>

                                    <x-delete :module="'data pembelian pada waktu'" :name="$purchase->purchase_date->translatedFormat('l, d M Y') " :action="route('purchase.destroy', $purchase->id)" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                                Belum ada data produk.
                            </td>
                        </tr>
                    @endforelse
                </x-slot>

                <x-slot name="showing">
                    Showing
                    <span
                        class="font-medium">{{ $purchases->firstItem() ?? 0 }}-{{ $purchases->lastItem() ?? 0 }}</span>
                    data of
                    <span class="font-medium">{{ $purchases->total() }}</span> entries
                </x-slot>

                <x-slot name="pagination">
                    {{ $purchases->onEachSide(1)->links() }}
                </x-slot>
            </x-content.data-table>
        </div>
    </div>
</x-app-layout>
