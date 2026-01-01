@php
    $expiryValue = old('expired_date', '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Tambah Stok Produk
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <x-content.form-card action="{{ route('stock.store') }}" method="POST" showBatchSelector="true">
                <x-slot name="batchSelector">
                    <div class="flex items-center gap-3">

                        <x-content.form-select name="batch_id" class="w-full">
                            <option value="">Batch Baru</option>
                            @if (isset($batches))
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->id }}">BATCH {{ $batch->number }}</option>
                                @endforeach
                            @endif
                        </x-content.form-select>

                    </div>
                </x-slot>

                <x-slot name="mainSection">true</x-slot>

                <x-slot:leftCol>
                    <div x-data="{
                        selectedId: '{{ $selectedProductId ?? old('product_id') }}',
                        isPreselected: {{ $selectedProductId ? 'true' : 'false' }},
                        productsMap: {{ $products->mapWithKeys(
                                fn($p) => [
                                    $p->id => [
                                        'code' => $p->code_id ?? $p->id,
                                        'name' => $p->name,
                                        'price_r3' => $p->price_r3 ?? 0,
                                    ],
                                ],
                            )->toJson() }}
                    }" class="space-y-5">

                        {{-- Nama Produk --}}
                        <div>
                            <x-content.form-select label="Nama Produk" name="product_id" x-model="selectedId"
                                x-bind:class="isPreselected ? 'cursor-not-allowed bg-gray-100 pointer-events-none' : ''"
                                required>
                                <option value="">Pilih Nama Produk</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" :selected="selectedId == '{{ $product->id }}'">
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </x-content.form-select>
                        </div>

                        {{-- Kode Produk --}}
                        <div>
                            <x-content.form-input label="Kode Produk" name="product_code_display"
                                x-bind:value="selectedId ? productsMap[selectedId].code : '-'" disabled readonly
                                class="cursor-not-allowed bg-gray-100" />
                        </div>

                        {{-- Harga Produk per Satuan (Konsumen) --}}
                        <div>
                            <x-content.form-currency label="Harga Produk per Satuan (Konsumen)" name="price_consument"
                                placeholder="Rp 10 xxx" required />
                        </div>

                        {{-- Harga Produk per Satuan (R3) --}}
                        <div>
                            <x-content.form-currency label="Harga Produk per Satuan (R2)" name="price_r2"
                                placeholder="Rp 10 xxx" required />
                        </div>
                    </div>
                </x-slot:leftCol>

                <x-slot:rightCol>
                    <div class="space-y-5">
                        {{-- Jumlah Stok --}}
                        <div>
                            <x-content.form-currency label="Jumlah Stok" name="stock_opname"
                                placeholder="Masukan Jumlah Stok" required />
                        </div>

                        {{-- Waktu Kadaluarsa (Calendar Mode) --}}
                        <div x-data="expiryHandler()">
                            <label for="expired_date" class="mb-2 block text-sm font-semibold text-gray-900">
                                Tanggal Kadaluarsa <br>
                                <span class="text-xs font-normal text-gray-600">
                                    Hari ini: {{ \Carbon\Carbon::today()->locale('id')->translatedFormat('l, d F Y') }}
                                </span>
                            </label>

                            <div class="relative">
                                <input type="date" id="expired_date" name="expired_date" required
                                    min="{{ date('Y-m-d') }}" value="{{ old('expired_date', $expiryValue ?? '') }}"
                                    x-model="selectedDate" @change="calculateRemaining()"
                                    class="focus:border-button-main focus:ring-button-main w-full rounded-lg border-2 border-black px-2 py-2 text-sm" />

                            </div>

                            {{-- PREVIEW SISA WAKTU (Otomatis muncul saat tanggal dipilih) --}}
                            <p class="mt-2 text-xs font-medium text-gray-700">
                                Status:
                                <span x-text="remainingText" class="text-black font-bold">
                                    - Pilih tanggal -
                                </span>
                            </p>
                        </div>

                        {{-- Harga Produk per Satuan (R1) --}}
                        <div>
                            <x-content.form-currency label="Harga Produk per Satuan (R1)" name="price_r1"
                                placeholder="Rp 10 xxx" required />
                        </div>
                    </div>
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

    @push('scripts')
        <script>
            function expiryHandler() {
                return {
                    selectedDate: "{{ old('expired_date', $expiryValue ?? '') }}",
                    remainingText: '- Pilih tanggal -',

                    init() {
                        if (this.selectedDate) {
                            this.calculateRemaining();
                        }
                    },

                    calculateRemaining() {
                        if (!this.selectedDate) {
                            this.remainingText = '- Pilih tanggal -';
                            return;
                        }

                        const today = new Date();

                        today.setHours(0, 0, 0, 0);

                        const parts = this.selectedDate.split('-');

                        const expiry = new Date(parts[0], parts[1] - 1, parts[2]);

                        expiry.setHours(0, 0, 0, 0);

                        const diffTime = expiry - today;

                        const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

                        if (diffDays < 0) {
                            this.remainingText = 'SUDAH KADALUARSA ⚠️';
                        } else if (diffDays === 0) {
                            this.remainingText = 'Kadaluarsa HARI INI';
                        } else {
                            this.remainingText = `Sisa waktu ${diffDays} hari lagi`;
                        }
                    }
                }
            }
        </script>
    @endpush
</x-app-layout>
