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

                {{-- LEFT COLUMN --}}
                <x-slot:leftCol>
                    <div class="space-y-5">

                        <x-content.form-input label="Nama Produk" name="product_name_display" :value="$activeStock->product->name" disabled
                            readonly class="cursor-not-allowed border-gray-300 bg-gray-100" />

                        <x-content.form-input label="Kode Produk" name="product_code_display" :value="$activeStock->product->code_id ?? $activeStock->product_id" disabled
                            readonly class="cursor-not-allowed border-gray-300 bg-gray-100" />

                        <x-content.form-currency label="Harga Produk per Satuan (Konsumen)" name="price_consument"
                            placeholder="Rp 10 xxx" :value="old('price_consument', $activeStock->price_consument)" required />

                        <x-content.form-currency label="Harga Produk per Satuan (R1)" name="price_r1"
                            placeholder="Rp 10 xxx" :value="old('price_r1', $activeStock->price_r1)" required />

                    </div>
                </x-slot:leftCol>

                {{-- RIGHT COLUMN --}}
                <x-slot:rightCol>
                    <div class="space-y-5">

                        <x-content.form-currency label="Stok Produk" name="stock_opname" placeholder="0"
                            :value="old('stock_opname', $activeStock->stock_opname)" required />

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-gray-900">
                                Waktu Kadaluarsa dari Hari Ini <br>
                                <span class="text-xs font-normal text-gray-600">
                                    {{ \Carbon\Carbon::today()->locale('id')->translatedFormat('l, d F Y') }}
                                </span>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                {{-- JUMLAH --}}
                                <input type="number" name="expiry_date" min="0"
                                    value="{{ old('expiry_date', $expiryValue) }}"
                                    class="w-full rounded-lg border-2 border-black px-4 text-lg focus:outline-none"
                                    placeholder="Jumlah" />

                                {{-- UNIT --}}
                                <x-content.form-select name="expiry_unit">
                                    <option value="days"
                                        {{ old('expiry_unit', $expiryUnit) == 'days' ? 'selected' : '' }}>HARI</option>
                                    <option value="weeks"
                                        {{ old('expiry_unit', $expiryUnit) == 'weeks' ? 'selected' : '' }}>MINGGU
                                    </option>
                                    <option value="months"
                                        {{ old('expiry_unit', $expiryUnit) == 'months' ? 'selected' : '' }}>BULAN
                                    </option>
                                    <option value="years"
                                        {{ old('expiry_unit', $expiryUnit) == 'years' ? 'selected' : '' }}>TAHUN
                                    </option>
                                </x-content.form-select>
                            </div>

                            <p id="expiredPreview" class="mt-2 text-xs font-medium text-gray-700">
                                Kadaluarsa pada:
                                <span class="font-semibold text-gray-900 text-xs">
                                    {{ \Carbon\Carbon::today()->add($expiryUnit, $expiryValue)->locale('id')->translatedFormat('l, d F Y') }}
                                </span>
                            </p>
                        </div>


                        <x-content.form-currency label="Harga Produk per Satuan (R2)" name="price_r2"
                            placeholder="Rp 10 xxx" :value="old('price_r2', $activeStock->price_r2)" required />
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

<script>
    function resetCurrencyField(name, value) {
        // 1) set hidden input (yang dikirim ke backend)
        const hidden = document.querySelector(`input[type="hidden"][name="${name}"]`);
        if (hidden) {
            hidden.value = String(value);
            hidden.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            hidden.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }

        // 2) set display input (yang kelihatan di UI) pakai AutoNumeric instance
        const display = document.getElementById(`${name}_display`);
        if (display && window.AutoNumeric) {
            const an = AutoNumeric.getAutoNumericElement(display);
            if (an) {
                an.set(String(value)); // update tampilan & internal state
            } else {
                // fallback kalau instance belum kebentuk
                display.value = String(value);
                display.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                display.dispatchEvent(new Event('keyup', {
                    bubbles: true
                }));
            }
        }
    }

    function resetNormalInput(name, value) {
        const input = document.querySelector(`[name="${name}"]`);
        if (!input) return;
        input.value = value;
        input.dispatchEvent(new Event('input', {
            bubbles: true
        }));
        input.dispatchEvent(new Event('change', {
            bubbles: true
        }));
    }

    function resetSelect(name, value) {
        const select = document.querySelector(`[name="${name}"]`);
        if (!select) return;
        select.value = value;
        select.dispatchEvent(new Event('change', {
            bubbles: true
        }));
    }

    function updatePreview() {
        const expiryInput = document.querySelector('input[name="expiry_date"]');
        const expiryUnit = document.querySelector('select[name="expiry_unit"]');
        const preview = document.getElementById('expiredPreview');
        if (!expiryInput || !expiryUnit || !preview) return;

        const value = parseInt(expiryInput.value, 10) || 0;
        const unit = expiryUnit.value;

        const now = new Date();
        const expiredDate = new Date(now);

        switch (unit) {
            case 'days':
                expiredDate.setDate(now.getDate() + value);
                break;
            case 'weeks':
                expiredDate.setDate(now.getDate() + value * 7);
                break;
            case 'months':
                expiredDate.setMonth(now.getMonth() + value);
                break;
            case 'years':
                expiredDate.setFullYear(now.getFullYear() + value);
                break;
        }

        const formatted = expiredDate.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });

        preview.innerHTML = `Kadaluarsa pada: <span class="font-semibold text-gray-900">${formatted}</span>`;
    }

    function handleBatchChange(value) {
        const flag = document.getElementById('is_new_batch');

        if (value !== 'new') {
            flag.value = 0;
            window.location.href =
                '{{ route('stock.edit', $activeStock->id) }}?batch_id=' + value;
            return;
        }

        // MODE BATCH BARU
        flag.value = 1;

        resetCurrencyField('stock_opname', 0);
        resetCurrencyField('price_consument', 0);
        resetCurrencyField('price_r1', 0);
        resetCurrencyField('price_r2', 0);

        resetNormalInput('expiry_date', 1);
        resetSelect('expiry_unit', 'days');

        updatePreview();
    }


    document.addEventListener('DOMContentLoaded', function() {
        const expiryInput = document.querySelector('input[name="expiry_date"]');
        const expiryUnit = document.querySelector('select[name="expiry_unit"]');

        if (expiryInput) expiryInput.addEventListener('input', updatePreview);
        if (expiryUnit) expiryUnit.addEventListener('change', updatePreview);

        updatePreview();
    });
</script>
