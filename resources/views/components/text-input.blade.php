@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'p-2 rounded-md shadow-sm focus:ring-2 
focus:ring-button-main focus:ring-offset-1 focus:outline-none text-sm']) }}>
