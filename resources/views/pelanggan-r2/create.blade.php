<x-app-layout>
    <div class="py-12 font-mont">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            <x-content.form-card action="{{ route('pelanggan-r2.store') }}" method="POST">
                @csrf
                <x-slot:leftCol>
                    <x-content.form-input label="Nama Pelanggan" name="name" placeholder="Masukkan nama pelanggan" required />
                    <x-content.form-input label="Nomor Kontak" name="phone_number" placeholder="08xxxxxxxxxx" required />
                </x-slot:leftCol>

                <x-slot:rightCol>
                    <x-content.form-input label="Alamat" name="address" placeholder="Masukkan alamat pelanggan" required />
                </x-slot:rightCol>

                <x-slot:actions>
                    <x-button.remove-button href="/pelanggan-r2">
                        <span class="font-bold">BATAL</span>
                    </x-button.remove-button>

                    <x-button.add-button type="submit">
                        <span class="font-bold">TAMBAH PELANGGAN</span>
                    </x-button.add-button>
                </x-slot:actions>
            </x-content.form-card>
        </div>
    </div>
</x-app-layout>
