<x-app-layout>
    <div class="py-12 font-mont">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('purchase.update', $purchase->id) }}" method="POST" id="productForm">
                @csrf
                @method('PUT')

                <x-slot:dynamicRows>
                    {{-- HEADER CONTROLS --}}
                    <div
                        class="mb-6 flex flex-wrap items-center justify-end gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4">

                        {{-- TANGGAL --}}
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">Tanggal Pembelian:</label>
                            <input type="date" name="purchase_date"
                                value="{{ $purchase->purchase_date->toDateString() }}"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                        </div>

                        {{-- PPN --}}
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">PPN:</label>
                            <select name="ppn" id="ppnSelect"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="0" {{ $purchase->ppn_percent == 0 ? 'selected' : '' }}>Tanpa PPN
                                </option>
                                <option value="11" {{ $purchase->ppn_percent == 11 ? 'selected' : '' }}>PPN 11%
                                </option>
                                <option value="12" {{ $purchase->ppn_percent == 12 ? 'selected' : '' }}>PPN 12%
                                </option>
                            </select>
                        </div>

                        {{-- DISKON --}}
                        <div class="flex items-center gap-2">
                            <label for="globalDiscount" class="text-sm font-semibold text-gray-700">Diskon (%):</label>
                            <input type="number" name="discount" id="globalDiscount"
                                class="w-24 px-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="0" min="0" max="100" step="0.01">
                        </div>

                        {{-- METODE --}}
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">Metode:</label>
                            <select name="method" id="method"
                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="0" {{ $purchase->payment_method === 'cash' ? 'selected' : '' }}>Cash
                                </option>
                                <option value="12" {{ $purchase->payment_method === 'credit' ? 'selected' : '' }}>
                                    Kredit</option>
                            </select>
                        </div>

                        {{-- LUNAS --}}
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-semibold text-gray-700">Lunas:</label>
                            <input type="checkbox" name="isPaid" id="isPaid" class="h-4 w-4 accent-button-main"
                                {{ $purchase->is_paid ? 'checked' : '' }}>
                        </div>
                    </div>

                    {{-- DETAIL ITEMS --}}
                    <div id="rowsContainer">
                        {{-- HEADER --}}
                        <div class="mb-3 grid grid-cols-12 gap-3 px-3 text-sm font-semibold text-gray-700">
                            <div class="col-span-1">Kode</div>
                            <div class="col-span-3">Item</div>
                            <div class="col-span-2">Harga</div>
                            <div class="col-span-2">Qty</div>
                            <div class="col-span-1">Satuan</div>
                            <div class="col-span-2">Sub Total</div>
                            <div class="col-span-1"></div>
                        </div>

                        {{-- ROWS --}}
                        @foreach ($purchase->details as $i => $detail)
                            <div class="product-row mb-3 rounded-lg border border-gray-200 p-3">
                                <div class="grid grid-cols-12 items-start gap-3">
                                    <input type="text" name="products[{{ $i }}][code]"
                                        value="{{ $detail->product_code }}"
                                        class="col-span-1 rounded-md border-gray-300 border shadow-lg px-3 py-1  focus:border-indigo-500 focus:ring-indigo-500 uppercase" required>

                                    <input type="text" name="products[{{ $i }}][item]"
                                        value="{{ $detail->product_name }}"
                                        class="col-span-3 rounded-md border-gray-300 border shadow-lg px-3 py-1  focus:border-indigo-500 focus:ring-indigo-500" required>

                                    <input type="text" name="products[{{ $i }}][price]"
                                        value="{{ number_format($detail->price, 0, ',', '.') }}"
                                        class="price-input col-span-2 rounded-md border-gray-300 border shadow-lg px-3 py-1  focus:border-indigo-500 focus:ring-indigo-500" required>

                                    <input type="number" name="products[{{ $i }}][quantity]"
                                        value="{{ $detail->quantity }}"
                                        class="quantity-input col-span-2 rounded-md border-gray-300 border shadow-lg px-3 py-1  focus:border-indigo-500 focus:ring-indigo-500" required>

                                    <input type="text" name="products[{{ $i }}][unit]"
                                        value="{{ $detail->unit }}"
                                        class="col-span-1 rounded-md border-gray-300 border shadow-lg px-3 py-1  focus:border-indigo-500 focus:ring-indigo-500" required>

                                    <input type="text" readonly
                                        value="{{ number_format($detail->subtotal, 0, ',', '.') }}"
                                        class="subtotal-input col-span-2 rounded-md border-gray-300 bg-gray-50 border shadow-lg px-3 py-1  focus:border-indigo-500 focus:ring-indigo-500" required>

                                    <div class="col-span-1 flex items-center justify-center">
                                        <button type="button"
                                            class="remove-row text-red-600 hover:text-red-800 disabled:opacity-50"
                                            disabled>
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ADD ROW + TOTAL --}}
                    <div class="mt-4 flex justify-between items-center">
                        <button type="button" id="addRow"
                            class="inline-flex items-center rounded-lg border border-button-hover bg-white px-4 py-2 text-sm font-semibold text-button-hover hover:bg-indigo-50">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            TAMBAH BARIS
                        </button>

                        <div class="flex items-center gap-6 rounded-lg border border-gray-300 bg-white px-6 py-3">
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Total</div>
                                <div id="totalDisplay" class="text-xl font-bold text-gray-900">Rp 0</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Diskon</div>
                                <div id="discountDisplay" class="text-lg font-semibold text-red-600">- Rp 0</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">PPN</div>
                                <div id="ppnDisplay" class="text-lg font-semibold text-button-main">+ Rp 0</div>
                            </div>
                            <div class="h-10 w-px bg-gray-300"></div>
                            <div class="text-right">
                                <div class="text-xs font-medium text-gray-500">Grand Total</div>
                                <div id="grandTotalDisplay" class="text-2xl font-bold text-button-hover">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </x-slot:dynamicRows>

                <x-slot:actions>
                    <x-button.remove-button href="{{ route('purchase.index') }}">
                        BATAL
                    </x-button.remove-button>

                    <x-button.add-button type="submit">
                        SIMPAN PERUBAHAN
                    </x-button.add-button>
                </x-slot:actions>
            </x-content.form-card>
        </div>
    </div>

    @push('scripts')
        @include('product-purchase._form-script', [
            'rowIndex' => $purchase->details->count(),
        ])
    @endpush

</x-app-layout>
