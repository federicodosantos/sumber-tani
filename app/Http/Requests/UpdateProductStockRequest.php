<?php

namespace App\Http\Requests;

use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * expired_date opsional dan boleh dikosongkan (tombol Hapus di form,
     * penting untuk Safari yang tidak punya clear pada date input).
     * Batas after_or_equal:today hanya untuk nilai baru/berubah — nilai
     * historis yang tidak diubah tetap lolos (pola yang sama dengan
     * validasi kondisional edit pembelian).
     */
    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer|exists:products,id',
            'is_new_batch' => 'required|boolean',
            'batch_id' => [
                'exclude_if:is_new_batch,1',
                'required',
                Rule::exists('product_stocks', 'id'),
            ],
            'unit_price' => 'required|numeric|gt:0|decimal:0,3',
            'stock_opname' => 'required|numeric|min:0|decimal:0,3',
            'price_consument' => 'required|numeric|min:0|decimal:0,3',
            'price_r1' => 'required|numeric|min:0|decimal:0,3',
            'price_r2' => 'required|numeric|min:0|decimal:0,3',
            'expired_date' => 'nullable|date',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $submitted = $this->normalizeExpiry($this->input('expired_date'));

            // Kosong (hasil tombol Hapus) selalu valid.
            if ($submitted === null) {
                return;
            }

            $stored = $this->storedExpiry();

            // Nilai historis yang tidak berubah tetap lolos.
            if ($submitted === $stored) {
                return;
            }

            if ($submitted < Carbon::today()->toDateString()) {
                $validator->errors()->add(
                    'expired_date',
                    'Tanggal kedaluwarsa tidak boleh sebelum hari ini.'
                );
            }
        });
    }

    /**
     * Kadaluarsa tersimpan pada batch target: batch yang diedit, atau
     * stok acuan route saat membuat batch baru.
     */
    private function storedExpiry(): ?string
    {
        $targetId = $this->boolean('is_new_batch')
            ? $this->route('stock_id')
            : $this->input('batch_id');

        if (empty($targetId)) {
            return null;
        }

        $stock = ProductStock::find($targetId);

        return $stock?->expired_date?->toDateString();
    }

    /**
     * String kosong dan null diperlakukan sama (tidak ada expiry).
     */
    private function normalizeExpiry(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            // Format tidak valid sudah ditolak rule `date`.
            return null;
        }
    }

    public function messages(): array
    {
        return [
            'batch_id.required' => 'Batch wajib dipilih.',
            'batch_id.exists' => 'Batch tidak ditemukan.',

            'stock_opname.required' => 'Jumlah stok wajib diisi.',
            'stock_opname.numeric' => 'Jumlah stok harus berupa angka.',
            'stock_opname.min' => 'Jumlah stok tidak boleh kurang dari 0.',

            'unit_price.required' => 'Harga HPP wajib diisi.',
            'unit_price.numeric' => 'Harga HPP harus berupa angka.',
            'unit_price.gt' => 'Harga HPP harus lebih dari 0.',

            'expired_date.date' => 'Tanggal kedaluwarsa tidak valid.',
            'expired_date.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh sebelum hari ini.',
        ];
    }
}
