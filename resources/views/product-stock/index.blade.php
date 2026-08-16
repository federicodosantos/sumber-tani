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
            <!-- <div class="mb-4 flex justify-start gap-x-4">
                <x-button.add-button href="{{ route('stock.create') }}">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add" class="inline h-5 w-5">
                    </x-slot>
                    <span class="font-bold">TAMBAH STOK</span>
                </x-button.add-button>
            </div> -->

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
                                    @php
                                        $st = (float) $product->stock_total;
                                        $stFormatted = $st == floor($st)
                                            ? number_format($st, 0, ',', '.')
                                            : rtrim(rtrim(number_format($st, 3, ',', '.'), '0'), ',');
                                    @endphp
                                    <span class="font-medium text-black">
                                        {{ $stFormatted }}
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                @if (is_null($product->price_consument))
                                    <span class="text-black">-</span>
                                @else
                                    @php
                                        $pc = (float) $product->price_consument;
                                        $pcFormatted = $pc == floor($pc)
                                            ? number_format($pc, 0, ',', '.')
                                            : rtrim(rtrim(number_format($pc, 3, ',', '.'), '0'), ',');
                                    @endphp
                                    Rp {{ $pcFormatted }}
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                @if (is_null($product->price_r1))
                                    <span class="text-black">-</span>
                                @else
                                    @php
                                        $pr1 = (float) $product->price_r1;
                                        $pr1Formatted = $pr1 == floor($pr1)
                                            ? number_format($pr1, 0, ',', '.')
                                            : rtrim(rtrim(number_format($pr1, 3, ',', '.'), '0'), ',');
                                    @endphp
                                    Rp {{ $pr1Formatted }}
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-black">
                                @if (is_null($product->price_r2))
                                    <span class="text-black">-</span>
                                @else
                                    @php
                                        $pr2 = (float) $product->price_r2;
                                        $pr2Formatted = $pr2 == floor($pr2)
                                            ? number_format($pr2, 0, ',', '.')
                                            : rtrim(rtrim(number_format($pr2, 3, ',', '.'), '0'), ',');
                                    @endphp
                                    Rp {{ $pr2Formatted }}
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



                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium" x-data="{
                                updateRupiah(name, value) {
                                    this.$dispatch('update-rupiah-value', { name: name, value: value });
                                }
                            }">
                                @if (is_null($product->latest_stock_id))
                                    {{-- TRIGGER ISI STOK AWAL --}}
                                    <button @click="$dispatch('open-modal', 'create-stock-{{ $product->product_id }}')"
                                        class="text-button-main hover:text-button-hover font-bold text-xs cursor-pointer">
                                        Isi Stok Awal
                                    </button>

                                    {{-- MODAL ISI STOK AWAL --}}
                                    <x-modal name="create-stock-{{ $product->product_id }}" title="ISI STOK AWAL: {{ $product->name }}" maxWidth="4xl">
                                        <div class="p-1">
                                            <form action="{{ route('stock.store') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->product_id }}">
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6 rounded-lg border border-gray-200 p-5">
                                                    {{-- Kode Produk --}}
                                                    <x-content.form-input label="Kode Produk" name="product_code_display"
                                                        value="{{ $product->code_id }}" 
                                                        class="cursor-not-allowed border-gray-300 bg-gray-100"
                                                        disabled readonly />

                                                    {{-- Nama Produk (Display only) --}}
                                                    <x-content.form-input label="Nama Produk" name="product_name_display"
                                                        value="{{ $product->name }}" 
                                                        class="cursor-not-allowed border-gray-300 bg-gray-100"
                                                        disabled readonly />

                                                    {{-- Row 2: Harga HPP (Unit Price) --}}
                                                    <x-input-rupiah label="Harga HPP (Unit Price)" name="unit_price"
                                                        placeholder="0" containerClass="" decimals="3" />

                                                    {{-- Row 2: Jumlah Stok --}}
                                                    <x-input-rupiah label="Jumlah Stok" name="stock_opname"
                                                        placeholder="0" containerClass="" required decimals="3" />

                                                    {{-- Row 3: Harga Konsumen --}}
                                                    <x-input-rupiah label="Harga Produk per Satuan (Konsumen)" name="price_consument"
                                                        placeholder="0" containerClass="" required decimals="3" />

                                                    {{-- Row 3: Harga R1 --}}
                                                    <x-input-rupiah label="Harga Produk per Satuan (R1)" name="price_r1"
                                                        placeholder="0" containerClass="" required decimals="3" />

                                                    {{-- Row 4: Harga R2 --}}
                                                    <x-input-rupiah label="Harga Produk per Satuan (R2)" name="price_r2"
                                                        placeholder="0" containerClass="" required decimals="3" />

                                                    {{-- Row 4: Tanggal Kadaluarsa --}}
                                                    <div>
                                                        <label class="mb-2 block text-sm font-semibold text-gray-900">Tanggal Kadaluarsa</label>
                                                        <input type="date" name="expired_date" min="{{ date('Y-m-d') }}"
                                                            class="focus:border-button-main focus:ring-button-main w-full rounded-lg border-2 border-black px-2 py-2 text-sm" />
                                                    </div>
                                                </div>

                                                <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                                                    <x-button.remove-button type="button" @click="$dispatch('close-modal', 'create-stock-{{ $product->product_id }}')">
                                                        BATAL
                                                    </x-button.remove-button>
                                                    <x-button.add-button type="submit">
                                                        SIMPAN STOK
                                                    </x-button.add-button>
                                                </div>
                                            </form>
                                        </div>
                                    </x-modal>
                                @else
                                    <div class="flex items-center gap-3">
                                        {{-- TRIGGER EDIT STOK --}}
                                        <button @click="$dispatch('open-modal', 'edit-stock-{{ $product->product_id }}')"
                                            class="text-button-main hover:text-button-hover cursor-pointer" title="Edit">
                                            <img src="{{ asset('update-button.svg') }}" alt="Edit"
                                                class="inline h-5 w-5">
                                        </button>

                                        {{-- MODAL EDIT STOK --}}
                                        @php
                                            $latestStock = $product->stock->firstWhere('id', $product->latest_stock_id);
                                            $batchOptions = $product->stock->sortByDesc('batch')->map(fn($s) => [
                                                'id' => $s->id,
                                                'label' => 'BATCH ' . $s->batch . ' (Stok: ' . rtrim(rtrim(number_format((float)$s->stock_opname, 3, ',', '.'), '0'), ',') . ')',
                                                'data' => [
                                                    'id' => $s->id,
                                                    'stock_opname' => $s->stock_opname,
                                                    'price_consument' => (int)$s->price_consument,
                                                    'price_r1' => (int)$s->price_r1,
                                                    'price_r2' => (int)$s->price_r2,
                                                    'unit_price' => (int)$s->unit_price,
                                                    'expired_date' => $s->expired_date ? \Carbon\Carbon::parse($s->expired_date)->format('Y-m-d') : '',
                                                ]
                                            ])->values()->all();
                                            
                                            // Add "New Batch" option
                                            $batchOptions[] = [
                                                'id' => 'new',
                                                'label' => '+ Tambah Batch Baru',
                                                'data' => [
                                                    'id' => 'new',
                                                    'stock_opname' => 0,
                                                    'price_consument' => 0,
                                                    'price_r1' => 0,
                                                    'price_r2' => 0,
                                                    'unit_price' => (int)($latestStock->unit_price ?? 0),
                                                    'expired_date' => '',
                                                ]
                                            ];
                                        @endphp

                                        <x-modal name="edit-stock-{{ $product->product_id }}" title="SESUAIKAN STOK: {{ $product->name }}" maxWidth="4xl">
                                            <div class="p-1" x-data="{
                                                isNewBatch: false,
                                                currentStockId: '{{ $product->latest_stock_id }}',
                                                currentData: {{ json_encode($latestStock ? [
                                                    'stock_opname' => $latestStock->stock_opname,
                                                    'price_consument' => (int)$latestStock->price_consument,
                                                    'price_r1' => (int)$latestStock->price_r1,
                                                    'price_r2' => (int)$latestStock->price_r2,
                                                    'unit_price' => (int)$latestStock->unit_price,
                                                    'expired_date' => $latestStock->expired_date ? \Carbon\Carbon::parse($latestStock->expired_date)->format('Y-m-d') : '',
                                                ] : []) }},
                                                
                                                handleBatchChange(detail) {
                                                    if (detail.value === 'new') {
                                                        this.isNewBatch = true;
                                                    } else {
                                                        this.isNewBatch = false;
                                                        this.currentStockId = detail.value;
                                                    }
                                                    
                                                    this.currentData = detail.selected.data;
                                                    
                                                    // Sync display components
                                                    this.$dispatch('update-rupiah-value', { name: 'unit_price', value: this.currentData.unit_price });
                                                    this.$dispatch('update-rupiah-value', { name: 'price_consument', value: this.currentData.price_consument });
                                                    this.$dispatch('update-rupiah-value', { name: 'price_r1', value: this.currentData.price_r1 });
                                                    this.$dispatch('update-rupiah-value', { name: 'price_r2', value: this.currentData.price_r2 });
                                                    this.$dispatch('update-rupiah-value', { name: 'stock_opname', value: this.currentData.stock_opname });
                                                }
                                            }" @combobox-change="if($event.detail.value) handleBatchChange($event.detail)">
                                                
                                                <form :action="'{{ url('stock') }}/' + currentStockId" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    
                                                    <input type="hidden" name="is_new_batch" :value="isNewBatch ? '1' : '0'">
                                                    <input type="hidden" name="batch_id" :value="currentStockId">
                                                    
                                                    <div class="mb-6 flex items-center justify-between bg-gray-50 p-4 rounded-xl border border-gray-200">
                                                        <div class="text-sm font-bold text-gray-700">PILIH BATCH</div>
                                                        <div class="w-64">
                                                            <x-content.combobox 
                                                                name="batch_selector" 
                                                                value="{{ $product->latest_stock_id }}"
                                                                :options="$batchOptions"
                                                                placeholder="Pilih Batch..."
                                                            />
                                                        </div>
                                                    </div>

                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6 rounded-lg border border-gray-200 p-5">
                                                        {{-- Kode Produk --}}
                                                        <x-content.form-input label="Kode Produk" name="product_code_display"
                                                            value="{{ $product->code_id }}" 
                                                            class="cursor-not-allowed border-gray-300 bg-gray-100"
                                                            disabled readonly />

                                                        {{-- Nama Produk --}}
                                                        <x-content.form-input label="Nama Produk" name="product_name_display"
                                                            value="{{ $product->name }}" 
                                                            class="cursor-not-allowed border-gray-300 bg-gray-100"
                                                            disabled readonly />

                                                        {{-- Harga HPP --}}
                                                        <x-input-rupiah label="Harga HPP (Unit Price)" name="unit_price"
                                                            :value="$latestStock->unit_price ?? 0"
                                                            placeholder="0" containerClass="" decimals="3" />

                                                        {{-- Jumlah Stok --}}
                                                        <x-input-rupiah label="Jumlah Stok" name="stock_opname"
                                                            :value="$latestStock->stock_opname ?? 0"
                                                            placeholder="0" containerClass="" required decimals="3"
                                                            @rupiah-change="currentData.stock_opname = $event.detail.value" />

                                                        {{-- Harga Konsumen --}}
                                                        <x-input-rupiah label="Harga Produk per Satuan (Konsumen)" name="price_consument"
                                                            :value="$latestStock->price_consument ?? 0"
                                                            placeholder="0" containerClass="" required decimals="3" />

                                                        {{-- Harga R1 --}}
                                                        <x-input-rupiah label="Harga Produk per Satuan (R1)" name="price_r1"
                                                            :value="$latestStock->price_r1 ?? 0"
                                                            placeholder="0" containerClass="" required decimals="3" />

                                                        {{-- Harga R2 --}}
                                                        <x-input-rupiah label="Harga Produk per Satuan (R2)" name="price_r2"
                                                            :value="$latestStock->price_r2 ?? 0"
                                                            placeholder="0" containerClass="" required decimals="3" />

                                                        {{-- Tanggal Kadaluarsa --}}
                                                        <div>
                                                            <label class="mb-2 block text-sm font-semibold text-gray-900">Tanggal Kadaluarsa</label>
                                                            <input type="date" name="expired_date" min="{{ date('Y-m-d') }}"
                                                                x-model="currentData.expired_date"
                                                                class="focus:border-button-main focus:ring-button-main w-full rounded-lg border-2 border-black px-2 py-2 text-sm" />
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                                                        <x-button.remove-button type="button" @click="$dispatch('close-modal', 'edit-stock-{{ $product->product_id }}')">
                                                            BATAL
                                                        </x-button.remove-button>
                                                        <x-button.add-button type="submit">
                                                            SIMPAN PERUBAHAN
                                                        </x-button.add-button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-modal>
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
