<?php

namespace App\Models;

use App\Services\DecimalMathService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPurchaseDetail extends Model
{
    /** Belum ada barang yang datang sama sekali. */
    public const STATUS_PENDING = 'pending';

    /** Sebagian sudah datang, sisanya masih ditunggu. */
    public const STATUS_PARTIAL = 'partial';

    /** Seluruh qty sudah masuk stok. */
    public const STATUS_RECEIVED = 'received';

    /** Sisa dinyatakan tidak akan datang lagi. */
    public const STATUS_CLOSED = 'closed';

    public const MODE_DIRECT = 'direct';

    public const MODE_PENDING = 'pending';

    protected $table = 'product_purchase_details';

    protected $fillable = [
        'product_id',
        'product_code',
        'product_name',
        'unit',
        'het_price',
        'basic_discount',
        'additional_discount',
        'net_price',
        'price',
        'quantity',
        'subtotal',
        'expired_date',
        'received_quantity',
        'receipt_mode',
        'closed_at',
        'closed_reason',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'quantity' => 'decimal:3',
        'het_price' => 'decimal:3',
        'basic_discount' => 'decimal:3',
        'additional_discount' => 'decimal:3',
        'net_price' => 'decimal:3',
        'price' => 'decimal:3',
        'subtotal' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'closed_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ProductPurchase::class, 'product_purchase_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ProductPurchaseReceipt::class, 'product_purchase_detail_id');
    }

    /**
     * Sisa yang masih ditunggu. Tidak pernah negatif.
     */
    public function getOutstandingQuantityAttribute(): string
    {
        $math = app(DecimalMathService::class);
        $outstanding = $math->subtract((string) $this->quantity, (string) $this->received_quantity);

        return $math->isNegative($outstanding) ? '0.000' : $outstanding;
    }

    /**
     * Status dihitung, tidak disimpan — supaya tidak ada angka kedua yang
     * bisa melenceng dari received_quantity.
     */
    public function getReceiptStatusAttribute(): string
    {
        $math = app(DecimalMathService::class);

        if ($math->compare((string) $this->received_quantity, (string) $this->quantity) >= 0) {
            return self::STATUS_RECEIVED;
        }

        if ($this->closed_at !== null) {
            return self::STATUS_CLOSED;
        }

        return $math->isPositive((string) $this->received_quantity)
            ? self::STATUS_PARTIAL
            : self::STATUS_PENDING;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->receipt_status === self::STATUS_RECEIVED;
    }

    /**
     * Baris yang masih menunggu barang: belum lengkap dan belum ditutup.
     *
     * whereColumn dipakai agar query valid di MySQL maupun SQLite.
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereNull('closed_at')
            ->whereColumn('received_quantity', '<', 'quantity');
    }
}
