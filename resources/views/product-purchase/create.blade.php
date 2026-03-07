<x-app-layout>
    <div class="font-mont py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('purchase.store') }}" method="POST" id="productForm">
                @csrf

                <x-slot:dynamicRows>
                    {{-- PPN & Diskon Controls - Responsive --}}
                    <div class="mb-8 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
                        <div class="border-b border-gray-50 bg-gray-50/50 px-4 py-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500">Informasi Transaksi</h3>
                        </div>

                        <div class="p-4 sm:p-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-gray-600">Tanggal Pembelian</label>
                                    <div class="relative">
                                        <input type="date" name="purchase_date"
                                            class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 pl-3 pr-3 text-sm transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-200"
                                            value="{{ now()->toDateString() }}" required>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="ppnInput" class="text-xs font-bold text-gray-600">PPN</label>
                                    <div class="flex gap-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200">
                                        <input type="number" name="ppn" id="ppnInput"
                                            class="w-full border-none bg-transparent py-2.5 pl-3 text-sm focus:ring-0"
                                            placeholder="0" min="0" step="0.01">
                                        <button type="button" id="togglePpnType"
                                            class="flex items-center justify-center border-l border-gray-200 bg-white px-3 text-sm font-bold text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                                            title="Klik untuk ganti tipe PPN">
                                            <span id="ppnUnitLabel">%</span>
                                        </button>
                                    </div>
                                    <input type="hidden" name="ppn_type" id="ppnTypeInput" value="percent">
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="globalDiscount" class="text-xs font-bold text-gray-600">Diskon Global</label>
                                    <div class="flex gap-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200">
                                        <input type="number" name="discount" id="globalDiscount"
                                            class="w-full border-none bg-transparent py-2.5 pl-3 text-sm focus:ring-0"
                                            placeholder="0" min="0" step="0.01">
                                        <button type="button" id="toggleDiscountType"
                                            class="flex items-center justify-center border-l border-gray-200 bg-white px-3 text-sm font-bold text-button-hover cursor-pointer hover:bg-green-50 transition-colors"
                                            title="Klik untuk ganti tipe diskon">
                                            <span id="discountUnitLabel">%</span>
                                        </button>
                                    </div>
                                    <input type="hidden" name="discount_type" id="discountTypeInput" value="percent">
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="method" class="text-xs font-bold text-gray-600">Metode Bayar</label>
                                    <select name="method" id="method"
                                        class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 text-sm transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-200">
                                        <option value="0">Tunai / Cash</option>
                                        <option value="12">Kredit / Piutang</option>
                                    </select>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-bold text-gray-600">Status</label>
                                    <label for="isPaid" class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 transition-all hover:bg-gray-100">
                                        <span class="text-sm font-medium text-gray-700">Sudah Lunas?</span>
                                        <input type="checkbox" name="isPaid" id="isPaid"
                                            class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 shadow-sm transition-all">
                                    </label>
                                </div>

                            </div>
                        </div>
                    </div>

                    @php
                        $productOptions = $products->map(fn($p) => [
                            'id' => $p->id,
                            'label' => $p->code_id . ' - ' . $p->name,
                            'code' => $p->code_id,
                            'name' => $p->name
                        ]);
                    @endphp

                    <div id="rowsContainer">
                        {{-- Header Row - Hidden on Mobile --}}
                        <div class="mb-3 hidden lg:grid lg:grid-cols-12 gap-3 px-3 text-sm font-semibold text-gray-700">
                            <div class="col-span-3">Produk</div>
                            <div class="col-span-2">Harga Beli</div>
                            <div class="col-span-2">Jumlah</div>
                            <div class="col-span-2">Satuan</div>
                            <div class="col-span-2">Sub Total</div>
                            <div class="col-span-1"></div>
                        </div>

                        {{-- Dynamic Row Template --}}
                        <div class="product-row mb-3 rounded-lg border border-gray-200 p-3 sm:p-4">
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-start lg:gap-3">
                                {{-- Product Selector --}}
                                <div class="lg:col-span-3">
                                    <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Produk</label>
                                    <x-content.combobox 
                                        name="products[0][product_id]"
                                        :options="$productOptions"
                                        placeholder="Pilih atau cari produk..."
                                        class="product-select"
                                        required />
                                </div>

                                <div class="grid grid-cols-2 gap-3 lg:col-span-4 lg:contents">
                                    <div class="lg:col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Harga
                                            Beli</label>
                                        <input type="text" name="products[0][price]"
                                            class="price-input w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="12.000" required>
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label
                                            class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Jumlah</label>
                                        <input type="number" name="products[0][quantity]"
                                            class="quantity-input w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="10" required min="1">
                                    </div>
                                </div>

                                <div class="lg:col-span-2">
                                    <label
                                        class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Satuan</label>
                                    <input type="text" name="products[0][unit]"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="PCS" required>
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Sub
                                        Total</label>
                                    <input type="text" name="products[0][subtotal]"
                                        class="subtotal-input w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="0" readonly>
                                </div>

                                <div class="flex items-center justify-center lg:col-span-1">
                                    <button type="button"
                                        class="remove-row w-full lg:w-auto rounded-md bg-red-50 px-4 py-2 text-red-600 hover:bg-red-100 hover:text-red-800 disabled:opacity-50 lg:bg-transparent lg:p-0"
                                        disabled>
                                        <span class="lg:hidden">Hapus Baris</span>
                                        <svg class="hidden h-5 w-5 lg:inline" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Add Row Button & Total Summary --}}
                    <div class="mt-4 space-y-4 flex flex-col lg:flex-row lg:justify-between">
                        {{-- Button Tambah Baris --}}
                        <button type="button" id="addRow"
                            class="border-button-hover text-button-hover lg:h-12 inline-flex w-full items-center justify-center rounded-lg border bg-white px-4 py-3 text-sm font-semibold hover:bg-indigo-50 sm:w-auto sm:py-2">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            TAMBAH BARIS
                        </button>

                        {{-- Total Summary --}}
                        <div class="rounded-lg border border-gray-300 bg-white p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-6">
                                <div class="flex justify-between lg:block lg:text-right">
                                    <div class="text-xs text-gray-500">Total</div>
                                    <div id="totalDisplay" class="text-lg font-bold text-gray-900 lg:text-xl">Rp 0
                                    </div>
                                </div>
                                <div class="flex justify-between lg:block lg:text-right">
                                    <div class="text-xs text-gray-500">Diskon</div>
                                    <div id="discountDisplay" class="text-base font-semibold text-red-600 lg:text-lg">
                                        - Rp 0</div>
                                </div>
                                <div class="flex justify-between lg:block lg:text-right">
                                    <div class="text-xs text-gray-500">PPN</div>
                                    <div id="ppnDisplay" class="text-button-main text-base font-semibold lg:text-lg">+
                                        Rp 0</div>
                                </div>
                                <div class="border-t border-gray-300 pt-3 lg:border-t-0 lg:border-l lg:pl-6 lg:pt-0">
                                    <div class="flex justify-between lg:block lg:text-right">
                                        <div class="text-xs font-medium text-gray-500">Grand Total</div>
                                        <div id="grandTotalDisplay"
                                            class="text-button-hover text-xl font-bold lg:text-2xl">Rp 0</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Manual Price Edit Section --}}
                            <div id="manualPriceSection" class="mt-4 border-t border-gray-200 pt-4 hidden">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                    <div class="flex-1">
                                        <label for="manualGrandTotal"
                                            class="mb-1 block text-xs font-semibold text-gray-600">
                                            Edit Manual Grand Total:
                                        </label>
                                        <input type="text" id="manualGrandTotal"
                                            class="w-full rounded-md border border-button-hover bg-amber-50 px-3 py-2 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="Masukkan harga manual">
                                        <input type="hidden" name="manual_grand_total" id="manualGrandTotalValue">
                                    </div>
                                    <button type="button" id="resetManualPrice"
                                        class="rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-button-main/80 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap
                                        hover:text-white transition duration-150 ease-in-out"
                                        disabled>
                                        Reset ke Harga Sistem
                                    </button>
                                </div>

                                {{-- Info Harga Default Sistem --}}
                                <div id="systemPriceInfo"
                                    class="mt-3 rounded-md bg-blue-50 border border-blue-200 p-3 hidden">
                                    <div class="flex items-start gap-2">
                                        <svg class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-blue-800">Harga telah diedit manual
                                            </p>
                                            <p class="text-xs text-blue-700 mt-1">
                                                Harga default sistem: <span id="systemPriceValue"
                                                    class="font-bold"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot:dynamicRows>

                <x-slot:actions>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <x-button.remove-button href="/purchase" class="w-full sm:w-auto">
                            <span class="font-bold">BATAL</span>
                        </x-button.remove-button>

                        <x-button.add-button type="submit" class="w-full sm:w-auto">
                            <span class="font-bold">SIMPAN PEMBELIAN PRODUK</span>
                        </x-button.add-button>
                    </div>
                </x-slot:actions>
            </x-content.form-card>
        </div>
    </div>

    @push('scripts')
        @include('product-purchase._form-script')
    @endpush
</x-app-layout>
