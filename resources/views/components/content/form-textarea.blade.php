@props(['label', 'name', 'rows' => 4, 'value' => ''])

<div class="w-full">
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-bold text-black">
        {{ $label }}
    </label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'block w-full px-2 py-1 rounded-lg border-2 focus:border-button-hover transition-all duration-100 focus:outline-none sm:text-sm']) }}>{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
