<x-app-layout>
    <div class="py-12 font-mont">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @include('customer-r2._form', ['action' => route('customer-r2.store'), 'method' => 'POST'])
        </div>
    </div>
</x-app-layout>
