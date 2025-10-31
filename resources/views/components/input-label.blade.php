@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-mont font-medium text-sm text-black']) }}>
    {{ $value ?? $slot }}
</label>
