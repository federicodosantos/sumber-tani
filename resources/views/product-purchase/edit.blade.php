<x-app-layout>
    <div class="py-6 sm:py-12 font-mont">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @include('product-purchase._form', [
                'action' => route('purchase.update', $purchase->id), 
                'method' => 'POST', 
                'purchase' => $purchase, 
                'products' => $products, 
                'isEdit' => true
            ])
        </div>
    </div>

    @push('scripts')
        @include('product-purchase._form-script', ['rowIndex' => $purchase->details->count()])
    @endpush
</x-app-layout>
