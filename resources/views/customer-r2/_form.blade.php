@props([
    'action',
    'method' => 'POST',
    'customer' => null,
    'isEdit' => false,
])

<x-content.form-card action="{{ $action }}" method="{{ $method }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <x-slot:leftCol>
        <x-content.form-input 
            label="Nama Pelanggan" 
            name="name" 
            placeholder="Masukkan nama pelanggan"
            required 
            :value="old('name', $customer?->name)" 
        />
        <x-content.form-input 
            label="Nomor Kontak" 
            name="phone_number" 
            placeholder="08xxxxxxxxxx" 
            required 
            :value="old('phone_number', $customer?->phone_number)" 
        />
    </x-slot:leftCol>

    <x-slot:rightCol>
        <x-content.form-input 
            label="Alamat" 
            name="address" 
            placeholder="Masukkan alamat pelanggan"
            required 
            :value="old('address', $customer?->address)" 
        />
    </x-slot:rightCol>

    <x-slot:actions>
        @if(!$isEdit && !Request::is('customer-r2/create'))
            {{-- In Modal --}}
            <x-button.remove-button x-on:click="$dispatch('close-modal', 'create-customer')" type="button">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @else
            {{-- On Page --}}
            <x-button.remove-button href="/customer-r2">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @endif

        <x-button.add-button type="submit">
            <span class="font-bold">{{ $isEdit ? 'SIMPAN PERUBAHAN' : 'TAMBAH PELANGGAN' }}</span>
        </x-button.add-button>
    </x-slot:actions>
</x-content.form-card>
