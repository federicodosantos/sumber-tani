<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Product;
use App\Models\ProductPurchase;
use App\Services\DecimalMathService;
use App\Services\ProductStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductPurchaseController extends Controller
{
    protected ProductStockService $stockService;

    public function __construct(ProductStockService $stockService)
    {
        $this->stockService = $stockService;
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

        $purchases = $query->paginate(10)->withQueryString();
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

            // Simpan detail + buat batch stok baru per item
            $items->each(function ($item) use ($purchase) {
                $purchase->details()->create($item);
                $latestPrices = $this->stockService->getLatestBatchPrices($item['product_id']);

                $this->stockService->createNewBatch($item['product_id'], [
                    'stock_opname' => $item['quantity'],
                    'unit_price' => $item['net_price'],
                    'price_consument' => $latestPrices['price_consument'],
                    'price_r1' => $latestPrices['price_r1'],
                    'price_r2' => $latestPrices['price_r2'],
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
                'products.*.id' => ['nullable', 'integer'],
            ])->validate();

            $this->validateExpiredDates($purchase, $validated['products']);
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

        DB::transaction(function () use ($purchase, $validated, $items, $subtotal, $totalItems, $discountType, $discountPercent, $discountValue, $ppnType, $ppnPercent, $ppnValue, $grandTotal, $paymentMethod, $isPaid) {
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

            // Reset details (note: tidak rollback stok lama)
            $purchase->details()->delete();
            $items->each(fn ($item) => $purchase->details()->create($item));
        });

        return redirect()->route('purchase.index')->with('success', 'Pembelian berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductPurchase $purchase)
    {
        ProductPurchase::destroy($purchase->id);

        return redirect()->route('purchase.index')->with('success', 'Pembelian berhasil dihapus.');
    }

    /**
     * Normalisasi input request sebelum validasi:
     * Konversi koma desimal (format Indonesia) ke titik agar lolos validasi 'numeric' Laravel.
     * Contoh: "1,5" → "1.5", "25.000,50" tidak berlaku (rupiah di-parse terpisah)
     */
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
