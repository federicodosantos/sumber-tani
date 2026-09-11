@props(['name' => null, 'label' => null, 'value' => '', 'placeholder' => '0', 'containerClass' => '', 'decimals' => 2])

<div x-data="{
    rawAmount: '',
    lastValidDisplay: '',
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
     * Konversi nilai dari format tampilan Indonesia (titik = ribuan, koma = desimal)
     * ke format raw (titik desimal). Digunakan untuk input display yang selalu
     * berformat Indonesia, agar ribuan seperti '500.000' tidak disalahartikan sebagai desimal.
     */
    fromDisplay(val) {
        if (!val && val !== 0) return '';
        return val.toString().trim().replace(/\./g, '').replace(',', '.');
    },

    /**
     * Format nilai raw (titik desimal) ke tampilan Indonesia (titik ribuan, koma desimal).
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
            this.lastValidDisplay = '';
            const el = this.$refs?.display;
            if (el) el.value = '';
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
            this.lastValidDisplay = '';
            const el = this.$refs?.display;
            if (el) el.value = '';
            this.$dispatch('rupiah-change', { value: '', name: currentName });
            return;
        }

        this.rawAmount = raw;
        const display = this.toDisplay(raw);
        this.lastValidDisplay = display;
        const el = this.$refs?.display;
        if (el) el.value = display;

        this.$nextTick(() => {
            this.$dispatch('rupiah-change', { value: raw, name: currentName });
        });
    },

    /**
     * Dipanggil saat user mengetik langsung di input display.
     * Hanya memperbarui nilai raw + event; penulisan ulang tampilan
     * (pemisah ribuan) ditangani formatLiveDisplay agar kursor terjaga.
     */
    onDisplayInput(displayVal) {
        const currentName = this.getComponentName();

        if (!displayVal || displayVal === '') {
            this.rawAmount = '';
            this.$nextTick(() => {
                this.$dispatch('rupiah-change', { value: '', name: currentName });
            });
            return;
        }

        // Jika user sedang mengetik trailing comma (misal '25000,') — jangan proses dulu
        if (displayVal.endsWith(',')) {
            const partialRaw = this.fromDisplay(displayVal.slice(0, -1)) || '';
            this.rawAmount = partialRaw;
            this.$nextTick(() => {
                this.$dispatch('rupiah-change', { value: partialRaw, name: currentName });
            });
            return;
        }

        let raw = this.fromDisplay(displayVal);

        let numVal = parseFloat(raw);
        if (isNaN(numVal) || numVal === 0) {
            this.rawAmount = '';
            this.$nextTick(() => {
                this.$dispatch('rupiah-change', { value: '', name: currentName });
            });
            return;
        }

        this.rawAmount = raw;
        this.$nextTick(() => {
            this.$dispatch('rupiah-change', { value: raw, name: currentName });
        });
    },

    /**
     * Format ulang tampilan secara live saat mengetik: grup bagian integer
     * dengan titik ribuan tanpa mengubah nilai raw. Posisi kursor dipulihkan
     * berdasarkan jumlah digit sebelum kursor agar mengetik di tengah
     * angka tidak membawa kursor ke ujung. Bagian desimal dibiarkan
     * apa adanya (tidak di-rounding saat mengetik).
     */
    formatLiveDisplay(el) {
        if (!el || !this.rawAmount) return;

        const caret = el.selectionStart ?? el.value.length;
        const cur = el.value;
        const commaIdx = cur.indexOf(',');

        // Hitung jumlah digit sebelum kursor (abaikan titik ribuan).
        const beforeCaret = cur.slice(0, caret);
        const digitsBeforeCaret = (beforeCaret.match(/[0-9]/g) || []).length;

        const rawInt = (commaIdx === -1 ? cur : cur.slice(0, commaIdx)).replace(/[^0-9]/g, '');
        const decPart = commaIdx === -1 ? null : cur.slice(commaIdx + 1).replace(/[^0-9]/g, '');
        if (rawInt === '') return;

        const intFormatted = rawInt.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const hasComma = commaIdx !== -1 || cur.endsWith(',');
        const next = decPart !== null ? intFormatted + ',' + decPart : (hasComma ? intFormatted + ',' : intFormatted);

        if (next === cur) {
            this.lastValidDisplay = cur;
            return;
        }

        el.value = next;

        // Petakan posisi kursor: digit ke-N sebelum kursor tetap di posisi digit ke-N.
        let target;
        if (digitsBeforeCaret <= rawInt.length) {
            let seen = 0, pos = intFormatted.length;
            for (let i = 0; i < intFormatted.length; i++) {
                if (/[0-9]/.test(intFormatted[i])) seen++;
                if (seen === digitsBeforeCaret) { pos = i + 1; break; }
            }
            if (digitsBeforeCaret === 0) pos = 0;
            target = pos;
        } else {
            // Kursor di zona desimal: pertahankan offset dari koma.
            target = intFormatted.length + 1 + (digitsBeforeCaret - rawInt.length);
        }
        target = Math.max(0, Math.min(next.length, target));
        try { el.setSelectionRange(target, target); } catch (e) {}

        this.lastValidDisplay = next;
    },

    /**
     * Dipanggil saat input kehilangan fokus: format ulang tampilan ke format
     * Indonesia yang rapi (pemisah ribuan) tanpa mengganggu kursor saat mengetik.
     */
    finalizeDisplay() {
        const el = this.$refs?.display;
        if (!el) return;
        el.value = this.rawAmount ? this.toDisplay(this.rawAmount) : '';
        this.lastValidDisplay = el.value;
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
            x-ref="display"
            inputmode="decimal"
            placeholder="{{ $placeholder }}"
            @if($attributes->has('readonly'))
            @keydown.prevent
            @paste.prevent
            tabindex="-1"
            @else
            @keydown="const k=$event.key; const nav=['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End','Enter']; if(/[0-9]/.test(k)){ const cur=$el.value; const selStart=$el.selectionStart, selEnd=$el.selectionEnd; const cand=cur.slice(0,selStart)+k+cur.slice(selEnd); const ci=cand.indexOf(','); const decLen=ci===-1?0:cand.length-ci-1; if(decLen>maxDecimals){$event.preventDefault(); return;} } else if(!(nav.includes(k)||$event.ctrlKey||$event.metaKey||(k===','&&!$el.value.includes(',')))){ $event.preventDefault(); }"
            @input="onDisplayInput($el.value); formatLiveDisplay($el)"
            @blur="finalizeDisplay()"
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
