<button {{ $attributes->merge(['type' => 'submit', 'class' => 'flex items-center bg-button-main rounded-md font-extrabold text-sm text-white uppercase 
tracking-widest hover:bg-button-hover focus:scale-95 focus:ring-2 focus:ring-button-main 
focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
