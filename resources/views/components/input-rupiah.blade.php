@props(['name', 'label' => null, 'value' => '', 'placeholder' => '0'])

<div class="mb-6" x-data="{
    displayAmount: '{{ old($name, $value) ? number_format(old($name, $value), 0, '', '.') : '' }}',
    rawAmount: '{{ old($name, $value) }}',
    formatNumber(value) {
        let digits = value.toString().replace(/[^0-9]/g, '');
        this.rawAmount = digits;
        if (!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    init() {
        // Tambahkan 'Rp ' di awal saat inisialisasi jika ada nilai
        if (this.displayAmount) {
            this.displayAmount = this.displayAmount;
        }
    }
}">
    @if ($label)
        <label for="{{ $name }}_display" class="mb-1.5 block text-sm font-semibold text-black">
            {{ $label }}
        </label>
    @endif

    <div class="relative mt-2 rounded-md shadow-sm">
        {{-- Prefix Rp --}}
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <span class="text-gray-500 sm:text-sm">Rp</span>
        </div>

        {{-- Hidden input untuk dikirim ke Database (hanya angka) --}}
        <input type="hidden" name="{{ $name }}" x-model="rawAmount">

        {{-- Input tampilan --}}
        <input type="text" id="{{ $name }}_display" x-model="displayAmount" inputmode="numeric"
            @input="displayAmount = formatNumber($event.target.value)" placeholder="{{ $placeholder }}"
            style="padding-left: 2.8rem;"
            {{ $attributes->merge(['class' => 'block w-full rounded-lg border-2 focus:border-button-hover focus:outline-none px-4 py-3 text-sm transition-all']) }}>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
