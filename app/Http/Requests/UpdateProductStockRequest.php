<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_new_batch' => 'required|boolean',
            'batch_id' => [
                'exclude_if:is_new_batch,1',
                'required',
                Rule::exists('product_stocks', 'id'),
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
            'batch_id.required' => 'Batch wajib dipilih.',
            'batch_id.exists' => 'Batch tidak ditemukan.',

            'stock_opname.required' => 'Jumlah stok wajib diisi.',
            'stock_opname.numeric' => 'Jumlah stok harus berupa angka.',
            'stock_opname.min' => 'Jumlah stok tidak boleh kurang dari 0.',

            'expired_date.date' => 'Tanggal kedaluwarsa tidak valid.',
            'expired_date.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh sebelum hari ini.',
        ];
    }
}
