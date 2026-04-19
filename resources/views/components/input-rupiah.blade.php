@props(['name' => null, 'label' => null, 'value' => '', 'placeholder' => '0', 'containerClass' => ''])

<div x-data="{
    displayAmount: '',
    rawAmount: '',
    formatNumber(value) {
        if (value === null || value === undefined || value === '') return '';
        
        // Fix for decimal values from backend (e.g. 25000.00 -> 25000)
        let str = value.toString();
        if (str.includes('.')) {
            str = str.split('.')[0];
        }
        
        let digits = str.replace(/[^0-9]/g, '');
        // If it's empty or just '0', return empty string to show placeholder
        if (!digits || digits === '0') return '';
        
        // Remove leading zeros
        digits = parseInt(digits, 10).toString();
        
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },
    updateValues(val) {
        if (val === null || val === undefined || val === '') {
            this.rawAmount = '';
            this.displayAmount = '';
            this.$dispatch('rupiah-change', { value: '', name: '{{ $name }}' });
            return;
        }

        let str = val.toString();
        if (str.includes('.')) {
            str = str.split('.')[0];
        }

        let numeric = str.replace(/[^0-9]/g, '');
        
        // Strip leading zeros
        if (numeric.length > 0) {
            numeric = parseInt(numeric, 10).toString();
        }

        // If numeric is '0', treat as empty for display/placeholder purposes
        this.rawAmount = numeric;
        this.displayAmount = (numeric === '0' || numeric === '') ? '' : this.formatNumber(numeric);
        
        this.$nextTick(() => {
            this.$dispatch('rupiah-change', { value: numeric, name: '{{ $name }}' });
        });
    },
    init() {
        this.updateValues('{{ $name ? old($name, $value) : $value }}');
        
        this.$watch('displayAmount', v => {
            let numeric = v ? v.toString().replace(/[^0-9]/g, '') : '';
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
        <label @if($name) for="{{ $name }}_display" @endif class="mb-1.5 block text-sm font-semibold text-black">
            {{ $label }}
        </label>
    @endif

    <div class="relative rounded-md shadow-sm">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="text-gray-500 sm:text-xs font-bold">Rp</span>
        </div>

        @if($name)
            <input type="hidden" name="{{ $name }}" x-model="rawAmount" 
                @if($attributes->has('id')) id="{{ $attributes->get('id') }}_value" @endif
                :disabled="{{ $attributes->has('x-bind:disabled') ? $attributes->get('x-bind:disabled') : ($attributes->has('disabled') ? 'true' : 'false') }}">
        @endif

        <input type="text" 
            @if($name) id="{{ $name }}_display" @elseif($attributes->has('id')) id="{{ $attributes->get('id') }}_display" @endif
            x-model="displayAmount" 
            inputmode="numeric"
            placeholder="{{ $placeholder }}"
            {{ $attributes->merge([
                'class' => 'block w-full rounded-md border border-gray-300 focus:border-button-hover pl-8 pr-3 py-2 text-sm focus:outline-none transition-all duration-100 text-right font-semibold text-gray-900' . ($attributes->has('disabled') ? ' bg-gray-100 cursor-not-allowed' : ' bg-white')
            ])->whereStartsWith(['disabled', 'readonly', 'required', 'autofocus', 'class']) }}>
    </div>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
