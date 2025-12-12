@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => '',
    'disabled' => false,
])

<div class="w-full">
    @if (!empty($label))
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold text-black">
            {{ $label }}
        </label>
    @endif
    <input {{ $disabled ? 'disabled' : '' }} type="{{ $type }}" id="{{ $name }}" name="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'form-control']) }}>
    
        @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
