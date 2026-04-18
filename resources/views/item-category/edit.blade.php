<x-app-layout>
  <div class="py-12 font-mont">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

      @include('item-category._form', [
          'action' => route('item-category.update', $itemCategory->id), 
          'method' => 'POST', 
          'category' => $itemCategory, 
          'isEdit' => true
      ])
    </div>
  </div>
</x-app-layout>
