@props(['href'])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'inline-flex items-center gap-2 rounded-lg bg-cancel-button px-4 py-2 text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2
             focus:scale-95 transition transform duration-100 hover:bg-red-600',
    ]) }}>
    @if (isset($icon))
        <span class="h-5 w-5">
            {{ $icon }}
        </span>
    @endif

    {{ $slot }}
</a>
