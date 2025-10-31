@props(['href'])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-medium text-white hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
    ]) }}>
    @if (isset($icon))
        <span class="h-5 w-5">
            {{ $icon }}
        </span>
    @endif

    {{ $slot }}
</a>
