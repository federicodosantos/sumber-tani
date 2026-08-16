@props(['name' => null, 'label' => null, 'value' => '', 'placeholder' => '0', 'containerClass' => '', 'decimals' => 2])

<div x-data="{
    displayAmount: '',
    rawAmount: '',
    isTypingDecimal: false,
    maxDecimals: parseInt('{{ $decimals }}', 10) || 2,
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
     * - Format ID display: '25.000,50' -> '25000.50'
     * - Format raw/backend: '25000.50' -> '25000.50' (tidak berubah)
     * - Integer: '25000' -> '25000'
     */
    toRaw(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().trim();
        if (str === '') return '';

        if (str.includes(',')) {
            // Format ID: titik = ribuan, koma = desimal
            return str.replace(/\./g, '').replace(',', '.');
        } else {
            // Format raw (titik = desimal) atau integer murni
            // Bersihkan karakter non-numerik kecuali titik pertama
            let clean = str.replace(/[^0-9.]/g, '');
            let firstDot = clean.indexOf('.');
            if (firstDot !== -1) {
                clean = clean.substring(0, firstDot + 1) + clean.substring(firstDot + 1).replace(/\./g, '');
            }
            return clean;
        }
    },

    /**
     * Format nilai raw (titik desimal) ke tampilan Indonesia (titik ribuan, koma desimal).
     * Mempertahankan trailing comma dan digit desimal yang sedang diketik.
     */
    toDisplay(raw) {
        if (!raw || raw === '') return '';

        let str = raw.toString();
        let [intPart, decPart] = str.split('.');

        let intNum = parseInt(intPart || '0', 10);
        if (isNaN(intNum)) return '';
        if (intNum === 0 && decPart === undefined) return '';

        let intFormatted = intNum === 0 ? '0' : intNum.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        if (decPart !== undefined) {
            return intFormatted + ',' + decPart;
        }
        return intNum === 0 ? '' : intFormatted;
    },

    /**
     * Titik masuk utama untuk mengubah nilai dari luar (backend, event, init).
     * val bisa berupa format raw (titik desimal) atau format ID display.
     */
    updateValues(val) {
        const currentName = this.getComponentName();
        if (val === null || val === undefined || val === '') {
            this.rawAmount = '';
            this.displayAmount = '';
            this.$dispatch('rupiah-change', { value: '', name: currentName });
            return;
        }

        let raw = this.toRaw(val);

        // Bulatkan ke maks 'maxDecimals' digit desimal (konsisten dengan pembulatan MySQL)
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

    /**
     * Dipanggil saat user mengetik langsung di input display.
     * Menjaga trailing comma agar user bisa menyelesaikan pengetikan desimal.
     */
    onDisplayInput(displayVal) {
        const currentName = this.getComponentName();

        if (!displayVal || displayVal === '') {
            this.rawAmount = '';
            this.displayAmount = '';
            this.$dispatch('rupiah-change', { value: '', name: currentName });
            return;
        }

        // Jika user sedang mengetik trailing comma (misal '25000,') — jangan proses dulu
        if (displayVal.endsWith(',')) {
            this.rawAmount = this.toRaw(displayVal.slice(0, -1)) || '';
            // Biarkan displayAmount apa adanya (dengan trailing comma)
            this.$dispatch('rupiah-change', { value: this.rawAmount, name: currentName });
            return;
        }

        let raw = this.toRaw(displayVal);

        // Bulatkan ke maks 'maxDecimals' digit desimal (konsisten dengan pembulatan MySQL)
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
        // Reformat display (tambahkan pemisah ribuan jika perlu)
        // Pertahankan bagian desimal apa adanya saat sedang diketik
        let [dInt, dDec] = displayVal.split(',');
        let intNum = parseInt(dInt.replace(/\./g, '') || '0', 10);
        let intFormatted = intNum === 0 ? '' : intNum.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        this.displayAmount = dDec !== undefined ? intFormatted + ',' + dDec : intFormatted;

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
                'class' => 'block w-full rounded-md border border-gray-300 focus:border-button-hover pl-8 pr-3 py-2 text-sm focus:outline-none transition-all duration-100 text-right font-semibold text-gray-900' . ($attributes->has('disabled') ? ' bg-gray-100 cursor-not-allowed' : ($attributes->has('readonly') ? ' bg-gray-50 cursor-not-allowed text-gray-500' : ' bg-white'))
            ])->whereStartsWith(['disabled', 'readonly', 'required', 'autofocus', 'class']) }}>
    </div>

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
