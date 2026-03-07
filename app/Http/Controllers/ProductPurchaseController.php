<?php

namespace App\Http\Controllers;

use App\Models\ProductPurchase;
use App\Models\Product;
use App\Services\ProductStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
                $query->orderBy('purchase_date', 'desc');
                break;
        }

        $purchases = $query->paginate(10)->withQueryString();

        return view('product-purchase.index', compact('purchases'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::select('id', 'code_id', 'name')->orderBy('code_id')->get();

        return view('product-purchase.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'purchase_date'      => ['required', 'date'],
            'ppn'                => ['nullable', 'numeric', 'min:0'],
            'ppn_type'           => ['required', 'in:percent,nominal'],
            'discount_type'      => ['required', 'in:percent,nominal'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'method'             => ['required', 'integer', 'in:0,12'],
            'manual_grand_total' => ['nullable', 'numeric', 'min:0'],
            'products'           => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.price'      => ['required'],
            'products.*.quantity'    => ['required', 'integer', 'min:1'],
            'products.*.unit'        => ['required', 'string', 'max:50'],
        ]);

        $paymentMethod = (int) $validated['method'] === 0 ? 'cash' : 'credit';
        $isPaid = $paymentMethod === 'cash' ? true : $request->boolean('isPaid');
        $discountType = $validated['discount_type'];
        $ppnType = $validated['ppn_type'];

        $items = collect($validated['products'])->map(function ($item) {
            $product = Product::findOrFail($item['product_id']);
            $price = $this->parseNumber($item['price']);
            $qty = (int) $item['quantity'];

            return [
                'product_id'   => $product->id,
                'product_code' => $product->code_id,
                'product_name' => $product->name,
                'unit'         => $item['unit'],
                'price'        => $price,
                'quantity'     => $qty,
                'subtotal'     => $price * $qty,
            ];
        });

        $subtotal = $items->sum('subtotal');

        // Hitung diskon berdasarkan tipe
        $discountInput = (float) ($validated['discount'] ?? 0);
        if ($discountType === 'percent') {
            $discountPercent = $discountInput;
            $discountValue = $subtotal * ($discountPercent / 100);
        } else {
            $discountValue = $discountInput;
            $discountPercent = $subtotal > 0 ? ($discountValue / $subtotal) * 100 : 0;
        }

        $afterDiscount = $subtotal - $discountValue;
        $ppnInput = (float) ($validated['ppn'] ?? 0);
        if ($ppnType === 'percent') {
            $ppnPercent = $ppnInput;
            $ppnValue = $afterDiscount * ($ppnPercent / 100);
        } else {
            $ppnValue = $ppnInput;
            $ppnPercent = $afterDiscount > 0 ? ($ppnValue / $afterDiscount) * 100 : 0;
        }

        $grandTotal = !empty($validated['manual_grand_total'])
            ? (float) $validated['manual_grand_total']
            : $afterDiscount + $ppnValue;

        DB::transaction(function () use ($validated, $items, $subtotal, $discountType, $discountPercent, $discountValue, $ppnType, $ppnPercent, $ppnValue, $grandTotal, $paymentMethod, $isPaid) {
            $purchase = ProductPurchase::create([
                'purchase_date'    => $validated['purchase_date'],
                'total_items'      => $items->sum('quantity'),
                'subtotal'         => $subtotal,
                'discount_type'    => $discountType,
                'discount_percent' => $discountPercent,
                'discount_value'   => $discountValue,
                'ppn_type'         => $ppnType,
                'ppn_percent'      => $ppnPercent,
                'ppn_value'        => $ppnValue,
                'grand_total'      => $grandTotal,
                'payment_method'   => $paymentMethod,
                'is_paid'          => $isPaid,
            ]);

            // Simpan detail + buat batch stok baru per item
            $items->each(function ($item) use ($purchase) {
                $purchase->details()->create($item);
                $latestPrices = $this->stockService->getLatestBatchPrices($item['product_id']);

                $this->stockService->createNewBatch($item['product_id'], [
                    'stock_opname'    => $item['quantity'],
                    'price_consument' => $latestPrices['price_consument'],
                    'price_r1'        => $latestPrices['price_r1'],
                    'price_r2'        => $latestPrices['price_r2'],
                    'expired_date'    => null,
                ]);
            });
        });

        return redirect()->route('purchase.index')->with('success', 'Pembelian produk berhasil disimpan & stok diperbarui.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductPurchase $purchase)
    {
        $purchase->load('details');
        $products = Product::select('id', 'code_id', 'name')->orderBy('code_id')->get();

        return view('product-purchase.edit', compact('purchase', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductPurchase $purchase)
    {
        $validated = $request->validate([
            'purchase_date'      => ['required', 'date'],
            'ppn'                => ['nullable', 'numeric', 'min:0'],
            'ppn_type'           => ['required', 'in:percent,nominal'],
            'discount_type'      => ['required', 'in:percent,nominal'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'method'             => ['required', 'integer', 'in:0,12'],
            'manual_grand_total' => ['nullable', 'numeric', 'min:0'],
            'products'           => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.price'      => ['required'],
            'products.*.quantity'    => ['required', 'integer', 'min:1'],
            'products.*.unit'        => ['required', 'string'],
        ]);

        $paymentMethod = (int) $validated['method'] === 0 ? 'cash' : 'credit';
        $isPaid = $paymentMethod === 'cash' ? true : $request->boolean('isPaid');
        $discountType = $validated['discount_type'];
        $ppnType = $validated['ppn_type'];

        $items = collect($validated['products'])->map(function ($item) {
            $product = Product::findOrFail($item['product_id']);
            $price = $this->parseNumber($item['price']);
            $qty = (int) $item['quantity'];

            return [
                'product_id'   => $product->id,
                'product_code' => $product->code_id,
                'product_name' => $product->name,
                'unit'         => $item['unit'],
                'price'        => $price,
                'quantity'     => $qty,
                'subtotal'     => $price * $qty,
            ];
        });

        $subtotal = $items->sum('subtotal');

        $discountInput = (float) ($validated['discount'] ?? 0);
        if ($discountType === 'percent') {
            $discountPercent = $discountInput;
            $discountValue = $subtotal * ($discountPercent / 100);
        } else {
            $discountValue = $discountInput;
            $discountPercent = $subtotal > 0 ? ($discountValue / $subtotal) * 100 : 0;
        }

        $afterDiscount = $subtotal - $discountValue;
        $ppnInput = (float) ($validated['ppn'] ?? 0);
        if ($ppnType === 'percent') {
            $ppnPercent = $ppnInput;
            $ppnValue = $afterDiscount * ($ppnPercent / 100);
        } else {
            $ppnValue = $ppnInput;
            $ppnPercent = $afterDiscount > 0 ? ($ppnValue / $afterDiscount) * 100 : 0;
        }

        $grandTotal = !empty($validated['manual_grand_total'])
            ? (float) $validated['manual_grand_total']
            : $afterDiscount + $ppnValue;

        DB::transaction(function () use ($purchase, $validated, $items, $subtotal, $discountType, $discountPercent, $discountValue, $ppnType, $ppnPercent, $ppnValue, $grandTotal, $paymentMethod, $isPaid) {
            $purchase->update([
                'purchase_date'    => $validated['purchase_date'],
                'total_items'      => $items->sum('quantity'),
                'subtotal'         => $subtotal,
                'discount_type'    => $discountType,
                'discount_percent' => $discountPercent,
                'discount_value'   => $discountValue,
                'ppn_type'         => $ppnType,
                'ppn_percent'      => $ppnPercent,
                'ppn_value'        => $ppnValue,
                'grand_total'      => $grandTotal,
                'payment_method'   => $paymentMethod,
                'is_paid'          => $isPaid,
            ]);

            // Reset details (note: tidak rollback stok lama)
            $purchase->details()->delete();
            $items->each(fn($item) => $purchase->details()->create($item));
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

    private function parseNumber(string $value): float
    {
        $clean = preg_replace('/[^0-9,.,-]/', '', $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }
}
