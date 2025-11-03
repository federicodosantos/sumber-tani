<x-app-layout>
  <div class="py-12 font-mont">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

      <x-content.form-card action="{{ route('item-category.update', $itemCategory->id) }}" method="PUT">
        @csrf
        @method('PUT')

        <x-slot:leftCol>
          <x-content.form-input 
              label="Nama Kategori" 
              name="name" 
              placeholder="Pupuk"
              value="{{ old('name', $itemCategory->name) }}" 
              required />
        </x-slot:leftCol>

        <x-slot:rightCol>
          <x-content.form-textarea 
              label="Deskripsi Produk" 
              name="description" 
              placeholder="Udin sedunia..."
              rows="6">{{ old('description', $itemCategory->description) }}</x-content.form-textarea>
        </x-slot:rightCol>

        <x-slot:actions>
          <x-button.remove-button href="{{ route('item-category') }}">
            <span class="font-bold">CANCEL</span>
          </x-button.remove-button>

          <x-button.add-button type="submit">
            <span class="font-bold">UPDATE CATEGORY</span>
          </x-button.add-button>
        </x-slot:actions>

      </x-content.form-card>
    </div>
  </div>
</x-app-layout>
