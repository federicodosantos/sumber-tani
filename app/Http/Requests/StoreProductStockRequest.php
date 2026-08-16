<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::unique('product_stocks', 'product_id')
                    ->whereNull('deleted_at'),
            ],
            'unit_price' => 'nullable|numeric|min:0',
            'stock_opname' => 'required|numeric|min:0',
            'price_consument' => 'required|numeric|min:0',
            'price_r1' => 'required|numeric|min:0',
            'price_r2' => 'required|numeric|min:0',
            'expired_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk tidak ditemukan.',
            'product_id.unique' => 'Produk ini sudah memiliki data stok. Gunakan opsi "Ubah Jumlah Stok".',

            'stock_opname.required' => 'Jumlah stok wajib diisi.',
            'stock_opname.numeric' => 'Jumlah stok harus berupa angka.',
            'stock_opname.min' => 'Jumlah stok tidak boleh kurang dari 0.',

            'expired_date.required' => 'Tanggal kedaluwarsa wajib diisi.',
            'expired_date.date' => 'Tanggal kedaluwarsa tidak valid.',
            'expired_date.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh sebelum hari ini.',
        ];
    }
}
