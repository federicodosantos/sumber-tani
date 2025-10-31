@extends('layouts.app')

@section('title', 'Create Product')

@section('content')
    <x-content.form-card action="#" method="POST">

        {{-- 1. Mengisi Kolom Kiri --}}
        <x-slot:leftCol>
            <x-content.form-input label="Kode Product" name="product_code" placeholder="XX-12345" required />

            <x-content.form-input label="Nama Product" name="product_name" placeholder="Pupuk 123" required />

            {{-- 
              Saya asumsikan label "Nama Product" yang kedua 
              seharusnya "Kategori Produk"
            --}}
            <x-content.form-select label="Kategori Produk" name="category_id" required>
                <option value="">Select Product Name</option>
                <option value="1">Pupuk</option>
                <option value="2">Bibit</option>
                <option value="3">Alat</option>
            </x-content.form-select>
        </x-slot:leftCol>

        {{-- 2. Mengisi Kolom Kanan --}}
        <x-slot:rightCol>
            <x-content.form-textarea label="Deskripsi Produk" name="description" placeholder="Udin sedunia..."
                rows="6" />
            <x-content.form-currency label="Harga Produk" name="price" placeholder="0.00" required />
        </x-slot:rightCol>

        {{-- 3. Mengisi Bagian Tombol --}}
        <x-slot:actions>
            <x-button.remove-button href="#">
                    CANCEL
                </x-button.remove-button>

                <x-button.add-button href="#">
                    CREATE PRODUCT
                </x-button.add-button>
            </x-slot:actions>

        </x-content.form-card>

@endsection
