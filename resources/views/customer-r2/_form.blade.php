@props([
    'action',
    'method' => 'POST',
    'customer' => null,
    'isEdit' => false,
    'modalName' => null,
])

<x-content.form-card action="{{ $action }}" method="{{ $method }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @php
        $selectedType = old('type', $customer?->type ?? 'r2');
    @endphp

    <x-slot:leftCol>
        <x-content.form-input
            label="Nama Pelanggan"
            name="name"
            placeholder="Masukkan nama pelanggan"
            required
            :value="old('name', $customer?->name)"
        />

        <div class="w-full">
            <span class="mb-1.5 block text-sm font-bold text-black">Tipe Pelanggan</span>
            <div class="grid grid-cols-2 gap-2">
                @foreach (['r1' => 'R1', 'r2' => 'R2'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="{{ $value }}"
                               class="peer sr-only"
                               {{ $selectedType === $value ? 'checked' : '' }} required>
                        <div class="flex items-center justify-center gap-2 rounded-lg border-2 border-gray-300 px-3 py-2 text-sm font-bold text-gray-600 transition-all peer-checked:border-button-main peer-checked:bg-button-main/10 peer-checked:text-button-hover hover:border-gray-400">
                            <span>Pelanggan {{ $label }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('type')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <x-content.form-input
            label="Nomor Kontak"
            name="phone_number"
            placeholder="08xxxxxxxxxx"
            required
            :value="old('phone_number', $customer?->phone_number)"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            maxlength="15"
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
        @php
            $resolvedModalName = $modalName ?? (Request::is('customer-r2/create') ? null : ($isEdit ? null : 'create-customer'));
        @endphp

        @if($resolvedModalName)
            {{-- In Modal --}}
            <x-button.remove-button x-on:click="$dispatch('close-modal', '{{ $resolvedModalName }}')" type="button">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @else
            {{-- On Page --}}
            <x-button.remove-button href="{{ $isEdit && $customer ? route('customer-r2.show', $customer->id) : '/customer-r2' }}">
                <span class="font-bold">BATAL</span>
            </x-button.remove-button>
        @endif

        <x-button.add-button type="submit">
            <span class="font-bold">{{ $isEdit ? 'SIMPAN PERUBAHAN' : 'TAMBAH PELANGGAN' }}</span>
        </x-button.add-button>
    </x-slot:actions>
</x-content.form-card>
