{{--
    Reusable Modal Component
    ========================
    Usage:
        <x-modal name="my-modal" title="Modal Title" maxWidth="lg">
            <p>Your content here</p>

            <x-slot name="headerAction">
                <button>Extra Header Button</button>
            </x-slot>

            <x-slot name="footer">
                <button @click="$dispatch('close-modal', 'my-modal')">Cancel</button>
                <button>Confirm</button>
            </x-slot>
        </x-modal>

    Open with:   $dispatch('open-modal', 'my-modal')
    Close with:  $dispatch('close-modal', 'my-modal')  OR  click backdrop  OR  press Escape

    Props:
        name        (required)  - Unique modal identifier for event dispatching
        title       (optional)  - Header title text
        maxWidth    (optional)  - sm | md | lg | xl | 2xl | 3xl | 4xl | 5xl | 6xl | 7xl | full  (default: md)
        zIndex      (optional)  - z-index class (default: z-50)
        closeable   (optional)  - Whether clicking backdrop / pressing Escape closes the modal (default: true)
        focusable   (optional)  - Whether to auto-focus the first focusable element (default: true)

    Slots:
        default       - Main body content
        headerAction  - Extra elements next to the close button
        footer        - Footer area (buttons, actions, etc.)
--}}

@props([
    'name',
    'title' => '',
    'maxWidth' => 'md',
    'zIndex' => 'z-50',
    'closeable' => true,
    'focusable' => true,
])

@php
$maxWidthClass = match ($maxWidth) {
    'sm'   => 'sm:max-w-sm',
    'md'   => 'sm:max-w-md',
    'lg'   => 'sm:max-w-lg',
    'xl'   => 'sm:max-w-xl',
    '2xl'  => 'sm:max-w-2xl',
    '3xl'  => 'sm:max-w-3xl',
    '4xl'  => 'sm:max-w-4xl',
    '5xl'  => 'sm:max-w-5xl',
    '6xl'  => 'sm:max-w-6xl',
    '7xl'  => 'sm:max-w-7xl',
    'full' => 'max-w-full',
    default => 'sm:max-w-md',
};
@endphp

<div
    x-data="{
        show: false,
        focusable: {{ $focusable ? 'true' : 'false' }},
        closeable: {{ $closeable ? 'true' : 'false' }},
        close() {
            if (this.closeable) {
                this.show = false;
                this.$dispatch('modal-closed', '{{ $name }}');
            }
        }
    }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') { show = true; $nextTick(() => { if (focusable) { let el = $refs.modalPanel?.querySelector('input, select, textarea, button:not([data-modal-close])'); if (el) el.focus(); } }); }"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') close()"
    x-on:keydown.escape.window="if (show) close()"
    x-show="show"
    x-cloak
    {{ $attributes->merge(['class' => 'fixed inset-0 ' . $zIndex . ' overflow-y-auto']) }}
    style="display: none;"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/30 backdrop-blur-sm"
        @click="close()"
    ></div>

    {{-- Modal Centering Wrapper --}}
    <div class="fixed inset-0 overflow-y-auto" @click="close()">
        <div class="flex min-h-full items-center justify-center p-4 text-center">

            {{-- Modal Panel --}}
            <div
                x-show="show"
                x-ref="modalPanel"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="w-full {{ $maxWidthClass }} transform rounded-2xl bg-white p-6 text-left align-middle shadow-2xl transition-all {{ $attributes->get('class', '') }}"
                @click.stop
                data-test-id="modal-{{ Str::slug($name) }}"
            >
                {{-- Header --}}
                @if ($title || isset($headerAction))
                <div class="flex items-center justify-between mb-4">
                    @if ($title)
                    <h3 class="text-lg font-bold leading-6 text-gray-900">
                        {{ $title }}
                    </h3>
                    @else
                    <div></div>
                    @endif

                    <div class="flex items-center gap-2">
                        @if (isset($headerAction))
                            {{ $headerAction }}
                        @endif

                        @if ($closeable)
                        <button
                            @click="close()"
                            data-modal-close
                            data-test-id="modal-close-button"
                            aria-label="Tutup"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors cursor-pointer"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Body --}}
                <div class="mt-2">
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                @if (isset($footer))
                <div class="mt-6 flex items-center justify-end gap-3">
                    {{ $footer }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
