<?php

namespace App\Services;

use App\Models\ProductPurchaseDetail;
use App\Models\ProductPurchaseReceipt;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Penerimaan barang per baris item nota pembelian.
 *
 * Aturan inti:
 * - satu penerimaan melahirkan tepat satu batch stok, sehingga FIFO
 *   (ProductStockService::allocateStockFifo, urut created_at) otomatis
 *   memakai kiriman yang datang lebih dulu;
 * - received_quantity pada detail adalah satu-satunya sumber kebenaran;
 * - pembatalan hanya boleh kalau batch yang dilahirkan masih utuh.
 */
class GoodsReceiptService
{
    public function __construct(
        private ProductStockService $stockService,
        private DecimalMathService $math,
    ) {}

    /**
     * Catat satu kedatangan barang dan masukkan ke stok.
     *
     * @param  array{quantity: string|int|float, received_date?: string|null, expired_date?: string|null, note?: string|null, user_id?: int|null}  $data
     *
     * @throws ValidationException saat baris sudah ditutup, qty <= 0, atau melebihi sisa.
     */
    public function receive(ProductPurchaseDetail $detail, array $data): ProductPurchaseReceipt
    {
        $quantity = $this->math->round((string) $data['quantity']);

        return DB::transaction(function () use ($detail, $data, $quantity) {
            // Kunci baris supaya dua request bersamaan (atau dobel-klik) tidak
            // bisa sama-sama lolos pengecekan sisa.
            $locked = ProductPurchaseDetail::whereKey($detail->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardReceivable($locked, $quantity);

            $expiredDate = array_key_exists('expired_date', $data) && $data['expired_date'] !== null && $data['expired_date'] !== ''
                ? $data['expired_date']
                : $locked->expired_date?->toDateString();

            $unitPrice = $this->math->round((string) $locked->net_price);
            $prices = $this->stockService->getLatestBatchPrices($locked->product_id);

            // Harga jual disalin dari batch terkini saat barang datang, bukan
            // saat nota dibuat — barang titipan bisa menunggu berminggu-minggu.
            $batch = $this->stockService->createNewBatch($locked->product_id, [
                'stock_opname' => $quantity,
                'unit_price' => $unitPrice,
                'price_consument' => $prices['price_consument'],
                'price_r1' => $prices['price_r1'],
                'price_r2' => $prices['price_r2'],
                'expired_date' => $expiredDate,
            ]);

            $receipt = ProductPurchaseReceipt::create([
                'product_purchase_detail_id' => $locked->id,
                'product_stock_id' => $batch->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'received_date' => $data['received_date'] ?? now()->toDateString(),
                'expired_date' => $expiredDate,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'note' => $data['note'] ?? null,
            ]);

            $locked->received_quantity = $this->math->add(
                (string) $locked->received_quantity,
                $quantity
            );
            $locked->save();

            // Samakan instance milik pemanggil dengan kondisi terbaru.
            $detail->setRawAttributes($locked->getAttributes(), true);

            return $receipt;
        });
    }

    /**
     * Batalkan satu penerimaan dan tarik kembali stoknya.
     *
     * Hanya boleh kalau batch yang dilahirkan masih menyimpan minimal
     * sebanyak qty penerimaan — kalau sudah terpakai kasir, membalikkannya
     * akan membuat stok minus dan mengacaukan FIFO.
     *
     * @throws ValidationException saat batch hilang atau sudah terpakai.
     */
    public function reverse(ProductPurchaseReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            $locked = ProductPurchaseReceipt::whereKey($receipt->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $detail = ProductPurchaseDetail::whereKey($locked->product_purchase_detail_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->reverseBatchFor($locked);

            $detail->received_quantity = $this->math->subtract(
                (string) $detail->received_quantity,
                (string) $locked->quantity
            );

            if ($this->math->isNegative($detail->received_quantity)) {
                $detail->received_quantity = '0.000';
            }

            $detail->save();
            $locked->delete();
        });
    }

    /**
     * Tutup sisa yang tidak akan datang lagi. Stok tidak disentuh.
     *
     * @throws ValidationException saat baris sudah lengkap atau sudah ditutup.
     */
    public function closeRemaining(ProductPurchaseDetail $detail, ?string $reason = null): ProductPurchaseDetail
    {
        return DB::transaction(function () use ($detail, $reason) {
            $locked = ProductPurchaseDetail::whereKey($detail->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->closed_at !== null) {
                throw ValidationException::withMessages([
                    'reason' => 'Sisa baris ini sudah ditutup sebelumnya.',
                ]);
            }

            if ($locked->is_fully_received) {
                throw ValidationException::withMessages([
                    'reason' => 'Baris ini sudah diterima penuh, tidak ada sisa untuk ditutup.',
                ]);
            }

            $locked->closed_at = now();
            $locked->closed_reason = $reason;
            $locked->save();

            $detail->setRawAttributes($locked->getAttributes(), true);

            return $locked;
        });
    }

    /**
     * Sebarkan koreksi harga beli baris nota ke penerimaan & batch yang lahir
     * darinya — itu barang yang sama, jadi HPP-nya harus ikut benar.
     *
     * Transaksi kasir yang sudah tercatat sengaja TIDAK disentuh: laba-rugi
     * periode yang sudah lewat tidak boleh berubah diam-diam.
     */
    public function syncUnitPrice(ProductPurchaseDetail $detail): void
    {
        $unitPrice = $this->math->round((string) $detail->net_price);

        DB::transaction(function () use ($detail, $unitPrice) {
            foreach ($detail->receipts()->get() as $receipt) {
                $receipt->unit_price = $unitPrice;
                $receipt->save();

                if ($receipt->product_stock_id === null) {
                    continue;
                }

                $batch = ProductStock::find($receipt->product_stock_id);

                if ($batch === null) {
                    continue;
                }

                $batch->unit_price = $unitPrice;
                $batch->save();
            }
        });
    }

    /**
     * Apakah penerimaan ini masih bisa dibatalkan (batch belum terpakai)?
     */
    public function canReverse(ProductPurchaseReceipt $receipt): bool
    {
        return $this->blockerFor($receipt) === null;
    }

    /**
     * Alasan kenapa sebuah penerimaan tidak bisa dibatalkan, atau null kalau bisa.
     */
    public function blockerFor(ProductPurchaseReceipt $receipt): ?string
    {
        if ($receipt->product_stock_id === null) {
            return 'Batch stok asal penerimaan ini tidak tercatat.';
        }

        $batch = ProductStock::withTrashed()->find($receipt->product_stock_id);

        if ($batch === null || $batch->trashed()) {
            return 'Batch stok asal penerimaan ini sudah dihapus.';
        }

        if ($this->math->compare((string) $batch->stock_opname, (string) $receipt->quantity) < 0) {
            return 'Batch '.$batch->batch.' sudah terpakai transaksi (sisa '
                .$this->trim((string) $batch->stock_opname).' dari '
                .$this->trim((string) $receipt->quantity).').';
        }

        return null;
    }

    /**
     * Nilai barang yang sudah dibeli tapi belum datang, untuk pos aset
     * "Barang Dalam Perjalanan" di neraca.
     *
     * SUM((quantity - received_quantity) * net_price) valid di MySQL maupun
     * SQLite, jadi tidak memakai fungsi khusus salah satu driver.
     */
    public function goodsInTransitValue(): string
    {
        $value = ProductPurchaseDetail::query()
            ->outstanding()
            ->sum(DB::raw('(quantity - received_quantity) * net_price'));

        return $this->math->round((string) $value);
    }

    /**
     * Baris item yang masih menunggu barang, untuk halaman Penerimaan Barang.
     */
    public function outstandingQuery(?string $search = null)
    {
        $query = ProductPurchaseDetail::query()
            ->with(['purchase', 'product:id,code_id,name', 'receipts' => fn ($q) => $q->orderBy('received_date')->orderBy('id')])
            ->outstanding();

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Jumlah baris yang masih menunggu barang (badge sidebar).
     */
    public function outstandingCount(): int
    {
        return ProductPurchaseDetail::query()->outstanding()->count();
    }

    /**
     * @throws ValidationException
     */
    private function guardReceivable(ProductPurchaseDetail $detail, string $quantity): void
    {
        if ($detail->product_id === null) {
            throw ValidationException::withMessages([
                'quantity' => 'Produk pada baris ini sudah dihapus, barang tidak bisa diterima.',
            ]);
        }

        if ($detail->closed_at !== null) {
            throw ValidationException::withMessages([
                'quantity' => 'Sisa baris ini sudah ditutup. Buka kembali lewat edit nota bila barang ternyata datang.',
            ]);
        }

        if (! $this->math->isPositive($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah yang diterima harus lebih dari 0.',
            ]);
        }

        $outstanding = $detail->outstanding_quantity;

        if ($this->math->compare($quantity, $outstanding) > 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah melebihi sisa yang belum datang (sisa '.$this->trim($outstanding).').',
            ]);
        }
    }

    /**
     * Hapus/kurangi batch yang dilahirkan sebuah penerimaan.
     *
     * @throws ValidationException
     */
    private function reverseBatchFor(ProductPurchaseReceipt $receipt): void
    {
        $blocker = $this->blockerFor($receipt);

        if ($blocker !== null) {
            throw ValidationException::withMessages(['receipt' => $blocker]);
        }

        $batch = ProductStock::whereKey($receipt->product_stock_id)
            ->lockForUpdate()
            ->firstOrFail();

        // Cek ulang di dalam lock: stok bisa berubah antara pengecekan dan sini.
        if ($this->math->compare((string) $batch->stock_opname, (string) $receipt->quantity) < 0) {
            throw ValidationException::withMessages([
                'receipt' => 'Batch '.$batch->batch.' sudah terpakai transaksi.',
            ]);
        }

        $remainder = $this->math->subtract((string) $batch->stock_opname, (string) $receipt->quantity);

        if ($this->math->isZero($remainder)) {
            // Batch ini murni milik penerimaan tersebut — buang seluruhnya.
            $batch->delete();

            return;
        }

        // Batch sempat ditambah manual lewat menu Stok; sisakan kelebihannya.
        $batch->stock_opname = $remainder;
        $batch->save();
    }

    private function trim(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.') ?: '0';
    }
}
