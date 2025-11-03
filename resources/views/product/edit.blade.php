<x-app-layout>
  <div class="py-12 font-mont">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

      <x-content.form-card action="{{ route('product.update', $product->id) }}" method="PUT">
        @csrf
        @method('PUT')

        <x-slot:leftCol>
          <x-content.form-input label="Kode Produk" name="id" value="{{ old('id', $product->id) }}" disabled="true"
            class="bg-gray-100 text-gray-500 border-gray-300 cursor-not-allowed" required />

          <x-content.form-input label="Nama Produk" name="id" value="{{ old('name', $product->name) }}" required />
          <x-content.form-select label="Nama Kategori" name="item_category_id" required>
            <option value="">Pilih Kategori Produk</option>
            @foreach ($categories as $category)
              <option value="{{ $category->id }}" {{ old('item_category_id') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
              </option>
            @endforeach
          </x-content.form-select>
        </x-slot:leftCol>

        <x-slot:rightCol>
          <x-content.form-textarea label="Deskripsi Produk" name="description" placeholder="Udin sedunia..."
            rows="6">{{ old('description', $product->description) }}</x-content.form-textarea>
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
