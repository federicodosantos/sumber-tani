@props([
    'action',
    'method' => 'POST',
    'category' => null,
    'isEdit' => false,
    'isAjax' => false,
])

<div x-data="{
    isAjax: {{ $isAjax ? 'true' : 'false' }},
    loading: false,
    errors: {},
    submitForm(e) {
        if (!this.isAjax) return;
        
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
                this.$dispatch('item-created', { type: 'category', item: data.item });
                this.$dispatch('close-modal', 'create-item-category');
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
        @submit.prevent="submitForm($event)"
    >
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div x-show="errors.general" class="mb-4 text-sm text-red-600 font-semibold" x-text="errors.general ? errors.general[0] : ''"></div>

        <x-slot:leftCol>
            <x-content.form-input 
                label="Nama Kategori" 
                name="name" 
                placeholder="Pupuk" 
                required 
                :value="old('name', $category?->name)" 
            />
            <template x-if="errors.name">
                <p class="mt-1 text-xs text-red-600 font-semibold" x-text="errors.name[0]"></p>
            </template>
        </x-slot:leftCol>

        <x-slot:rightCol>
            <x-content.form-textarea 
                label="Deskripsi Kategori" 
                name="description" 
                placeholder="Isi kategori deskripsi"
                rows="6"
                :value="old('description', $category?->description)"
            />
            <template x-if="errors.description">
                <p class="mt-1 text-xs text-red-600 font-semibold" x-text="errors.description[0]"></p>
            </template>
        </x-slot:rightCol>

        <x-slot:actions>
            @if(!$isEdit && (!Request::is('item-category/create') || $isAjax))
                {{-- In Modal --}}
                <x-button.remove-button x-on:click="$dispatch('close-modal', 'create-item-category')" type="button">
                    <span class="font-bold" x-text="loading ? '...' : 'BATAL'"></span>
                </x-button.remove-button>
            @else
                {{-- On Page --}}
                <x-button.remove-button href="/item-category">
                    <span class="font-bold">BATAL</span>
                </x-button.remove-button>
            @endif

            <x-button.add-button type="submit" ::disabled="loading">
                <span class="font-bold" x-text="loading ? 'PROSES...' : '{{ $isEdit ? 'SIMPAN PERUBAHAN' : 'TAMBAH KATEGORI' }}'"></span>
            </x-button.add-button>
        </x-slot:actions>
    </x-content.form-card>
</div>
