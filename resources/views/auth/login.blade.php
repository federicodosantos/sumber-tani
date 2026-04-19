<x-guest-layout>
    @if(session('error') || session('status') || $errors->any())
        <div class="mb-4 p-3 rounded text-sm
            @if(session('error')) bg-red-50 text-red-700 border border-red-200
            @else bg-yellow-50 text-yellow-700 border border-yellow-200 @endif">
            {{ session('error') ?? session('status') ?? $errors->first() }}
        </div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required
                autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 
                    accent-button-main shadow-sm 
                    checked:hover:accent-button-hover
                    checked:border-black"
                    name="remember" @checked(old('remember'))>
                <span class="ms-2 text-xs mt-1 text-black">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="w-full flex justify-center items-center py-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
