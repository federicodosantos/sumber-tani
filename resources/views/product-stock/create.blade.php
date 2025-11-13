<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Tambah Stok Produk
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <x-content.form-card action="{{ route('stock.store') }}" method="POST">
                <x-slot:leftCol>
                    <div x-data="{
                        selectedId: '{{ $selectedProductId ?? old('product_id') }}',
                        isPreselected: {{ $selectedProductId ? 'true' : 'false' }},
                        productsMap: {{ $products->mapWithKeys(fn($p) => [$p->id => ['code' => $p->id]])->toJson() }}
                    }" class="space-y-5">
                        <x-content.form-select label="Nama Produk" name="product_id" x-model="selectedId"
                            x-bind:disabled="isPreselected" required>
                            <option value="">Pilih Product Name</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" :selected="selectedId == '{{ $product->id }}'">
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </x-content.form-select>

                        <input type="hidden" name="product_id" x-model="selectedId">

                        <x-content.form-input label="Kode Produk" name="product_code_display" placeholder="-"
                            x-bind:value="selectedId ? productsMap[selectedId].code : ''" disabled readonly
                            class="bg-gray-100" />
                    </div>
                </x-slot:leftCol>

                <x-slot:rightCol>
                    <x-content.form-currency label="Stok Produk" name="stock_opname" placeholder="0" required />
                    <x-content.form-currency label="Harga Produk per Satuan" name="price" placeholder="0.00"
                        required />
                </x-slot:rightCol>

                <x-slot:actions>
                    <x-button.remove-button href="{{ route('stock.index') }}">
                        BATAL
                    </x-button.remove-button>

                    <x-button.add-button type="submit">
                        TAMBAH STOK
                    </x-button.add-button>
                </x-slot:actions>
            </x-content.form-card>
        </div>
    </div>
</x-app-layout>
