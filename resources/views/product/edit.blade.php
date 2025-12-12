<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Edit Produk: {{ $product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('product.update', $product->id) }}" method="PUT"
                title="Edit Data Produk" showBatchSelector="true">

                {{-- Main Section Border --}}
                <x-slot name="mainSection">true</x-slot>

                {{-- LEFT COL --}}
                <x-slot:leftCol>
                    <div class="space-y-5">

                        {{-- Kode Produk (tidak boleh diubah) --}}
                        <x-content.form-input label="Kode Produk" name="product_code" :value="old('product_code', $product->code_id)"
                            placeholder="XX-12345" class="cursor-not-allowed border-gray-300 bg-gray-100" readonly
                            disabled />

                        {{-- Nama Produk --}}
                        <x-content.form-input label="Nama Produk" name="product_name" :value="old('product_name', $product->name)" required />

                        {{-- Kategori Produk --}}
                        <x-content.form-select label="Kategori Produk" name="item_category_id" required>
                            <option value="">Pilih Kategori Produk</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('item_category_id', $product->item_category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-content.form-select>

                    </div>
                </x-slot:leftCol>

                {{-- RIGHT COL --}}
                <x-slot:rightCol>
                    <div class="space-y-5">

                        {{-- Deskripsi Produk --}}
                        <x-content.form-textarea label="Deskripsi Produk" name="description" rows="7"
                            placeholder="Isi deskripsi produk" :value="old('description', $product->description)" />

                    </div>
                </x-slot:rightCol>

                {{-- DETAIL PEMBELIAN --}}
                <x-slot name="detailTitle">Edit Data Detail Pembelian Produk</x-slot>

                <x-slot name="detailSection">

                    {{-- Batch Selector --}}
                    <div class="mb-5 flex items-center justify-between">
                        <x-content.form-select name="batch_id" class="w-36 flex-none rounded-xl sm:w-48">
                            <option value="">Pilih Batch</option>
                            @if (isset($batches))
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->id }}">BATCH {{ $batch->number }}</option>
                                @endforeach
                            @endif
                        </x-content.form-select>

                        <x-content.form-select name="ppn" class="ml-4 w-36 flex-none rounded-xl sm:w-48">
                            <option value="">Pilih PPN</option>
                            <option value="0" {{ old('ppn') == '0' ? 'selected' : '' }}>0%</option>
                            <option value="10" {{ old('ppn') == '10' ? 'selected' : '' }}>10%</option>
                            <option value="11" {{ old('ppn') == '11' ? 'selected' : '' }}>11%</option>
                        </x-content.form-select>
                    </div>

                    <div class="grid grid-cols-1 gap-x-6 md:grid-cols-2 lg:gap-x-8">

                        {{-- LEFT COL --}}
                        <div class="space-y-5">

                            {{-- Harga Beli --}}
                            <x-content.form-currency label="Harga Beli" name="purchase_price" placeholder="Rp. 10 xxx"
                                :value="old('purchase_price', $purchase->purchase_price ?? '')" required />

                            {{-- Jumlah Beli --}}
                            <x-content.form-input label="Jumlah Beli" name="purchase_quantity" type="number"
                                min="1" placeholder="0" :value="old('purchase_quantity', $purchase->quantity ?? '')" required />

                            {{-- Diskon --}}
                            <x-content.form-currency label="Diskon" name="discount" placeholder="0" :value="old('discount', $purchase->discount ?? '0')" />

                            {{-- Tanggal Pembelian --}}
                            <div>
                                <label class="form-label">Tanggal Pembelian</label>
                                <input type="date" name="purchase_date"
                                    value="{{ old('purchase_date', $purchase->purchase_date ?? '') }}"
                                    class="form-control" required>
                            </div>

                        </div>

                        {{-- RIGHT COL --}}
                        <div class="space-y-5">

                            {{-- Satuan --}}
                            <x-content.form-input label="Satuan" name="unit" placeholder="PAX / PCS / BOX"
                                :value="old('unit', $purchase->unit ?? '')" required />

                            {{-- Harga Sebelum PPN --}}
                            <x-content.form-currency label="Harga Sebelum PPN" name="price_before_ppn"
                                placeholder="Rp. 10 xxx" :value="old('price_before_ppn', $purchase->price_before_ppn ?? '')" required />

                            {{-- Harga Setelah PPN --}}
                            <x-content.form-currency label="Harga Setelah PPN" name="price_after_ppn"
                                placeholder="Rp. 10 xxx" :value="old('price_after_ppn', $purchase->price_after_ppn ?? '')" required />

                            {{-- Metode Pembayaran --}}
                            <x-content.form-select label="Metode Pembayaran" name="payment_method" required>
                                <option value="">Pilih Metode Pembayaran</option>

                                <option value="cash"
                                    {{ old('payment_method', $purchase->payment_method ?? '') == 'cash' ? 'selected' : '' }}>
                                    Cash
                                </option>

                                <option value="transfer"
                                    {{ old('payment_method', $purchase->payment_method ?? '') == 'transfer' ? 'selected' : '' }}>
                                    Transfer
                                </option>

                                <option value="credit"
                                    {{ old('payment_method', $purchase->payment_method ?? '') == 'credit' ? 'selected' : '' }}>
                                    Credit
                                </option>
                            </x-content.form-select>

                            {{-- Sudah Lunas --}}
                            <div class="flex items-center pt-2">
                                <input type="checkbox" id="is_paid" name="is_paid" value="1"
                                    {{ old('is_paid', $purchase->is_paid ?? false) ? 'checked' : '' }}
                                    class="text-button-main focus:bg-button-main h-4 w-4 rounded border-gray-300 outline-none focus:ring-2">
                                <label for="is_paid" class="ml-2 text-sm font-semibold text-gray-900">
                                    SUDAH LUNAS
                                </label>
                            </div>

                        </div>

                    </div>
                </x-slot>

                {{-- ACTION BUTTONS --}}
                <x-slot:actions>
                    <x-button.remove-button href="{{ route('product') }}">
                        BATAL
                    </x-button.remove-button>

                    <x-button.add-button type="submit">
                        SIMPAN PERUBAHAN
                    </x-button.add-button>
                </x-slot:actions>

            </x-content.form-card>

        </div>
    </div>

</x-app-layout>
