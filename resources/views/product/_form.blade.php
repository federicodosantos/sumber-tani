@props([
    'action',
    'method' => 'POST',
    'product' => null,
    'categories' => [],
    'isEdit' => false,
    'isAjax' => false,
    'editModalName' => null,
])

@php
    $categoryOptions = collect($categories)->map(fn($c) => [
        'id' => $c->id,
        'label' => $c->name
    ])->toArray();
@endphp

<div x-data="{
    isAjax: {{ $isAjax ? 'true' : 'false' }},
    loading: false,
    errors: {},
    submitForm(e) {
        if (!this.isAjax) return;
        
        e.preventDefault();
        this.loading = true;
        this.errors = {};
        
        const formData = new FormData(e.target);
        
        fetch(e.target.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=&quot;csrf-token&quot;]').getAttribute('content')
            }
        })
        .then(async response => {
            const data = await response.json();
            if (response.ok) {
                this.$dispatch('item-created', { type: 'product', item: data.item });
                this.$dispatch('close-modal', 'create-product');
                e.target.reset();
            } else {
                this.errors = data.errors || { general: [data.message] };
            }
        })
        .catch(err => {
            console.error(err);
            this.errors = { general: ['Terjadi kesalahan sistem.'] };
        })
        .finally(() => {
            this.loading = false;
        });
    }
}">
    <x-content.form-card 
        action="{{ $action }}" 
        method="{{ $method }}"
        @submit="submitForm($event)"
    >
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div x-show="errors.general" class="mb-4 text-sm text-red-600 font-semibold" x-text="errors.general ? errors.general[0] : ''"></div>

        <x-slot:leftCol>
            <x-content.form-input 
                label="Kode Produk" 
                name="code_id" 
                placeholder="XX-1234" 
                required 
                class="uppercase" 
                :value="old('code_id', $product?->code_id)" 
            />
            <template x-if="errors.code_id">
                <p class="mt-1 text-xs text-red-600 font-semibold" x-text="errors.code_id[0]"></p>
            </template>

            <x-content.form-input 
                label="Nama Produk" 
                name="name" 
                placeholder="Pupuk Udang" 
                required 
                :value="old('name', $product?->name)" 
            />
            <template x-if="errors.name">
                <p class="mt-1 text-xs text-red-600 font-semibold" x-text="errors.name[0]"></p>
            </template>
            
            <div class="mb-4">
                <label class="mb-2 block text-sm font-bold text-gray-700">Nama Kategori</label>
                <x-content.combobox 
                    name="item_category_id" 
                    :options="$categoryOptions" 
                    :value="old('item_category_id', $product?->item_category_id)"
                    placeholder="Pilih Kategori Produk..."
                    empty-action="create-item-category"
                    empty-label="+ Tambah Kategori Baru"
                    type="category"
                    required 
                />
                <template x-if="errors.item_category_id">
                    <p class="mt-1 text-xs text-red-600 font-semibold" x-text="errors.item_category_id[0]"></p>
                </template>
            </div>
        </x-slot:leftCol>

        <x-slot:rightCol>
            <x-content.form-textarea 
                label="Deskripsi Produk" 
                name="description" 
                placeholder="Isi deskripsi produk"
                rows="6"
                :value="old('description', $product?->description)" 
            />
            <template x-if="errors.description">
                <p class="mt-1 text-xs text-red-600 font-semibold" x-text="errors.description[0]"></p>
            </template>
        </x-slot:rightCol>

        <x-slot:actions>
            @if($editModalName)
                {{-- Edit In Modal --}}
                <x-button.remove-button x-on:click="$dispatch('close-modal', '{{ $editModalName }}')" type="button">
                    <span class="font-bold">BATAL</span>
                </x-button.remove-button>
            @elseif(!$isEdit && (!Request::is('product/create') || $isAjax))
                {{-- Create In Modal --}}
                <x-button.remove-button x-on:click="$dispatch('close-modal', 'create-product')" type="button">
                    <span class="font-bold" x-text="loading ? '...' : 'BATAL'"></span>
                </x-button.remove-button>
            @else
                {{-- On Page --}}
                <x-button.remove-button href="/product">
                    <span class="font-bold">BATAL</span>
                </x-button.remove-button>
            @endif

            <x-button.add-button type="submit" ::disabled="loading">
                <span class="font-bold" x-text="loading ? 'PROSES...' : '{{ $isEdit ? 'SIMPAN PERUBAHAN' : 'TAMBAH PRODUK' }}'"></span>
            </x-button.add-button>
        </x-slot:actions>
    </x-content.form-card>
</div>

{{-- Modal Tambah Kategori Baru --}}
<x-modal name="create-item-category" title="TAMBAH KATEGORI BARU" maxWidth="2xl">
    <div class="p-1">
        @include('item-category._form', [
            'action' => route('item-category.store'), 
            'method' => 'POST',
            'isAjax' => true
        ])
    </div>
</x-modal>
