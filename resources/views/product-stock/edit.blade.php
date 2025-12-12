<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Sesuaikan Stok Produk: {{ $stock->product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('stock.update', $stock->id) }}" method="PUT" showBatchSelector="true">
                {{-- Batch Selector --}}
                <x-slot name="batchSelector">
                    <div class="flex items-center gap-3">

                        <x-content.form-select name="batch_id" class="w-full">
                            <option value="">Pilih Batch</option>
                            @if (isset($batches))
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->id }}">BATCH {{ $batch->number }}</option>
                                @endforeach
                            @endif
                        </x-content.form-select>

                    </div>
                </x-slot>

                <x-slot name="mainSection">true</x-slot>

                {{-- LEFT COLUMN --}}
                <x-slot:leftCol>
                    <div class="space-y-5">

                        <x-content.form-input label="Nama Produk" name="product_name_display" :value="$stock->product->name" disabled
                            readonly class="bg-gray-100" />

                        <x-content.form-input label="Kode Produk" name="product_code_display" :value="$stock->product->code_id ?? $stock->product_id" disabled
                            readonly class="bg-gray-100" />

                        <x-content.form-currency label="Harga Produk per Satuan (Konsumen)" name="price_consumer"
                            placeholder="Rp 10 xxx" :value="old('price_consumer', $stock->price_consumer ?? 0)" />

                        <x-content.form-currency label="Harga Produk per Satuan (R3)" name="price_r3"
                            placeholder="Rp 10 xxx" :value="old('price_r3', $stock->price_r3 ?? 0)" />

                    </div>
                </x-slot:leftCol>

                {{-- RIGHT COLUMN --}}
                <x-slot:rightCol>
                    <div class="space-y-5">

                        <x-content.form-currency label="Stok Produk" name="stock_opname" placeholder="0"
                            :value="old('stock_opname', $stock->stock_opname)" required />

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900">
                                Waktu Kadaluarsa dari Hari Ini
                            </label>

                            <div class="grid grid-cols-2 gap-3">

                                {{-- Number --}}
                                <x-content.form-currency name="expiry_date" placeholder="1" :value="old('expiry_date', $stock->expiry_value ?? 1)" />

                                {{-- Unit --}}
                                <x-content.form-select name="expiry_unit" required>
                                    <option value="days"
                                        {{ old('expiry_unit', $stock->expiry_unit) == 'days' ? 'selected' : '' }}>HARI
                                    </option>
                                    <option value="weeks"
                                        {{ old('expiry_unit', $stock->expiry_unit) == 'weeks' ? 'selected' : '' }}>
                                        MINGGU</option>
                                    <option value="months"
                                        {{ old('expiry_unit', $stock->expiry_unit) == 'months' ? 'selected' : '' }}>
                                        BULAN</option>
                                    <option value="years"
                                        {{ old('expiry_unit', $stock->expiry_unit) == 'years' ? 'selected' : '' }}>TAHUN
                                    </option>
                                </x-content.form-select>

                            </div>
                        </div>

                        <x-content.form-currency label="Harga Produk per Satuan (R1)" name="price_r1"
                            placeholder="Rp 10 xxx" :value="old('price_r1', $stock->price_r1 ?? 0)" required />

                    </div>
                </x-slot:rightCol>

                {{-- ACTION BUTTONS --}}
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
