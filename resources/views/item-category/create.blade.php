<x-app-layout>
  <div class="py-12 font-mont">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

      @include('item-category._form', ['action' => route('item-category.store'), 'method' => 'POST'])
    </div>
  </div>
</x-app-layout>
