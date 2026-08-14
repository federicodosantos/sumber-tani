@php
    function batchValue($isNewBatch, $oldValue, $defaultValue)
    {
        return $isNewBatch ? $defaultValue : $oldValue ?? $defaultValue;
    }
@endphp

<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Sesuaikan Stok Produk: {{ $activeStock->product->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('stock.update', $activeStock->id) }}" method="PUT"
                showBatchSelector="true">
                <input type="hidden" name="is_new_batch" id="is_new_batch" value="0">

                {{-- BATCH SELECTOR --}}
                <x-slot name="batchSelector">
                    <x-content.form-select name="batch_id" class="w-full" onchange="handleBatchChange(this.value)">
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $activeStock->id == $batch->id ? 'selected' : '' }}>
                                BATCH {{ $batch->batch }}
                            </option>
                        @endforeach
                        <option value="new">+ Tambah Batch Baru</option>
                    </x-content.form-select>
                </x-slot>

                <x-slot name="mainSection">true</x-slot>

                <x-slot:content>
                    {{-- Row 1: Kode & Nama --}}
                    <div class="col-span-1 md:col-span-2">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            {{-- Kode Produk --}}
                            <x-content.form-input label="Kode Produk" name="product_code_display" :value="$activeStock->product->code_id ?? $activeStock->product_id" 
                                class="cursor-not-allowed border-gray-300 bg-gray-100"
                                disabled readonly />

                            {{-- Nama Produk --}}
                            <x-content.form-input label="Nama Produk" name="product_name_display" :value="$activeStock->product->name" disabled
                                readonly class="cursor-not-allowed border-gray-300 bg-gray-100" />
                        </div>
                    </div>

                    {{-- Row 2: Harga HPP (Disabled) --}}
                    <x-input-rupiah label="Harga HPP (Unit Price)" name="unit_price"
                        :value="old('unit_price', $activeStock->unit_price)" 
                        containerClass="" placeholder="0" disabled readonly decimals="3" />

                    {{-- Row 2: Jumlah Stok --}}
                    <x-content.form-input label="Jumlah Stok" name="stock_opname" type="number"
                        placeholder="0" :value="old('stock_opname', $activeStock->stock_opname)" required />

                    {{-- Row 3: Harga Konsumen --}}
                    <x-input-rupiah label="Harga Produk per Satuan (Konsumen)" name="price_consument"
                        placeholder="0" :value="old('price_consument', $activeStock->price_consument)" containerClass="" required decimals="3" />

                    {{-- Row 3: Harga R1 --}}
                    <x-input-rupiah label="Harga Produk per Satuan (R1)" name="price_r1"
                        placeholder="0" :value="old('price_r1', $activeStock->price_r1)" containerClass="" required decimals="3" />

                    {{-- Row 4: Harga R2 --}}
                    <x-input-rupiah label="Harga Produk per Satuan (R2)" name="price_r2"
                        placeholder="0" :value="old('price_r2', $activeStock->price_r2)" containerClass="" required decimals="3" />

                    {{-- Row 4: Tanggal Kadaluarsa --}}
                    <div x-data="{
                        selectedDate: '{{ old('expired_date', $expiryValue ?? '') }}',
                        remainingText: '- Pilih tanggal -'
                    }">
                        <label for="expired_date" class="mb-2 block text-sm font-semibold text-gray-900">
                            Tanggal Kadaluarsa 
                            <span class="text-xs font-normal text-gray-600">
                                Hari ini: {{ \Carbon\Carbon::today()->locale('id')->translatedFormat('l, d F Y') }}
                            </span>
                        </label>

                        <div class="flex gap-2">
                            <div class="relative w-full">
                                <input type="date" id="expired_date" name="expired_date"
                                    min="{{ date('Y-m-d') }}" value="{{ old('expired_date', $expiryValue ?? '') }}"
                                    class="focus:border-button-main focus:ring-button-main w-full rounded-lg border-2 border-black px-2 py-2 text-sm" />
                            </div>

                            <button type="button" onclick="clearExpiry()"
                                class="rounded-lg border-2 border-red-500 px-3 py-2 text-sm font-bold text-red-500 transition hover:bg-red-500 hover:text-white"
                                title="Hapus Tanggal">
                                Hapus
                            </button>
                        </div>

                        <p id="expiredPreview" class="mt-2 text-xs font-medium text-gray-700">
                            Status: <span class="font-bold text-gray-900">- Pilih Tanggal -</span>
                        </p>
                    </div>
                </x-slot:content>

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

