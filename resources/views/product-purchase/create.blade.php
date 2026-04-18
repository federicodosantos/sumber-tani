<x-app-layout>
    <div class="font-mont py-6 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @include('product-purchase._form', [
                'action' => route('purchase.store'), 
                'method' => 'POST', 
                'products' => $products
            ])
        </div>
    </div>

    @push('scripts')
        @include('product-purchase._form-script')
    @endpush
</x-app-layout>
