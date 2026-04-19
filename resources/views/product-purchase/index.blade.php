<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont"
        x-data="{
            loading: false,
            editContent: '',
            loadEdit(url) {
                this.loading = true;
                this.editContent = '<div class=\'flex items-center justify-center p-10\'><div class=\'animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600\'></div><span class=\'ml-3 text-gray-500\'>Memuat data...</span></div>';
                $dispatch('open-modal', 'edit-purchase');
                
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    this.editContent = html;
                    this.loading = false;
                    
                    // Re-init form components
                    $nextTick(() => {
                        if (window.Alpine) {
                            window.Alpine.initTree(document.getElementById('edit-modal-body'));
                        }
                        
                        // Re-init purchase form logic
                        if (window.initPurchaseForm) {
                            // Update rowIndex based on loaded rows
                            const rows = document.querySelectorAll('#edit-modal-body .product-row');
                            if (window.setRowIndex) window.setRowIndex(rows.length);
                            window.initPurchaseForm();
                        }
                    });
                })
                .catch(err => {
                    this.editContent = '<div class=\'p-3 text-red-600\'>Gagal memuat data. Silakan coba lagi.</div>';
                    this.loading = false;
                });
            }
        }">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            <!-- Header dengan button -->
            <div class="mb-4 flex justify-start">
                <x-button.add-button @click="$dispatch('open-modal', 'create-purchase')" class="w-full sm:w-auto cursor-pointer">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add Icon" class="h-5 w-5">
                    </x-slot>
                    <span class="font-bold">TAMBAH PEMBELIAN PRODUK</span>
                </x-button.add-button>
            </div>

            <x-modal name="create-purchase" title="TAMBAH PEMBELIAN PRODUK" maxWidth="full" 
                x-init="if ($errors->any()) $dispatch('open-modal', 'create-purchase')">
                <div class="">
                    @include('product-purchase._form', ['action' => route('purchase.store'), 'method' => 'POST', 'products' => $products])
                </div>
            </x-modal>

            <x-modal name="edit-purchase" title="UBAH DATA PEMBELIAN" maxWidth="full">
                <div id="edit-modal-body" x-html="editContent">
                    {{-- Content loaded via AJAX --}}
                </div>
            </x-modal>

            <div class="mb-6 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <form method="GET" class="flex flex-wrap items-end gap-5">
                    
                    {{-- From Date --}}
                    <div class="flex min-w-40 flex-1 flex-col gap-1.5 sm:flex-none">
                        <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Dari Tanggal
                        </label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                    </div>

                    {{-- To Date --}}
                    <div class="flex min-w-[160px] flex-1 flex-col gap-1.5 sm:flex-none">
                        <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                            Sampai Tanggal
                        </label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                    </div>

                    {{-- Sort --}}
                    <div class="flex min-w-[200px] flex-1 flex-col gap-1.5 sm:flex-none">
                        <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                            </svg>
                            Urutkan Berdasarkan
                        </label>
                        <select name="sort"
                            class="w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-100">
                            <option value="">Default</option>
                            <option value="purchase_date_asc" {{ request('sort') == 'purchase_date_asc' ? 'selected' : '' }}>Tanggal Terlama</option>
                            <option value="purchase_date_desc" {{ request('sort') == 'purchase_date_desc' ? 'selected' : '' }}>Tanggal Terbaru</option>
                            <option value="method_asc" {{ request('sort') == 'method_asc' ? 'selected' : '' }}>Metode Pembayaran</option>
                            <option value="total_asc" {{ request('sort') == 'total_asc' ? 'selected' : '' }}>Total (⇧ Rendah-Tinggi)</option>
                            <option value="total_desc" {{ request('sort') == 'total_desc' ? 'selected' : '' }}>Total (⇩ Tinggi-Rendah)</option>
                            <option value="paid" {{ request('sort') == 'paid' ? 'selected' : '' }}>Status: Lunas</option>
                            <option value="unpaid" {{ request('sort') == 'unpaid' ? 'selected' : '' }}>Status: Belum Lunas</option>
                        </select>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-2 pt-1">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-button-main px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-button-hover cursor-pointer hover:shadow-md active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Cari
                        </button>

                        <a href="{{ route('purchase.index') }}"
                            class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 transition-all hover:bg-gray-50 hover:text-gray-800 active:scale-95"
                            title="Reset Filter">
                            Reset
                        </a>
                    </div>
                </form>
            </div>


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
                                    <a href="#"
                                        @click.prevent="loadEdit('{{ route('purchase.edit', $purchase->id) }}')"
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

    @push('scripts')
        @include('product-purchase._form-script')
    @endpush
</x-app-layout>
