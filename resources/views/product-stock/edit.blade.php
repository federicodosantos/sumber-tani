<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Sesuaikan Stok Produk: {{ $stock->product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('stock.update', $stock->id) }}" method="PUT">

                <x-slot:leftCol>

                    <div class="space-y-5">

                        <x-content.form-input label="Nama Produk" name="product_name_display" :value="$stock->product->name" disabled
                            readonly class="bg-gray-100" />

                        <x-content.form-input label="Kode Produk" name="product_code_display" :value="$stock->product_id" disabled
                            readonly class="bg-gray-100" />

                    </div>
                </x-slot:leftCol>

                <x-slot:rightCol>
                    <x-content.form-currency label="Stok Produk" name="stock_opname" placeholder="0" :value="old('stock_opname', $stock->stock_opname)"
                        required />

                    <x-content.form-currency label="Harga Produk per Satuan" name="price" placeholder="0.00"
                        :value="old('price', $stock->price)" required />
                </x-slot:rightCol>

                <x-slot:actions>
                    <x-button.remove-button href="{{ route('stock.index') }}">
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
