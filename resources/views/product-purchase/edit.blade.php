<x-app-layout>
    <div class="py-6 sm:py-12 font-mont">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('purchase.update', $purchase->id) }}" method="POST" id="productForm">
                @csrf
                @method('PUT')

                <x-slot:dynamicRows>
                    {{-- HEADER CONTROLS --}}
                    <div class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-3 sm:p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end sm:gap-4">

                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2">
                                <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">
                                    Tanggal Pembelian:
                                </label>
                                <input type="date" name="purchase_date"
                                    value="{{ $purchase->purchase_date->toDateString() }}"
                                    class="w-full sm:w-auto rounded-md border border-gray-300 px-3 py-1 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            {{-- PPN Input Manual --}}
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2">
                                <label for="ppnInput" class="text-sm font-semibold text-gray-700">PPN:</label>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="number"
                                        name="ppn"
                                        id="ppnInput"
                                        value="{{ rtrim(rtrim($purchase->ppn_percent, '0'), '.') }}"
                                        min="0"
                                        step="0.1">
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Diskon Input Manual --}}
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2">
                                <label for="globalDiscount" class="text-sm font-semibold text-gray-700">Diskon:</label>
                                <div class="relative rounded-md shadow-sm">
                                    <input type="number" name="discount" id="globalDiscount"
                                        value="{{ rtrim(rtrim($purchase->discount_percent, '0'), '.') }}"
                                        class="block w-full sm:w-24 rounded-md border-gray-300 pl-3 pr-8 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="0" min="0" max="100" step="0.01">
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2">
                                <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">Metode Pembayaran:</label>
                                <select name="method" id="method"
                                    class="w-full sm:w-auto rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="0" {{ $purchase->payment_method === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="12" {{ $purchase->payment_method === 'credit' ? 'selected' : '' }}>Kredit</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <label for="isPaid" class="text-sm font-semibold text-gray-700">Lunas:</label>
                                <input type="checkbox" name="isPaid" id="isPaid"
                                    class="h-4 w-4 accent-button-main focus:ring-button-main"
                                    {{ $purchase->is_paid ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>

                    {{-- ITEMS --}}
                    <div id="rowsContainer">
                        <div class="mb-3 hidden lg:grid lg:grid-cols-12 gap-3 px-3 text-sm font-semibold text-gray-700">
                            <div class="col-span-1">Kode</div>
                            <div class="col-span-3">Item</div>
                            <div class="col-span-2">Harga</div>
                            <div class="col-span-2">Qty</div>
                            <div class="col-span-1">Satuan</div>
                            <div class="col-span-2">Sub Total</div>
                            <div class="col-span-1"></div>
                        </div>

                        @foreach ($purchase->details as $i => $detail)
                            <div class="product-row mb-3 rounded-lg border border-gray-200 p-3 sm:p-4">
                                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-start lg:gap-3">

                                    <div class="lg:col-span-1">
                                        <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Kode</label>
                                        <input type="text" name="products[{{ $i }}][code]"
                                            value="{{ $detail->product_code }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 uppercase shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                            required>
                                    </div>

                                    <div class="lg:col-span-3">
                                        <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Item</label>
                                        <input type="text" name="products[{{ $i }}][item]"
                                            value="{{ $detail->product_name }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                            required>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 lg:col-span-5 lg:contents">
                                        <div class="lg:col-span-2">
                                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Harga</label>
                                            <input type="text" name="products[{{ $i }}][price]"
                                                value="{{ number_format($detail->price, 0, ',', '.') }}"
                                                class="price-input w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                                required>
                                        </div>

                                        <div class="lg:col-span-2">
                                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Qty</label>
                                            <input type="number" name="products[{{ $i }}][quantity]"
                                                value="{{ $detail->quantity }}"
                                                class="quantity-input w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                                required min="1">
                                        </div>
                                    </div>

                                    <div class="lg:col-span-1">
                                        <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Satuan</label>
                                        <input type="text" name="products[{{ $i }}][unit]"
                                            value="{{ $detail->unit }}"
                                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500"
                                            required>
                                    </div>

                                    <div class="lg:col-span-2">
                                        <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Sub Total</label>
                                        <input type="text" name="products[{{ $i }}][subtotal]"
                                            value="{{ number_format($detail->subtotal, 0, ',', '.') }}"
                                            class="subtotal-input w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 shadow-lg"
                                            readonly>
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
                        @endforeach
                    </div>

                    {{-- ADD ROW + TOTAL --}}
                    <div class="mt-4 space-y-4 flex flex-col lg:flex-row lg:justify-between">
                        <button type="button" id="addRow"
                            class="border-button-hover text-button-hover lg:h-12 inline-flex w-full items-center justify-center rounded-lg border bg-white px-4 py-3 text-sm font-semibold hover:bg-indigo-50 sm:w-auto sm:py-2">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            TAMBAH BARIS
                        </button>

                        <div class="rounded-lg border border-gray-300 bg-white p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-6">
                                <div class="flex justify-between lg:block lg:text-right">
                                    <div class="text-xs text-gray-500">Total</div>
                                    <div id="totalDisplay" class="text-lg font-bold text-gray-900 lg:text-xl">Rp 0</div>
                                </div>
                                <div class="flex justify-between lg:block lg:text-right">
                                    <div class="text-xs text-gray-500">Diskon</div>
                                    <div id="discountDisplay" class="text-base font-semibold text-red-600 lg:text-lg">- Rp 0</div>
                                </div>
                                <div class="flex justify-between lg:block lg:text-right">
                                    <div class="text-xs text-gray-500">PPN</div>
                                    <div id="ppnDisplay" class="text-base font-semibold text-button-main lg:text-lg">+ Rp 0</div>
                                </div>
                                <div class="border-t border-gray-300 pt-3 lg:border-t-0 lg:border-l lg:pl-6 lg:pt-0">
                                    <div class="flex justify-between lg:block lg:text-right">
                                        <div class="text-xs font-medium text-gray-500">Grand Total</div>
                                        <div id="grandTotalDisplay" class="text-button-hover text-xl font-bold lg:text-2xl">Rp 0</div>
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
                                        class="rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-button-main/80 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap hover:text-white transition duration-150 ease-in-out"
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
                                            <p class="text-sm font-semibold text-blue-800">Harga telah diedit manual</p>
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
                        <x-button.remove-button href="{{ route('purchase.index') }}" class="w-full sm:w-auto">
                            <span class="font-bold">BATAL</span>
                        </x-button.remove-button>

                        <x-button.add-button type="submit" class="w-full sm:w-auto">
                            <span class="font-bold">SIMPAN PERUBAHAN</span>
                        </x-button.add-button>
                    </div>
                </x-slot:actions>
            </x-content.form-card>
        </div>
    </div>

    @push('scripts')
        @include('product-purchase._form-script', ['rowIndex' => $purchase->details->count()])
    @endpush
</x-app-layout>