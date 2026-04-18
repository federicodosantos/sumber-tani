<x-app-layout>
  <div class="py-12 font-mont">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

      @include('product._form', [
          'action' => route('product.update', $product->id), 
          'method' => 'POST', 
          'product' => $product, 
          'categories' => $categories, 
          'isEdit' => true
      ])
    </div>
  </div>
</x-app-layout>
