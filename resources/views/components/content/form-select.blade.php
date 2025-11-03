@props(['label', 'name'])

<div class="w-full">
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-bold text-black">
        {{ $label }}
    </label>
    <select id="{{ $name }}" name="{{ $name }}"
        {{ $attributes->merge(['class' => 'block w-full px-2 rounded-lg border-2 focus:border-button-hover focus:outline-none py-2 cursor-pointer transition-all duration-100 text-sm font-normal']) }}>
        {{ $slot }}
    </select>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
