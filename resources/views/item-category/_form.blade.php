@props([
    'action',
    'method' => 'POST',
    'category' => null,
    'isEdit' => false,
])

<x-content.form-card action="{{ $action }}" method="{{ $method }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <x-slot:leftCol>
        <x-content.form-input 
            label="Nama Kategori" 
            name="name" 
            placeholder="Pupuk" 
            required 
            :value="old('name', $category?->name)" 
        />
    </x-slot:leftCol>

    <x-slot:rightCol>
        <x-content.form-textarea 
            label="Deskripsi Kategori" 
            name="description" 
            placeholder="Isi kategori deskripsi"
            rows="6"
            :value="old('description', $category?->description)"
        />
    </x-slot:rightCol>

    <x-slot:actions>
        @if(!$isEdit && !Request::is('item-category/create'))
            {{-- In Modal --}}
            <x-button.remove-button x-on:click="$dispatch('close-modal', 'create-item-category')" type="button">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @else
            {{-- On Page --}}
            <x-button.remove-button href="/item-category">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @endif

        <x-button.add-button type="submit">
            <span class="font-bold">{{ $isEdit ? 'SIMPAN PERUBAHAN' : 'TAMBAH KATEGORI' }}</span>
        </x-button.add-button>
    </x-slot:actions>
</x-content.form-card>
