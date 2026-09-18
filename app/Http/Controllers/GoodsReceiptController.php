<?php

namespace App\Http\Controllers;

use App\Models\ProductPurchaseDetail;
use App\Models\ProductPurchaseReceipt;
use App\Services\GoodsReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Penerimaan barang untuk baris nota pembelian yang barangnya belum datang.
 *
 * Controller sengaja tipis: seluruh aturan (rem kelebihan terima, syarat
 * pembatalan, tutup sisa) ada di GoodsReceiptService.
 */
class GoodsReceiptController extends Controller
{
    public function __construct(private GoodsReceiptService $service) {}

    /**
     * Daftar baris yang masih menunggu barang, lintas nota.
     */
    public function index(Request $request)
    {
        $details = $this->service
            ->outstandingQuery($request->input('search'))
            ->orderBy('product_purchase_id')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('goods-receipt.index', [
            'details' => $details,
            'search' => $request->input('search'),
            'outstandingCount' => $this->service->outstandingCount(),
        ]);
    }

    /**
     * Catat kedatangan sebagian/seluruh sisa untuk satu baris.
     */
    public function store(Request $request, ProductPurchaseDetail $detail)
    {
        $validated = Validator::make($this->normalize($request), [
            'quantity' => ['required', 'numeric', 'decimal:0,3'],
            'received_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expired_date' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $this->service->receive($detail, [
            'quantity' => $validated['quantity'],
            'received_date' => $validated['received_date'] ?? now()->toDateString(),
            'expired_date' => $validated['expired_date'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        $detail->refresh();

        $message = $detail->is_fully_received
            ? $detail->product_name.' sudah diterima lengkap.'
            : $detail->product_name.' diterima sebagian. Sisa '.$this->trim($detail->outstanding_quantity).' '.$detail->unit.'.';

        return back()->with('success', $message);
    }

    /**
     * Nyatakan sisa tidak akan datang lagi.
     */
    public function close(Request $request, ProductPurchaseDetail $detail)
    {
        $validated = Validator::make($request->all(), [
            'reason' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $this->service->closeRemaining($detail, $validated['reason'] ?? null);

        return back()->with('success', 'Sisa '.$detail->product_name.' ditutup.');
    }

    /**
     * Batalkan satu penerimaan dan tarik kembali stoknya.
     */
    public function destroy(ProductPurchaseReceipt $receipt)
    {
        $name = $receipt->detail?->product_name ?? 'Barang';

        $this->service->reverse($receipt);

        return back()->with('success', 'Penerimaan '.$name.' dibatalkan dan stoknya ditarik kembali.');
    }

    /**
     * Terima koma desimal ala Indonesia ("1,5") seperti form pembelian.
     */
    private function normalize(Request $request): array
    {
        $data = $request->all();

        if (isset($data['quantity'])) {
            $data['quantity'] = str_replace(',', '.', (string) $data['quantity']);
        }

        return $data;
    }

    private function trim(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.') ?: '0';
    }
}
