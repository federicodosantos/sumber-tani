@props(['name' => null, 'label' => null, 'value' => '', 'placeholder' => '0', 'containerClass' => '', 'decimals' => 3])

<div x-data="{
    displayAmount: '',
    rawAmount: '',
    maxDecimals: parseInt('{{ $decimals }}', 10) || 3,
    componentName: '{{ $name }}',
    getComponentName() {
        const hiddenInput = this.$el.querySelector('input[type=hidden][name]');
        if (hiddenInput && hiddenInput.name) {
            this.componentName = hiddenInput.name;
        }
        return this.componentName;
    },

    /**
     * Konversi nilai ke format raw (titik desimal) dari berbagai format input:
     * - '1,25'      -> '1.25'  (koma = desimal)
     * - '1.25'      -> '1.25'  (titik = desimal)
     * - '1000,5'    -> '1000.5'
     */
    toRaw(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().trim();
        if (str === '') return '';

        if (str.includes(',')) {
            return str.replace(/\./g, '').replace(',', '.');
        }

        let clean = str.replace(/[^0-9.]/g, '');
        let firstDot = clean.indexOf('.');
        if (firstDot !== -1) {
            clean = clean.substring(0, firstDot + 1) + clean.substring(firstDot + 1).replace(/\./g, '');
        }
        return clean;
    },

    /**
     * Format raw (titik desimal) ke tampilan (koma desimal, tanpa pemisah ribuan).
     */
    toDisplay(raw) {
        if (!raw || raw === '') return '';

        let str = raw.toString();
        let [intPart, decPart] = str.split('.');

        let intNum = parseInt(intPart || '0', 10);
        if (isNaN(intNum)) return '';
        if (intNum === 0 && decPart === undefined) return '';

        return decPart !== undefined ? intNum + ',' + decPart : String(intNum);
    },

    updateValues(val) {
        const currentName = this.getComponentName();
        if (val === null || val === undefined || val === '') {
            this.rawAmount = '';
            this.displayAmount = '';
            this.$dispatch('rupiah-change', { value: '', name: currentName });
            return;
        }

        let raw = this.toRaw(val);

        if (raw.includes('.')) {
            const pow = Math.pow(10, this.maxDecimals);
            raw = String(Math.round(parseFloat(raw) * pow) / pow);
        }

        let numVal = parseFloat(raw);
        if (isNaN(numVal)) {
            this.rawAmount = '';
            this.displayAmount = '';
            this.$dispatch('rupiah-change', { value: '', name: currentName });
            return;
        }

        this.rawAmount = raw;
        this.displayAmount = this.toDisplay(raw);

        this.$nextTick(() => {
            this.$dispatch('rupiah-change', { value: raw, name: currentName });
        });
    },

    onDisplayInput(displayVal) {
        const currentName = this.getComponentName();

        if (!displayVal || displayVal === '') {
            this.rawAmount = '';
            this.displayAmount = '';
            this.$dispatch('rupiah-change', { value: '', name: currentName });
            return;
        }

        if (displayVal.endsWith(',')) {
            this.rawAmount = this.toRaw(displayVal.slice(0, -1)) || '';
            this.$dispatch('rupiah-change', { value: this.rawAmount, name: currentName });
            return;
        }

        let raw = this.toRaw(displayVal);

        if (raw.includes('.')) {
            const pow = Math.pow(10, this.maxDecimals);
            raw = String(Math.round(parseFloat(raw) * pow) / pow);
        }

        let numVal = parseFloat(raw);
        if (isNaN(numVal) || numVal === 0) {
            this.rawAmount = '';
            this.displayAmount = '';
            this.$dispatch('rupiah-change', { value: '', name: currentName });
            return;
        }

        this.rawAmount = raw;
        this.displayAmount = this.toDisplay(raw);

        this.$nextTick(() => {
            this.$dispatch('rupiah-change', { value: raw, name: currentName });
        });
    },

    init() {
        this.updateValues('{{ $name ? old($name, $value) : $value }}');
    }
}"
@update-rupiah-value.window="if(getComponentName() && $event.detail.name === getComponentName()) updateValues($event.detail.value)"
@update-rupiah-value="updateValues($event.detail.value)"
class="{{ $containerClass }}"
{{ $attributes->whereDoesntStartWith('class')->whereDoesntStartWith('value')->whereDoesntStartWith('placeholder') }}>
    @if ($label)
        <label @if($name) for="{{ $name }}_display" @endif class="mb-1.5 block text-sm font-semibold text-black">
            {{ $label }}
        </label>
    @endif

    <div class="relative rounded-md shadow-sm">
        @if($name)
            <input type="hidden" name="{{ $name }}" x-model="rawAmount"
                @if($attributes->has('id')) id="{{ $attributes->get('id') }}_value" @endif
                :disabled="{{ $attributes->has('x-bind:disabled') ? $attributes->get('x-bind:disabled') : ($attributes->has('disabled') ? 'true' : 'false') }}">
        @endif

        <input type="text"
            @if($name) id="{{ $name }}_display" @elseif($attributes->has('id')) id="{{ $attributes->get('id') }}_display" @endif
            x-model="displayAmount"
            inputmode="decimal"
            placeholder="{{ $placeholder }}"
            @if($attributes->has('readonly'))
            @keydown.prevent
            @paste.prevent
            tabindex="-1"
            @else
            @focus="setTimeout(() => $el.setSelectionRange($el.value.length, $el.value.length), 10)"
            @click="setTimeout(() => $el.setSelectionRange($el.value.length, $el.value.length), 10)"
            @keydown="const k=$event.key; const nav=['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End','Enter']; const ok=/[0-9]/.test(k)||nav.includes(k)||$event.ctrlKey||$event.metaKey||(k===','&&!$el.value.includes(',')); if(!ok) $event.preventDefault();"
            @input="onDisplayInput($el.value)"
            @endif
            {{ $attributes->merge([
                'class' => 'block w-full rounded-md border border-gray-300 focus:border-button-hover px-3 py-2 text-sm focus:outline-none transition-all duration-100 text-right font-semibold text-gray-900' . ($attributes->has('disabled') ? ' bg-gray-100 cursor-not-allowed' : ($attributes->has('readonly') ? ' bg-gray-50 cursor-not-allowed text-gray-500' : ' bg-white'))
            ])->whereStartsWith(['disabled', 'readonly', 'required', 'autofocus', 'class']) }}>
    </div>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>