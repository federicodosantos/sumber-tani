<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductPurchaseDetail;
use App\Services\DecimalMathService;
use App\Services\GoodsReceiptService;
use App\Services\ProductStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductPurchaseController extends Controller
{
    protected ProductStockService $stockService;

    protected GoodsReceiptService $receiptService;

    public function __construct(ProductStockService $stockService, GoodsReceiptService $receiptService)
    {
        $this->stockService = $stockService;
        $this->receiptService = $receiptService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProductPurchase::query();

        // 📅 FILTER TANGGAL (RANGE)
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('purchase_date', [$request->date_from, $request->date_to]);
        }

        // 📅 FILTER SATU TANGGAL (kalau cuma isi salah satu)
        elseif ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }

        // 🔃 SORTING
        switch ($request->input('sort')) {
            case 'purchase_date_asc':
                $query->orderBy('purchase_date', 'asc');
                break;

            case 'purchase_date_desc':
                $query->orderBy('purchase_date', 'desc');
                break;

            case 'method_asc':
                $query->orderBy('payment_method', 'asc');
                break;

            case 'total_asc':
                $query->orderBy('grand_total', 'asc');
                break;

            case 'total_desc':
                $query->orderBy('grand_total', 'desc');
                break;

            case 'paid':
                $query->where('is_paid', true)->orderBy('purchase_date', 'desc');
                break;

            case 'unpaid':
                $query->where('is_paid', false)->orderBy('purchase_date', 'desc');
                break;

            default:
                $query->latest();
                break;
        }

        // details di-eager load untuk badge status penerimaan (hindari N+1).
        $purchases = $query->with('details')->paginate(10)->withQueryString();
        $products = Product::select('id', 'code_id', 'name')->orderBy('code_id')->get();
        $categories = ItemCategory::orderBy('name', 'asc')->get();

        // Jika validasi EDIT baru saja gagal, bawa konteks purchase yang sedang
        // diedit agar index membuka ulang modal edit (bukan modal create).
        $editPurchaseId = session('edit_purchase_id');
        $pendingEditPurchase = null;
        if ($editPurchaseId) {
            $pendingEditPurchase = ProductPurchase::with('details')->find($editPurchaseId);

            // Konteks edit stale (purchase sudah tidak ada): bersihkan marker agar
            // halaman tidak jatuh ke alur create dengan old input dari edit.
            if (! $pendingEditPurchase) {
                session()->forget('edit_purchase_id');
            }
        }

        return view('product-purchase.index', compact('purchases', 'products', 'categories', 'editPurchaseId', 'pendingEditPurchase'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::select('id', 'code_id', 'name')->orderBy('code_id')->get();
        $categories = ItemCategory::orderBy('name', 'asc')->get();

        return view('product-purchase.create', compact('products', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $math = app(DecimalMathService::class);
        $data = $this->prepareRequestData($request);
        $validated = Validator::make($data, [
            'purchase_date' => ['required', 'date'],
            'ppn' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'ppn_type' => ['required', 'in:percent,nominal'],
            'discount_type' => ['required', 'in:percent,nominal'],
            'discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'method' => ['required', 'integer', 'in:0,12'],
            'manual_grand_total' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.het_price' => ['required', 'decimal:0,3'],
            'products.*.basic_discount' => ['nullable', 'decimal:0,3'],
            'products.*.additional_discount' => ['nullable', 'decimal:0,3'],
            'products.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001'],
            'products.*.unit' => ['required', 'string', 'max:50'],
            'products.*.expired_date' => ['nullable', 'date', 'after_or_equal:today'],
            'products.*.direct_stock' => ['nullable', 'boolean'],
        ])->validate();

        $paymentMethod = (int) $validated['method'] === 0 ? 'cash' : 'credit';
        $isPaid = $paymentMethod === 'cash' ? true : $request->boolean('isPaid');
        $discountType = $validated['discount_type'];
        $ppnType = $validated['ppn_type'];

        $items = collect($validated['products'])->map(function ($item) use ($math) {
            $product = Product::findOrFail($item['product_id']);
            $het = $math->round($this->parseNumber($item['het_price']));
            $basicDisc = $math->round($this->parseNumber($item['basic_discount'] ?? '0'));
            $addDisc = $math->round($this->parseNumber($item['additional_discount'] ?? '0'));
            $qty = $math->round(str_replace(',', '.', (string) $item['quantity']));

            $netPrice = $math->subtract($math->subtract($het, $basicDisc), $addDisc);
            $subtotal = $math->multiply($netPrice, $qty);

            return [
                'product_id' => $product->id,
                'product_code' => $product->code_id,
                'product_name' => $product->name,
                'unit' => $item['unit'],
                'het_price' => $het,
                'basic_discount' => $basicDisc,
                'additional_discount' => $addDisc,
                'net_price' => $netPrice,
                'price' => $netPrice, // maintains compatibility
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'expired_date' => $item['expired_date'] ?? null,
                'receipt_mode' => $this->isDirectStock($item)
                    ? ProductPurchaseDetail::MODE_DIRECT
                    : ProductPurchaseDetail::MODE_PENDING,
                // Dipakai alur update untuk mencocokkan baris lama. Dibuang
                // sebelum persist — 'id' bukan kolom yang boleh diisi massal.
                'id' => $item['id'] ?? null,
            ];
        });

        $subtotal = '0.000';
        $totalItems = '0.000';
        foreach ($items as $item) {
            $subtotal = $math->add($subtotal, $item['subtotal']);
            $totalItems = $math->add($totalItems, $item['quantity']);
        }

        // Hitung diskon berdasarkan tipe
        $discountInput = $math->round($validated['discount'] ?? 0);
        if ($discountType === 'percent') {
            $discountPercent = $discountInput;
            $discountValue = $math->multiply($subtotal, $math->divide($discountPercent, '100'));
        } else {
            $discountValue = $discountInput;
            $discountPercent = $math->isPositive($subtotal)
                ? $math->multiply($math->divide($discountValue, $subtotal), '100')
                : '0.000';
        }

        $afterDiscount = $math->subtract($subtotal, $discountValue);
        $ppnInput = $math->round($validated['ppn'] ?? 0);
        if ($ppnType === 'percent') {
            $ppnPercent = $ppnInput;
            $ppnValue = $math->multiply($afterDiscount, $math->divide($ppnPercent, '100'));
        } else {
            $ppnValue = $ppnInput;
            $ppnPercent = $math->isPositive($afterDiscount)
                ? $math->multiply($math->divide($ppnValue, $afterDiscount), '100')
                : '0.000';
        }

        $grandTotal = ! empty($validated['manual_grand_total'])
            ? $math->round($validated['manual_grand_total'])
            : $math->add($afterDiscount, $ppnValue);

        DB::transaction(function () use ($validated, $items, $subtotal, $totalItems, $discountType, $discountPercent, $discountValue, $ppnType, $ppnPercent, $ppnValue, $grandTotal, $paymentMethod, $isPaid) {
            $purchase = ProductPurchase::create([
                'purchase_date' => $validated['purchase_date'],
                'total_items' => $totalItems,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_percent' => $discountPercent,
                'discount_value' => $discountValue,
                'ppn_type' => $ppnType,
                'ppn_percent' => $ppnPercent,
                'ppn_value' => $ppnValue,
                'grand_total' => $grandTotal,
                'payment_method' => $paymentMethod,
                'is_paid' => $isPaid,
            ]);

            // Simpan detail. Baris "langsung masuk stok" dicatat sebagai
            // penerimaan penuh lewat GoodsReceiptService, bukan createNewBatch
            // langsung, supaya jejak batch-nya sama persis dengan penerimaan
            // manual — dan karenanya ikut bisa dibatalkan saat nota dihapus.
            $items->each(function ($item) use ($purchase) {
                $detail = $purchase->details()->create($item);

                if ($detail->receipt_mode !== ProductPurchaseDetail::MODE_DIRECT) {
                    return;
                }

                $this->receiptService->receive($detail, [
                    'quantity' => $item['quantity'],
                    'received_date' => $purchase->purchase_date->toDateString(),
                    'expired_date' => $item['expired_date'],
                ]);
            });
        });

        return redirect()->route('purchase.index')->with('success', 'Pembelian produk berhasil disimpan & stok diperbarui.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ProductPurchase $purchase)
    {
        $purchase->load('details');
        $products = Product::select('id', 'code_id', 'name')->orderBy('code_id')->get();
        $categories = ItemCategory::orderBy('name', 'asc')->get();

        if ($request->ajax()) {
            return view('product-purchase.edit-partial', compact('purchase', 'products', 'categories'))->render();
        }

        return view('product-purchase.edit', compact('purchase', 'products', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductPurchase $purchase)
    {
        $math = app(DecimalMathService::class);
        $data = $this->prepareRequestData($request);

        try {
            $validated = Validator::make($data, [
                'purchase_date' => ['required', 'date'],
                'ppn' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
                'ppn_type' => ['required', 'in:percent,nominal'],
                'discount_type' => ['required', 'in:percent,nominal'],
                'discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
                'method' => ['required', 'integer', 'in:0,12'],
                'manual_grand_total' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
                'products' => ['required', 'array', 'min:1'],
                'products.*.product_id' => ['required', 'exists:products,id'],
                'products.*.het_price' => ['required', 'decimal:0,3'],
                'products.*.basic_discount' => ['nullable', 'decimal:0,3'],
                'products.*.additional_discount' => ['nullable', 'decimal:0,3'],
                'products.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001'],
                'products.*.unit' => ['required', 'string'],
                'products.*.expired_date' => ['nullable', 'date'],
                'products.*.direct_stock' => ['nullable', 'boolean'],
                'products.*.id' => ['nullable', 'integer'],
            ])->validate();

            $this->validateExpiredDates($purchase, $validated['products']);
            $this->validateReceiptConstraints($purchase, $validated['products']);
        } catch (ValidationException $e) {
            // Tandai bahwa validasi gagal berasal dari EDIT purchase ini, agar
            // halaman index membuka ulang modal edit (bukan modal create).
            session()->flash('edit_purchase_id', $purchase->id);

            throw $e;
        }

        $paymentMethod = (int) $validated['method'] === 0 ? 'cash' : 'credit';
        $isPaid = $paymentMethod === 'cash' ? true : $request->boolean('isPaid');
        $discountType = $validated['discount_type'];
        $ppnType = $validated['ppn_type'];

        $items = collect($validated['products'])->map(function ($item) use ($math) {
            $product = Product::findOrFail($item['product_id']);
            $het = $math->round($this->parseNumber($item['het_price']));
            $basicDisc = $math->round($this->parseNumber($item['basic_discount'] ?? '0'));
            $addDisc = $math->round($this->parseNumber($item['additional_discount'] ?? '0'));
            $qty = $math->round(str_replace(',', '.', (string) $item['quantity']));

            $netPrice = $math->subtract($math->subtract($het, $basicDisc), $addDisc);
            $subtotal = $math->multiply($netPrice, $qty);

            return [
                'product_id' => $product->id,
                'product_code' => $product->code_id,
                'product_name' => $product->name,
                'unit' => $item['unit'],
                'het_price' => $het,
                'basic_discount' => $basicDisc,
                'additional_discount' => $addDisc,
                'net_price' => $netPrice,
                'price' => $netPrice, // maintains compatibility
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'expired_date' => $item['expired_date'] ?? null,
                'receipt_mode' => $this->isDirectStock($item)
                    ? ProductPurchaseDetail::MODE_DIRECT
                    : ProductPurchaseDetail::MODE_PENDING,
                // Dipakai alur update untuk mencocokkan baris lama. Dibuang
                // sebelum persist — 'id' bukan kolom yang boleh diisi massal.
                'id' => $item['id'] ?? null,
            ];
        });

        $subtotal = '0.000';
        $totalItems = '0.000';
        foreach ($items as $item) {
            $subtotal = $math->add($subtotal, $item['subtotal']);
            $totalItems = $math->add($totalItems, $item['quantity']);
        }

        $discountInput = $math->round($validated['discount'] ?? 0);
        if ($discountType === 'percent') {
            $discountPercent = $discountInput;
            $discountValue = $math->multiply($subtotal, $math->divide($discountPercent, '100'));
        } else {
            $discountValue = $discountInput;
            $discountPercent = $math->isPositive($subtotal)
                ? $math->multiply($math->divide($discountValue, $subtotal), '100')
                : '0.000';
        }

        $afterDiscount = $math->subtract($subtotal, $discountValue);
        $ppnInput = $math->round($validated['ppn'] ?? 0);
        if ($ppnType === 'percent') {
            $ppnPercent = $ppnInput;
            $ppnValue = $math->multiply($afterDiscount, $math->divide($ppnPercent, '100'));
        } else {
            $ppnValue = $ppnInput;
            $ppnPercent = $math->isPositive($afterDiscount)
                ? $math->multiply($math->divide($ppnValue, $afterDiscount), '100')
                : '0.000';
        }

        $grandTotal = ! empty($validated['manual_grand_total'])
            ? $math->round($validated['manual_grand_total'])
            : $math->add($afterDiscount, $ppnValue);

        DB::transaction(function () use ($math, $purchase, $validated, $items, $subtotal, $totalItems, $discountType, $discountPercent, $discountValue, $ppnType, $ppnPercent, $ppnValue, $grandTotal, $paymentMethod, $isPaid) {
            $purchase->update([
                'purchase_date' => $validated['purchase_date'],
                'total_items' => $totalItems,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_percent' => $discountPercent,
                'discount_value' => $discountValue,
                'ppn_type' => $ppnType,
                'ppn_percent' => $ppnPercent,
                'ppn_value' => $ppnValue,
                'grand_total' => $grandTotal,
                'payment_method' => $paymentMethod,
                'is_paid' => $isPaid,
            ]);

            // Rekonsiliasi per id, BUKAN delete-recreate. Menghapus lalu
            // membuat ulang detail akan melenyapkan riwayat penerimaan lewat
            // cascade FK — dan dulu juga meninggalkan stok lama menggantung.
            $existing = $purchase->details()->get()->keyBy('id');
            $keptIds = [];

            foreach ($items as $item) {
                $submittedId = $item['id'] ?? null;
                $attributes = Arr::except($item, ['id']);

                $detail = $submittedId !== null && $submittedId !== ''
                    ? $existing->get((int) $submittedId)
                    : null;

                if ($detail === null) {
                    $created = $purchase->details()->create($attributes);
                    $keptIds[] = $created->id;

                    if ($created->receipt_mode === ProductPurchaseDetail::MODE_DIRECT) {
                        $this->receiptService->receive($created, [
                            'quantity' => $created->quantity,
                            'received_date' => $purchase->purchase_date->toDateString(),
                            'expired_date' => $created->expired_date?->toDateString(),
                        ]);
                    }

                    continue;
                }

                // receipt_mode adalah niat saat nota dibuat; edit nota tidak
                // mengubahnya. Menerima barang dilakukan lewat menu Penerimaan.
                unset($attributes['receipt_mode']);

                $priceChanged = $math->compare((string) $detail->net_price, (string) $attributes['net_price']) !== 0;

                $detail->update($attributes);
                $keptIds[] = $detail->id;

                // Harga beli batch ikut dikoreksi karena itu barang yang sama.
                // Transaksi kasir yang sudah tercatat sengaja tidak disentuh.
                if ($priceChanged) {
                    $this->receiptService->syncUnitPrice($detail);
                }
            }

            // Baris yang tidak dikirim lagi. validateReceiptConstraints sudah
            // memastikan tidak ada yang pernah diterima, jadi aman dihapus.
            $purchase->details()
                ->whereNotIn('id', $keptIds ?: [0])
                ->get()
                ->each(fn ($detail) => $detail->delete());
        });

        return redirect()->route('purchase.index')->with('success', 'Pembelian berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductPurchase $purchase)
    {
        $purchase->load('details.receipts');

        $blockers = $this->collectDeleteBlockers($purchase);

        if ($blockers !== []) {
            return back()->withErrors(['purchase' => $blockers]);
        }

        DB::transaction(function () use ($purchase) {
            // Tarik kembali seluruh stok yang lahir dari nota ini sebelum
            // notanya hilang. Tanpa ini, nota terhapus tapi barangnya tetap
            // tercatat ada di gudang (bug perilaku lama).
            foreach ($purchase->details as $detail) {
                foreach ($detail->receipts as $receipt) {
                    $this->receiptService->reverse($receipt);
                }
            }

            $purchase->delete();
        });

        return redirect()->route('purchase.index')->with('success', 'Pembelian dihapus dan stoknya ditarik kembali.');
    }

    /**
     * Dampak penghapusan nota terhadap stok, untuk modal konfirmasi.
     */
    public function deletePreview(ProductPurchase $purchase)
    {
        $purchase->load('details.receipts');

        $impact = [];

        foreach ($purchase->details as $detail) {
            foreach ($detail->receipts as $receipt) {
                $impact[] = [
                    'product_name' => $detail->product_name,
                    'quantity' => (string) $receipt->quantity,
                    'unit' => $detail->unit,
                ];
            }
        }

        $blockers = $this->collectDeleteBlockers($purchase);

        return response()->json([
            'can_delete' => $blockers === [],
            'impact' => $impact,
            'blockers' => $blockers,
        ]);
    }

    /**
     * Alasan-alasan kenapa nota ini tidak bisa dihapus.
     *
     * @return string[]
     */
    private function collectDeleteBlockers(ProductPurchase $purchase): array
    {
        $blockers = [];

        foreach ($purchase->details as $detail) {
            foreach ($detail->receipts as $receipt) {
                $blocker = $this->receiptService->blockerFor($receipt);

                if ($blocker !== null) {
                    $blockers[] = $detail->product_name.': '.$blocker;
                }
            }
        }

        return $blockers;
    }

    /**
     * Batasan edit nota yang muncul karena barang sudah terlanjur diterima.
     *
     * Dipanggil sebelum DB::transaction agar penolakan tidak menyisakan
     * perubahan setengah jalan.
     */
    private function validateReceiptConstraints(ProductPurchase $purchase, array $products): void
    {
        $math = app(DecimalMathService::class);
        $existing = $purchase->details()->get()->keyBy('id');
        $submitted = [];
        $errors = [];

        foreach ($products as $index => $item) {
            $submittedId = $item['id'] ?? null;

            if ($submittedId === null || $submittedId === '') {
                continue;
            }

            $detail = $existing->get((int) $submittedId);

            // Kepemilikan id sudah divalidasi validateExpiredDates.
            if ($detail === null) {
                continue;
            }

            $submitted[(int) $submittedId] = true;
            $received = (string) $detail->received_quantity;
            $quantity = $math->round(str_replace(',', '.', (string) $item['quantity']));

            if ($math->compare($quantity, $received) < 0) {
                $errors["products.$index.quantity"][] =
                    'Jumlah tidak boleh kurang dari yang sudah diterima ('.$this->trimDecimal($received).').';
            }

            if ((int) $item['product_id'] !== (int) $detail->product_id && $math->isPositive($received)) {
                $errors["products.$index.product_id"][] =
                    'Produk tidak bisa diganti karena barangnya sudah diterima. Batalkan penerimaannya dulu.';
            }
        }

        foreach ($existing as $id => $detail) {
            if (isset($submitted[$id])) {
                continue;
            }

            if ($math->isPositive((string) $detail->received_quantity)) {
                $errors['products'][] = $detail->product_name
                    .' sudah diterima sebagian dan tidak bisa dihapus. Batalkan penerimaannya dulu lewat menu Penerimaan Barang.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function trimDecimal(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.') ?: '0';
    }

    /**
     * Normalisasi input request sebelum validasi:
     * Konversi koma desimal (format Indonesia) ke titik agar lolos validasi 'numeric' Laravel.
     * Contoh: "1,5" → "1.5", "25.000,50" tidak berlaku (rupiah di-parse terpisah)
     */
    /**
     * Apakah baris item ini ditandai "langsung masuk stok"?
     *
     * Key yang tidak dikirim sama sekali diperlakukan sebagai true agar
     * klien lama (dan nota yang disimpan sebelum fitur ini ada) tetap
     * berperilaku seperti semula: simpan nota = stok bertambah.
     */
    private function isDirectStock(array $item): bool
    {
        if (! array_key_exists('direct_stock', $item) || $item['direct_stock'] === null) {
            return true;
        }

        return filter_var($item['direct_stock'], FILTER_VALIDATE_BOOLEAN);
    }

    private function prepareRequestData(Request $request): array
    {
        $data = $request->all();

        if (isset($data['products']) && is_array($data['products'])) {
            foreach ($data['products'] as $i => $item) {
                if (isset($item['quantity'])) {
                    $data['products'][$i]['quantity'] = str_replace(',', '.', (string) $item['quantity']);
                }
            }
        }

        foreach (['ppn', 'discount', 'manual_grand_total'] as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data[$field] = str_replace(',', '.', (string) $data[$field]);
            }
        }

        return $data;
    }

    /**
     * Validasi kondisional expired_date pada alur edit (Rev. 3 R9):
     * - ID detail yang dikirim wajib milik purchase saat ini (scoped, bukan find global);
     * - ID non-kosong tidak boleh diduplikasi;
     * - nilai expired_date yang tidak berubah boleh sudah lewat (historical);
     * - hanya nilai baru/berubah yang non-null wajib >= hari ini.
     * Dipanggil sebelum DB::transaction / update / delete, jadi tidak pernah
     * mengevaluasi detail yang sudah di-delete-recreate.
     */
    private function validateExpiredDates(ProductPurchase $purchase, array $products): void
    {
        $existingDetails = $purchase->details()->get()->keyBy('id');
        $today = Carbon::today()->toDateString();
        $errors = [];

        $rows = [];
        $seenIds = [];

        // 1) Kumpulkan id non-kosong, tolak duplikat, verifikasi kepemilikan (scoped).
        foreach ($products as $index => $item) {
            $submittedId = $item['id'] ?? null;

            if ($submittedId === null || $submittedId === '') {
                $rows[$index] = [
                    'id' => null,
                    'expiry' => $this->normalizeExpiry($item['expired_date'] ?? null),
                ];

                continue;
            }

            $id = (int) $submittedId;

            if (isset($seenIds[$id])) {
                $errors["products.$index.id"][] = 'Detail pembelian tidak boleh diduplikasi.';

                continue;
            }
            $seenIds[$id] = true;

            $detail = $existingDetails->get($id);

            if ($detail === null) {
                $errors["products.$index.id"][] = 'Detail pembelian tidak valid.';

                continue;
            }

            $rows[$index] = [
                'id' => $id,
                'detail' => $detail,
                'expiry' => $this->normalizeExpiry($item['expired_date'] ?? null),
            ];
        }

        // 2) Validasi expiry: baris baru / nilai berubah yang non-null wajib >= hari ini.
        foreach ($rows as $index => $row) {
            if ($row['id'] === null) {
                if ($row['expiry'] !== null && $row['expiry'] < $today) {
                    $errors["products.$index.expired_date"][] = 'Tanggal kadaluarsa tidak boleh sebelum hari ini.';
                }

                continue;
            }

            $storedExpiry = $row['detail']->expired_date?->toDateString();
            $changed = $row['expiry'] !== $storedExpiry;

            if ($changed && $row['expiry'] !== null && $row['expiry'] < $today) {
                $errors["products.$index.expired_date"][] = 'Tanggal kadaluarsa tidak boleh sebelum hari ini.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Normalisasi nilai expired_date ke representasi Y-m-d.
     * String kosong dan null diperlakukan sama (tidak ada expiry).
     */
    private function normalizeExpiry($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    private function parseNumber(string $value): float
    {
        $value = trim($value);

        if (str_contains($value, ',')) {
            // Format tampilan Indonesia: "25.000,50" — titik = ribuan, koma = desimal
            $clean = str_replace('.', '', $value);   // hapus pemisah ribuan
            $clean = str_replace(',', '.', $clean);  // koma desimal → titik
        } else {
            // Format raw dari hidden input: "25000.50" — titik = desimal
            // Bersihkan karakter selain angka dan titik
            $clean = preg_replace('/[^0-9.]/', '', $value);
            // Pastikan hanya ada satu titik desimal
            $parts = explode('.', $clean);
            if (count($parts) > 2) {
                // Jika ada lebih dari satu titik, satukan bagian belakangnya
                $clean = $parts[0].'.'.implode('', array_slice($parts, 1));
            }
        }

        return (float) $clean;
    }
}
