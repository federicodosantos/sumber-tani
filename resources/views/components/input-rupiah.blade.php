@props(['name' => null, 'label' => null, 'value' => '', 'placeholder' => '0', 'containerClass' => 'mb-6'])

<div x-data="{
    displayAmount: '',
    rawAmount: '',
    formatNumber(value) {
        if (!value) return '';
        let digits = value.toString().replace(/[^0-9]/g, '');
        if (!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    updateValues(val) {
        let numeric = val ? val.toString().replace(/[^0-9]/g, '') : '';
        this.rawAmount = numeric;
        this.displayAmount = this.formatNumber(numeric);
        
        // Use Alpine standard $dispatch
        this.$dispatch('rupiah-change', { value: numeric, name: '{{ $name }}' });
    },
    init() {
        this.updateValues('{{ $name ? old($name, $value) : $value }}');
        
        this.$watch('displayAmount', v => {
            let numeric = v.replace(/[^0-9]/g, '');
            if (this.rawAmount !== numeric) {
                this.updateValues(numeric);
            }
        });
    }
}" 
@update-rupiah-value.window="if('{{ $name }}' && $event.detail.name === '{{ $name }}') updateValues($event.detail.value)"
@update-rupiah-value="updateValues($event.detail.value)"
class="{{ $containerClass }}"
{{ $attributes->whereDoesntStartWith('class')->whereDoesntStartWith('value')->whereDoesntStartWith('placeholder') }}>
    @if ($label)
        <label @if($name) for="{{ $name }}_display" @endif class="mb-1.5 block text-xs font-bold text-gray-600">
            {{ $label }}
        </label>
    @endif

    <div class="relative rounded-md shadow-sm">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="text-gray-500 sm:text-xs font-bold">Rp</span>
        </div>

        @if($name)
            <input type="hidden" name="{{ $name }}" x-model="rawAmount">
        @endif

        <input type="text" 
            @if($name) id="{{ $name }}_display" @endif
            x-model="displayAmount" 
            inputmode="numeric"
            placeholder="{{ $placeholder }}"
            class="block w-full rounded-lg border border-gray-300 pl-8 pr-3 py-2 text-sm focus:border-button-main focus:outline-none focus:ring-2 focus:ring-button-main transition-all text-right font-semibold text-gray-900">
    </div>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