<script>
    // FUNGSI RESET FIELD CURRENCY (MENGGUNAKAN EVENT DISPATCH KE INPUT-RUPIAH)
    function resetCurrencyField(name, value) {
        window.dispatchEvent(new CustomEvent('update-rupiah-value', {
            detail: {
                name: name,
                value: value
            }
        }));
    }

    function clearExpiry() {
        const dateInput = document.getElementById('expired_date');
        if (dateInput) {
            dateInput.value = ''; // Kosongkan value
            updatePreview(); // Reset tulisan status jadi "- Pilih Tanggal -"
        }
    }

    // FUNGSI PREVIEW KADALUARSA
    function updatePreview() {
        const expiryInput = document.getElementById('expired_date');
        const preview = document.getElementById('expiredPreview');

        if (!expiryInput || !preview) return;

        const val = expiryInput.value; // Format: YYYY-MM-DD

        if (!val) {
            preview.innerHTML = 'Status: <span class="font-bold text-gray-900">- Pilih Tanggal -</span>';
            return;
        }

        // Logic Hitung Hari (Vanilla JS yang Aman Timezone)
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const parts = val.split('-');
        const expiry = new Date(parts[0], parts[1] - 1, parts[2]);
        expiry.setHours(0, 0, 0, 0);

        const diffTime = expiry - today;
        const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

        let text = '';
        let colorClass = 'text-gray-900';

        if (diffDays < 0) {
            text = 'SUDAH KADALUARSA ⚠️';
            colorClass = 'text-red-600';
        } else if (diffDays === 0) {
            text = 'Kadaluarsa HARI INI';
            colorClass = 'text-red-600';
        } else {
            text = `Sisa waktu ${diffDays} hari lagi`;
            colorClass = 'text-black font-bold';
        }

        preview.innerHTML = `Status: <span class="font-bold ${colorClass}">${text}</span>`;
    }

    // FUNGSI HANDLE BATCH (UPDATE LOGIC PERBAIKAN JS ERROR)
    function handleBatchChange(value) {
        const flag = document.getElementById('is_new_batch');
        if (!flag) return;

        // KASUS 1: PILIH BATCH LAMA -> RELOAD
        if (value !== 'new') {
            flag.value = '0';
            const baseUrl = "{{ route('stock.edit', $activeStock->id) }}";
            window.location.href = `${baseUrl}?batch_id=${value}`;
            return;
        }

        // KASUS 2: PILIH BATCH BARU -> RESET FORM (TANPA RELOAD)
        flag.value = '1';

        // Reset Stok (Regular Input)
        const stockInput = document.getElementById('stock_opname');
        if (stockInput) stockInput.value = '0';

        resetCurrencyField('price_consument', 0);
        resetCurrencyField('price_r1', 0);
        resetCurrencyField('price_r2', 0);

        // Reset Tanggal (Langsung akses Element ID)
        const dateInput = document.getElementById('expired_date');
        if (dateInput) {
            dateInput.value = '';
            updatePreview();
        }
    }

    // EVENT LISTENER
    document.addEventListener('DOMContentLoaded', function() {
        const expiryInput = document.getElementById('expired_date');
        if (expiryInput) {
            expiryInput.addEventListener('input', updatePreview);
            expiryInput.addEventListener('change', updatePreview);
            updatePreview();
        }
    });
</script>
