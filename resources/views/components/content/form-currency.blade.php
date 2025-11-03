@props(['label', 'name', 'value' => '', 'placeholder' => ''])

<div x-data="{
    autoNumericInstance: null,
    rawValue: '{{ old($name, $value) }}'
}" x-init="autoNumericInstance = new AutoNumeric($refs.displayInput, {
    digitGroupSeparator: '.',
    decimalCharacter: ',',
    decimalPlaces: 0,
    minimumValue: '0'
});

if (rawValue) {
    autoNumericInstance.set(rawValue);
}">
    <label for="{{ $name }}_display" class="mb-1.5 block text-sm font-bold text-black">
        {{ $label }}
    </label>

    <input type="text" id="{{ $name }}_display" x-ref="displayInput" inputmode="numeric"
        placeholder="{{ $placeholder }}" x-on:keyup="rawValue = autoNumericInstance.getNumericString()"
        x-on:change="rawValue = autoNumericInstance.getNumericString()"
        class="focus:border-button-hover transition-all duration-100  block w-full rounded-lg border-2 px-3 py-2 focus:outline-none"
        {{ $attributes->whereDoesntStartWith('name') }}>

    <input type="hidden" id="{{ $name }}" name="{{ $name }}" x-model="rawValue">

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
