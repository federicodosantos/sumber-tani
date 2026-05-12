@props([
    'action',
    'method' => 'POST',
    'purchase' => null,
    'products' => [],
    'isEdit' => false,
])

@php
    $productOptions = $products->map(fn($p) => [
        'id' => $p->id,
        'label' => $p->code_id . ' - ' . $p->name,
        'code' => $p->code_id,
        'name' => $p->name
    ]);
@endphp

<x-content.form-card action="{{ $action }}" method="{{ $method }}" id="productForm">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

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
                                value="{{ old('purchase_date', $purchase?->purchase_date?->toDateString() ?? now()->toDateString()) }}" required>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5" x-data="{ 
                        type: '{{ old('ppn_type', $purchase?->ppn_type ?? 'percent') }}', 
                        toggle() { 
                            this.type = this.type === 'percent' ? 'nominal' : 'percent';
                            // Clear inputs on toggle to avoid confusion (but keep the price sync if desired)
                            // for now following original behavior: clear
                            $nextTick(() => {
                                const input = document.getElementById(this.type === 'percent' ? 'ppnInput' : 'ppnInputNominal_display');
                                if (input) { input.value = ''; input.dispatchEvent(new Event('input')); }
                            });
                        } 
                    }">
                        <label for="ppnInput" class="text-xs font-bold text-gray-600">PPN</label>
                        <div class="flex gap-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200">
                            {{-- Percent Mode --}}
                            {{-- Percent Mode --}}
                            <div x-show="type === 'percent'" class="w-full" x-data="{ 
                                val: '{{ (old('ppn_type', $purchase?->ppn_type) == 'percent' || !old('ppn_type')) ? str_replace('.', ',', old('ppn', $purchase?->ppn_percent !== null ? round($purchase->ppn_percent, 3) : '')) : '' }}' 
                            }">
                                <input type="text" 
                                    x-model="val"
                                    id="ppnInput"
                                    class="w-full border-none bg-transparent py-2.5 pl-3 text-sm focus:ring-0"
                                    placeholder="0"
                                    inputmode="decimal"
                                    @input="val = $el.value.replace(/[^0-9,]/g, ''); let parts = val.split(','); if(parts.length > 2) val = parts[0] + ',' + parts.slice(1).join(''); if(parts[1] && parts[1].length > 3) val = parts[0] + ',' + parts[1].slice(0, 3);"
                                    x-bind:disabled="type !== 'percent'">
                                <input type="hidden" name="ppn" :value="val.replace(',', '.')" x-bind:disabled="type !== 'percent'">
                            </div>

                            {{-- Nominal Mode --}}
                            <div x-show="type === 'nominal'" class="w-full">
                                <x-input-rupiah 
                                    name="ppn" 
                                    id="ppnInputNominal"
                                    class="w-full"
                                    placeholder="0"
                                    :value="old('ppn_type', $purchase?->ppn_type) == 'nominal' ? old('ppn', $purchase?->ppn_value ?? '') : ''" 
                                    x-bind:disabled="type !== 'nominal'" />
                            </div>

                            <button type="button" @click="toggle()"
                                class="flex items-center justify-center border-l border-gray-200 bg-white px-3 text-sm font-bold text-indigo-600 hover:bg-indigo-50 transition-colors cursor-pointer"
                                title="Klik untuk ganti tipe PPN">
                                <span x-text="type === 'nominal' ? 'Rp' : '%'"></span>
                            </button>
                        </div>
                        <input type="hidden" name="ppn_type" id="ppnTypeInput" :value="type">
                    </div>

                    <div class="flex flex-col gap-1.5" x-data="{ 
                        type: '{{ old('discount_type', $purchase?->discount_type ?? 'percent') }}', 
                        toggle() { 
                            this.type = this.type === 'percent' ? 'nominal' : 'percent';
                            $nextTick(() => {
                                const input = document.getElementById(this.type === 'percent' ? 'globalDiscount' : 'globalDiscountNominal_display');
                                if (input) { input.value = ''; input.dispatchEvent(new Event('input')); }
                            });
                        } 
                    }">
                        <label for="globalDiscount" class="text-xs font-bold text-gray-600">Diskon Global</label>
                        <div class="flex gap-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-200">
                            {{-- Percent Mode --}}
                            {{-- Percent Mode --}}
                            <div x-show="type === 'percent'" class="w-full" x-data="{ 
                                val: '{{ (old('discount_type', $purchase?->discount_type) == 'percent' || !old('discount_type')) ? str_replace('.', ',', old('discount', $purchase?->discount_percent !== null ? round($purchase->discount_percent, 3) : '')) : '' }}' 
                            }">
                                <input type="text" 
                                    x-model="val"
                                    id="globalDiscount"
                                    class="w-full border-none bg-transparent py-2.5 pl-3 text-sm focus:ring-0"
                                    placeholder="0"
                                    inputmode="decimal"
                                    @input="val = $el.value.replace(/[^0-9,]/g, ''); let parts = val.split(','); if(parts.length > 2) val = parts[0] + ',' + parts.slice(1).join(''); if(parts[1] && parts[1].length > 3) val = parts[0] + ',' + parts[1].slice(0, 3);"
                                    x-bind:disabled="type !== 'percent'">
                                <input type="hidden" name="discount" :value="val.replace(',', '.')" x-bind:disabled="type !== 'percent'">
                            </div>

                            {{-- Nominal Mode --}}
                            <div x-show="type === 'nominal'" class="w-full">
                                <x-input-rupiah 
                                    name="discount" 
                                    id="globalDiscountNominal"
                                    class="w-full"
                                    placeholder="0"
                                    :value="old('discount_type', $purchase?->discount_type) == 'nominal' ? old('discount', $purchase?->discount_value ?? '') : ''" 
                                    x-bind:disabled="type !== 'nominal'" />
                            </div>

                            <button type="button" @click="toggle()"
                                class="flex items-center justify-center border-l border-gray-200 bg-white px-3 text-sm font-bold text-button-hover cursor-pointer hover:bg-green-50 transition-colors"
                                title="Klik untuk ganti tipe diskon">
                                <span x-text="type === 'nominal' ? 'Rp' : '%'"></span>
                            </button>
                        </div>
                        <input type="hidden" name="discount_type" id="discountTypeInput" :value="type">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="method" class="text-xs font-bold text-gray-600">Metode Bayar</label>
                        <select name="method" id="method"
                            class="w-full rounded-lg border-gray-200 bg-gray-50 py-2.5 text-sm transition-all focus:border-indigo-500 focus:bg-white focus:ring-2 focus:ring-indigo-200">
                            <option value="0" {{ old('method', ($purchase?->payment_method == 'cash' ? 0 : 12)) == 0 ? 'selected' : '' }}>Tunai / Cash</option>
                            <option value="12" {{ old('method', ($purchase?->payment_method == 'credit' ? 12 : 0)) == 12 ? 'selected' : '' }}>Kredit / Piutang</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-gray-600">Status</label>
                        <label for="isPaid" class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 transition-all hover:bg-gray-100">
                            <span class="text-sm font-medium text-gray-700">Sudah Lunas?</span>
                            <input type="checkbox" name="isPaid" id="isPaid"
                                {{ old('isPaid', $purchase?->is_paid) ? 'checked' : '' }}
                                class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 shadow-sm transition-all">
                        </label>
                    </div>

                </div>
            </div>
        </div>

        <div id="rowsContainer">
            {{-- Header Row - Hidden on Mobile --}}
            <div class="mb-3 hidden lg:grid lg:grid-cols-[2fr_0.7fr_1fr_1.5fr_1.5fr_1.5fr_1.5fr_1.5fr_30px] gap-3 px-4 border border-transparent text-sm font-semibold text-gray-700">
                <div>Produk</div>
                <div>Jumlah</div>
                <div>Satuan</div>
                <div>HET Price</div>
                <div>Basic Disc</div>
                <div>Add Disc</div>
                <div>Net Price</div>
                <div>Sub Total</div>
                <div></div>
            </div>

            @php $rowCount = $isEdit ? $purchase->details->count() : 1; @endphp
            @for($i = 0; $i < $rowCount; $i++)
                @php $detail = $isEdit ? $purchase->details[$i] : null; @endphp
                <div class="product-row mb-3 rounded-lg border border-gray-200 p-3 sm:p-4">
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-[2fr_0.7fr_1fr_1.5fr_1.5fr_1.5fr_1.5fr_1.5fr_30px] lg:items-start lg:gap-3">
                        {{-- Product Selector --}}
                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Produk</label>
                            <x-content.combobox 
                                name="products[{{ $i }}][product_id]"
                                :options="$productOptions"
                                :value="old('products.' . $i . '.product_id', $detail?->product_id)"
                                placeholder="Pilih atau cari produk..."
                                class="product-select"
                                empty-action="create-product"
                                empty-label="+ Tambah Produk Baru"
                                type="product"
                                required />
                        </div>

                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Jumlah</label>
                            <input type="number" name="products[{{ $i }}][quantity]"
                                value="{{ old('products.' . $i . '.quantity', $detail?->quantity ?? 1) }}"
                                class="quantity-input w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                placeholder="10" required min="1">
                        </div>

                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Satuan</label>
                            <input type="text" name="products[{{ $i }}][unit]"
                                value="{{ old('products.' . $i . '.unit', $detail?->unit ?? 'PCS') }}"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-lg focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                placeholder="PCS" required>
                        </div>

                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">HET Price</label>
                            <x-input-rupiah 
                                name="products[{{ $i }}][het_price]"
                                id="products_{{ $i }}_het_price"
                                class="w-full"
                                placeholder="0"
                                :value="old('products.' . $i . '.het_price', $detail?->het_price ?? '')"
                                required />
                        </div>

                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Basic Disc</label>
                            <x-input-rupiah 
                                name="products[{{ $i }}][basic_discount]"
                                id="products_{{ $i }}_basic_discount"
                                class="w-full"
                                placeholder="0"
                                :value="old('products.' . $i . '.basic_discount', $detail?->basic_discount ?? '')" />
                        </div>

                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Add Disc</label>
                            <x-input-rupiah 
                                name="products[{{ $i }}][additional_discount]"
                                id="products_{{ $i }}_additional_discount"
                                class="w-full"
                                placeholder="0"
                                :value="old('products.' . $i . '.additional_discount', $detail?->additional_discount ?? '')" />
                        </div>

                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Net Price</label>
                            <x-input-rupiah 
                                name="products[{{ $i }}][net_price]"
                                id="products_{{ $i }}_net_price"
                                class="w-full"
                                placeholder="0"
                                :value="old('products.' . $i . '.net_price', $detail?->net_price ?? '')"
                                readonly />
                        </div>

                        <div class="min-w-0">
                            <label class="mb-1 block text-xs font-semibold text-gray-600 lg:hidden">Sub Total</label>
                            <x-input-rupiah 
                                name="products[{{ $i }}][subtotal]"
                                id="products_{{ $i }}_subtotal"
                                class="w-full"
                                placeholder="0"
                                :value="$detail?->subtotal ?? ''"
                                readonly />
                        </div>

                        <div class="flex h-[42px] items-center justify-end -mr-1">
                            <button type="button"
                                class="remove-row w-full lg:w-auto rounded-md bg-red-50 px-4 py-2 text-red-600 hover:bg-red-100 hover:text-red-800 disabled:opacity-50 lg:bg-transparent lg:p-0"
                                {{ $rowCount <= 1 ? 'disabled' : '' }}>
                                <span class="lg:hidden">Hapus Baris</span>
                                <svg class="hidden h-5 w-5 lg:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        {{-- Add Row Button & Total Summary --}}
        <div class="mt-4 space-y-4 flex flex-col lg:flex-row lg:justify-between">
            {{-- Button Tambah Baris --}}
            <button type="button" id="addRow"
                class="border-button-hover text-button-hover lg:h-12 inline-flex w-full items-center justify-center rounded-lg border bg-white px-4 py-3 text-sm font-semibold hover:bg-indigo-50 sm:w-auto sm:py-2">
                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                TAMBAH BARIS
            </button>

            {{-- Total Summary --}}
            <div class="rounded-lg border border-gray-300 bg-white p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-6">
                    <div class="flex justify-between lg:block lg:text-right">
                        <div class="text-xs text-gray-500">Total</div>
                        <div id="totalDisplay" class="text-lg font-bold text-gray-900 lg:text-xl">
                            Rp {{ number_format($purchase?->subtotal ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="flex justify-between lg:block lg:text-right">
                        <div class="text-xs text-gray-500">Diskon</div>
                        <div id="discountDisplay" class="text-base font-semibold text-red-600 lg:text-lg">
                            - Rp {{ number_format($purchase?->discount_value ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="flex justify-between lg:block lg:text-right">
                        <div class="text-xs text-gray-500">PPN</div>
                        <div id="ppnDisplay" class="text-button-main text-base font-semibold lg:text-lg">+
                            Rp {{ number_format($purchase?->ppn_value ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="border-t border-gray-300 pt-3 lg:border-t-0 lg:border-l lg:pl-6 lg:pt-0">
                        <div class="flex justify-between lg:block lg:text-right">
                            <div class="text-xs font-medium text-gray-500">Grand Total</div>
                            <div id="grandTotalDisplay" class="text-button-hover text-xl font-bold lg:text-2xl">
                                Rp {{ number_format($purchase?->grand_total ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Manual Price Edit Section --}}
                <div id="manualPriceSection" class="mt-4 border-t border-gray-200 pt-4 {{ $purchase?->manual_grand_total ? '' : 'hidden' }}">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex-1">
                            <label for="manualGrandTotal" class="mb-1 block text-xs font-semibold text-gray-600">
                                Edit Manual Grand Total:
                            </label>
                            <input type="text" id="manualGrandTotal"
                                value="{{ $purchase?->manual_grand_total ? number_format($purchase?->manual_grand_total, 0, ',', '.') : '' }}"
                                class="w-full rounded-md border border-button-hover bg-amber-50 px-3 py-2 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                placeholder="Masukkan harga manual">
                            <input type="hidden" name="manual_grand_total" id="manualGrandTotalValue" value="{{ $purchase?->manual_grand_total }}">
                        </div>
                        <button type="button" id="resetManualPrice"
                            class="rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-button-main/80 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap hover:text-white transition duration-150 ease-in-out"
                            {{ $purchase?->manual_grand_total ? '' : 'disabled' }}>
                            Reset ke Harga Sistem
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:dynamicRows>

    <x-slot:actions>
        <div class="flex flex-col gap-3 sm:flex-row">
            @if(Request::ajax() || (!Request::is('purchase/create') && !Request::is('purchase/*/edit')))
                {{-- In Modal (Create or Edit) --}}
                <x-button.remove-button x-on:click="$dispatch('close-modal', 'create-purchase'); $dispatch('close-modal', 'edit-purchase')" type="button" class="w-full sm:w-auto">
                    <span class="font-bold">BATAL</span>
                </x-button.remove-button>
            @else
                {{-- On Page --}}
                <x-button.remove-button href="/purchase" class="w-full sm:w-auto">
                    <span class="font-bold">BATAL</span>
                </x-button.remove-button>
            @endif

            <x-button.add-button type="submit" class="w-full sm:w-auto">
                <span class="font-bold">{{ $isEdit ? 'SIMPAN PERUBAHAN' : 'SIMPAN PEMBELIAN PRODUK' }}</span>
            </x-button.add-button>
        </div>
    </x-slot:actions>
</x-content.form-card>

{{-- Modal Tambah Produk Baru --}}
<x-modal name="create-product" title="TAMBAH PRODUK BARU" maxWidth="4xl">
    <div class="p-1">
        @include('product._form', [
            'action' => route('product.store'), 
            'method' => 'POST', 
            'categories' => $categories,
            'isAjax' => true
        ])
    </div>
</x-modal>
