@props([
    'name',
    'options' => [], // array of ['id', 'label', 'data' => []]
    'value' => '',
    'placeholder' => 'Pilih Produk...',
    'id' => null,
])

<div x-data="{
    open: false,
    search: '',
    value: '{{ $value }}',
    options: {{ json_encode($options) }},
    selectedIndex: -1,
    get filteredOptions() {
        if (!this.search) return this.options;
        const s = this.search.toLowerCase();
        return this.options.filter(opt => opt.label.toLowerCase().includes(s));
    },
    get selectedLabel() {
        const opt = this.options.find(o => o.id == this.value);
        return opt ? opt.label : '';
    },
    select(opt) {
        this.value = opt.id;
        this.search = '';
        this.open = false;
        
        // Dispatch custom event to notify external listeners
        this.$nextTick(() => {
            $refs.hiddenInput.dispatchEvent(new CustomEvent('combobox-change', { 
                bubbles: true,
                detail: { value: opt.id, selected: opt }
            }));
        });
    }
}" class="relative w-full" @click.away="open = false" @combobox-reset.window="if($event.detail.name === '{{ $name }}') { value = ''; search = ''; }">
    {{-- Hidden Input for Form Submission --}}
    <input type="hidden" name="{{ $name }}" x-model="value" x-ref="hiddenInput" id="{{ $id ?? $name }}" {{ $attributes->merge(['class' => 'combobox-hidden']) }}>

    {{-- Selected Label Display & Search Input --}}
    <div class="relative">
        <div @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
             :class="open ? 'border-button-hover ring-2 ring-button-main/10 bg-white' : 'border-gray-200 bg-white'"
             class="flex w-full cursor-pointer items-center justify-between rounded-lg border-2 px-3 py-2 text-sm transition-all hover:border-button-hover shadow-sm">
            
            <template x-if="!open">
                <span x-text="value ? selectedLabel : '{{ $placeholder }}'" 
                      :class="value ? 'text-black font-semibold' : 'text-gray-400'"
                      class="truncate pr-4"></span>
            </template>
            
            <input x-show="open" x-ref="searchInput" x-model="search" type="text"
                   class="w-full border-none p-0 text-sm bg-transparent outline-none focus:outline-none focus-visible:outline-none focus:ring-0"
                   placeholder="Cari..."
                   @keydown.arrow-down.prevent="selectedIndex = (selectedIndex + 1) % filteredOptions.length"
                   @keydown.arrow-up.prevent="selectedIndex = (selectedIndex - 1 + filteredOptions.length) % filteredOptions.length"
                   @keydown.enter.prevent="if(selectedIndex >= 0) select(filteredOptions[selectedIndex])"
                   @keydown.escape.prevent="open = false"
            >
            
            <svg class="ml-2 h-4 w-4 text-gray-400 transition-transform flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        {{-- Dropdown --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute z-[100] mt-1 max-h-64 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-1 shadow-xl">
            
            <div class="px-1 py-1">
                <template x-for="(opt, index) in filteredOptions" :key="opt.id">
                    <div @click="select(opt)"
                         @mouseenter="selectedIndex = index"
                         :class="{
                            'bg-button-main text-white': selectedIndex === index,
                            'text-gray-700 hover:bg-gray-50': selectedIndex !== index,
                            'bg-gray-50 border-l-4 border-button-main': value == opt.id && selectedIndex !== index
                         }"
                         class="group cursor-pointer rounded-lg px-3 py-2.5 text-sm transition-all mb-0.5 last:mb-0">
                        <div class="flex items-center justify-between">
                            <span x-text="opt.label" :class="value == opt.id ? 'font-bold' : ''"></span>
                            <svg x-show="value == opt.id" class="h-4 w-4" :class="selectedIndex === index ? 'text-white' : 'text-button-main'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </template>
                
                <div x-show="filteredOptions.length === 0" class="px-3 py-6 text-center">
                    <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-xs text-gray-400">Produk tidak ditemukan</p>
                </div>
            </div>
        </div>
    </div>
</div>
