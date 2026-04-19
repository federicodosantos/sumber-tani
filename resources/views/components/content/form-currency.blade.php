@props(['label' => null, 'name', 'value' => '', 'placeholder' => ''])

<div x-data="{
    autoNumericInstance: null,
    rawValue: '{{ old($name, $value) }}'
}" x-init="autoNumericInstance = new AutoNumeric($refs.displayInput, {
    digitGroupSeparator: '.',
    decimalCharacter: ',',
    decimalPlaces: 0,
    minimumValue: '0'
});

if (rawValue) autoNumericInstance.set(rawValue);">
    @if (!empty($label))
        <label for="{{ $name }}_display" class="form-label">{{ $label }}</label>
    @endif

    <input type="text" id="{{ $name }}_display" x-ref="displayInput" inputmode="numeric"
        placeholder="{{ $placeholder }}" x-on:keyup="rawValue = autoNumericInstance.getNumericString()"
        x-on:change="rawValue = autoNumericInstance.getNumericString()"
        {{ $attributes->merge([
            'class' => 'focus:border-button-hover transition-all duration-100 block w-full rounded-lg border-2 px-3 py-2 focus:outline-none bg-white'
        ])->whereDoesntStartWith('name') }}>

    <input type="hidden" id="{{ $name }}" name="{{ $name }}" x-model="rawValue">

    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
