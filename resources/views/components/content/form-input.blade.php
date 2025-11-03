@props(['label', 'name', 'type' => 'text', 'value' => ''])

<div class="w-full">
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold text-black">
        {{ $label }}
    </label>
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'block w-full px-2 rounded-lg border-2 focus:border-button-hover focus:outline-none py-2 transition-all duration-100 text-sm']) }}>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
