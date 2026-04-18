@props([
    'action',
    'method' => 'POST',
    'product' => null,
    'categories' => [],
    'isEdit' => false,
])

<x-content.form-card action="{{ $action }}" method="{{ $method }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <x-slot:leftCol>
        <x-content.form-input 
            label="Kode Produk" 
            name="code_id" 
            placeholder="XX-1234" 
            required 
            class="uppercase" 
            :value="old('code_id', $product?->code_id)" 
        />
        <x-content.form-input 
            label="Nama Produk" 
            name="name" 
            placeholder="Pupuk Udang" 
            required 
            :value="old('name', $product?->name)" 
        />
        <x-content.form-select label="Nama Kategori" name="item_category_id" required>
            <option value="">Pilih Kategori Produk</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ old('item_category_id', $product?->item_category_id) == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </x-content.form-select>
    </x-slot:leftCol>

    <x-slot:rightCol>
        <x-content.form-textarea 
            label="Deskripsi Produk" 
            name="description" 
            placeholder="Isi deskripsi produk"
            rows="6"
            :value="old('description', $product?->description)" 
        />
    </x-slot:rightCol>

    <x-slot:actions>
        @if(!$isEdit && !Request::is('product/create'))
            {{-- In Modal --}}
            <x-button.remove-button x-on:click="$dispatch('close-modal', 'create-product')" type="button">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @else
            {{-- On Page --}}
            <x-button.remove-button href="/product">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @endif

        <x-button.add-button type="submit">
            <span class="font-bold">{{ $isEdit ? 'SIMPAN PERUBAHAN' : 'TAMBAH PRODUK' }}</span>
        </x-button.add-button>
    </x-slot:actions>
</x-content.form-card>
