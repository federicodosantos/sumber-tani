<x-app-layout>
  <div class="py-12 font-mont">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

      <x-content.form-card action="{{ route('product.store') }}" method="POST">
        @csrf
        <x-slot:leftCol>
          <x-content.form-input label="Kode Produk" name="code_id" placeholder="XX-1234" required class="uppercase" />
          <x-content.form-input label="Nama Produk" name="name" placeholder="Pupuk Udang" required />
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
            rows="6" />
        </x-slot:rightCol>

        <x-slot:actions>
          <x-button.remove-button href="/product">
            <span class="font-bold">BATAL</span>
          </x-button.remove-button>

          <x-button.add-button type="submit">
            <span class="font-bold">TAMBAH PRODUK</span>
          </x-button.add-button>
        </x-slot:actions>
      </x-content.form-card>
    </div>
  </div>
</x-app-layout>
