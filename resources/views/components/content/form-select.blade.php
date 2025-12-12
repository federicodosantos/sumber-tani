@props(['label' => null, 'name'])

<div class="w-full">
    @if (!empty($label))
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-bold text-black">
            {{ $label }}
        </label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}"
        {{ $attributes->merge(['class' => 'form-control']) }}>
        {{ $slot }}
    </select>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
