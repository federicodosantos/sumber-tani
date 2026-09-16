@php use Illuminate\Support\Number; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Lengkapi Harga Beli (HPP)
        </h2>
    </x-slot>

    <div class="py-12 font-mont">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Progres --}}
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-sm font-bold text-gray-900">Sisa yang belum punya harga beli (HPP)</p>
                <p class="mt-1 text-sm text-gray-700" id="bulk-progress">
                    <span id="stat-batch" class="font-bold">{{ number_format($stats['batch_count'], 0, ',', '.') }}</span> batch
                    (<span id="stat-qty" class="font-bold">{{ Number::format((float) $stats['stock_qty'], null, 3, 'id') }}</span> satuan) berstok
                    + <span id="stat-empty" class="font-bold">{{ number_format($stats['empty_count'], 0, ',', '.') }}</span> batch kosong.
                </p>
                <p class="mt-2 text-xs leading-relaxed text-gray-600">
                    Harga acuan di bawah ini hanyalah saran dari pembelian terakhir dan masih bisa diubah.
                    Pastikan memeriksa sebelum menyimpan, karena data yang disimpan dianggap telah dikonfirmasi.
                </p>
            </div>

            {{-- Filter: tab + search --}}
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex gap-2">
                    <a href="{{ route('stock.bulk.edit', ['tab' => 'in_stock', 'search' => $search]) }}"
                        class="rounded-lg px-4 py-2 text-sm font-bold {{ $tab === 'in_stock' ? 'bg-button-main text-white' : 'bg-white text-gray-700 border border-gray-300' }}">
                        Berstok
                    </a>
                    <a href="{{ route('stock.bulk.edit', ['tab' => 'empty', 'search' => $search]) }}"
                        class="rounded-lg px-4 py-2 text-sm font-bold {{ $tab === 'empty' ? 'bg-button-main text-white' : 'bg-white text-gray-700 border border-gray-300' }}">
                        Stok kosong
                    </a>
                </div>
                <form method="GET" action="{{ route('stock.bulk.edit') }}" class="flex gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama/kode produk…"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm sm:w-64">
                    <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-bold text-white">Cari</button>
                </form>
            </div>

            {{-- Daftar batch, dikelompokkan per produk --}}
            @forelse ($batches->getCollection()->groupBy('product_id') as $productId => $rows)
                @php $saran = $suggestions[$productId] ?? null; @endphp
                <div class="mb-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" data-product-group="{{ $productId }}">
                    <div class="flex flex-col gap-2 border-b border-gray-100 bg-gray-50/50 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-900">
                                {{ $rows->first()->product->name ?? 'Produk #'.$productId }}
                                <span class="ml-1 font-normal text-gray-500">{{ $rows->first()->product->code_id ?? '' }}</span>
                            </p>
                            @if ($saran)
                                <p class="mt-0.5 text-xs text-gray-600">
                                    Acuan: <span class="font-bold">Rp {{ Number::format((float) $saran['price'], null, 3, 'id') }}</span>
                                    — beli {{ $saran['date'] ? \Carbon\Carbon::parse($saran['date'])->locale('id')->translatedFormat('d F Y') : '-' }}
                                </p>
                            @else
                                <p class="mt-0.5 text-xs font-medium text-gray-500">Tanpa acuan — isi manual (nota supplier/catatan).</p>
                            @endif
                        </div>
                        @if ($saran)
                            <button type="button" data-apply-group="{{ $productId }}" data-price="{{ $saran['price'] }}"
                                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-bold text-gray-800 hover:bg-gray-100">
                                Samakan semua batch dengan harga baris pertama
                            </button>
                        @endif
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($rows as $batch)
                            <form method="POST" action="{{ route('stock.bulk.update', $batch->id) }}"
                                data-row-form="{{ $batch->id }}" data-product-id="{{ $productId }}"
                                class="flex flex-col gap-3 px-5 py-3 sm:flex-row sm:items-end">
                                @csrf
                                @method('PUT')
                                <div class="flex-1">
                                    <p class="text-xs font-bold text-gray-500">BATCH {{ $batch->batch }} · Stok {{ Number::format((float) $batch->stock_opname, null, 3, 'id') }}</p>
                                    <div class="mt-1 max-w-xs">
                                        <x-input-rupiah label="" name="unit_price_{{ $batch->id }}"
                                            :value="$saran['price'] ?? ''" placeholder="0" decimals="3" />
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span data-row-status="{{ $batch->id }}"
                                        class="rounded-full px-2.5 py-1 text-xs font-medium {{ $saran ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $saran ? 'Saran sistem' : 'Isi manual' }}
                                    </span>
                                    <button type="submit" data-row-save="{{ $batch->id }}"
                                        class="rounded-lg bg-button-main px-4 py-2 text-xs font-bold text-white hover:bg-button-hover">
                                        Simpan
                                    </button>
                                </div>
                            </form>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-600 shadow-sm">
                    @if ($tab === 'empty')
                        Tidak ada batch kosong tanpa HPP. Semua beres untuk tab ini.
                    @else
                        Semua batch berstok sudah punya harga beli. Nilai persediaan di neraca sudah lengkap.
                    @endif
                </div>
            @endforelse

            <div class="mt-4">
                {{ $batches->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            function setRowStatus(stockId, kind, text) {
                const el = document.querySelector(`[data-row-status="${stockId}"]`);
                if (!el) return;
                const styles = {
                    suggest: 'rounded-full px-2.5 py-1 text-xs font-medium bg-yellow-100 text-yellow-800',
                    manual: 'rounded-full px-2.5 py-1 text-xs font-medium bg-gray-200 text-gray-700',
                    saved: 'rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800',
                    error: 'rounded-full px-2.5 py-1 text-xs font-medium bg-red-100 text-red-700',
                    saving: 'rounded-full px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-700',
                };
                el.className = styles[kind] ?? styles.manual;
                el.textContent = text;
            }

            function rowRawValue(stockId) {
                const form = document.querySelector(`[data-row-form="${stockId}"]`);
                // Samakan dengan hidden input milik x-input-rupiah (bukan _token/_method).
                const hidden = form?.querySelector('input[type="hidden"][name^="unit_price"]');
                return hidden ? hidden.value : '';
            }

            async function saveRow(stockId) {
                const form = document.querySelector(`[data-row-form="${stockId}"]`);
                if (!form) return;
                const value = rowRawValue(stockId);
                const num = parseFloat(value);

                if (!value || isNaN(num) || num <= 0) {
                    setRowStatus(stockId, 'error', 'HPP harus > 0');
                    return;
                }

                const btn = document.querySelector(`[data-row-save="${stockId}"]`);
                if (btn) btn.disabled = true;
                setRowStatus(stockId, 'saving', 'Menyimpan…');

                try {
                    const res = await fetch(form.action, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ unit_price: value }),
                    });
                    const data = await res.json();

                    if (!res.ok || !data.success) {
                        const msg = data.message ?? (data.errors?.unit_price?.[0] ?? 'Gagal menyimpan');
                        setRowStatus(stockId, 'error', msg);
                        return;
                    }

                    setRowStatus(stockId, 'saved', 'Terkonfirmasi');
                    if (data.stats) {
                        const b = document.getElementById('stat-batch');
                        const q = document.getElementById('stat-qty');
                        const e = document.getElementById('stat-empty');
                        if (b) b.textContent = Number(data.stats.batch_count).toLocaleString('id-ID');
                        if (q) q.textContent = Number(data.stats.stock_qty).toLocaleString('id-ID', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
                        if (e) e.textContent = Number(data.stats.empty_count).toLocaleString('id-ID');
                    }
                } catch (err) {
                    setRowStatus(stockId, 'error', 'Jaringan gagal');
                } finally {
                    if (btn) btn.disabled = false;
                }
            }

            // Intercept submit per-baris → AJAX (fallback: submit biasa bila JS mati).
            document.querySelectorAll('[data-row-form]').forEach((form) => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const stockId = form.getAttribute('data-row-form');
                    saveRow(stockId);
                });
            });

            // "Samakan semua batch dengan harga baris pertama" → sebarkan nilai live
            // baris pertama grup ke semua baris (tanpa menyimpan otomatis; Simpan per baris tetap manual).
            // Fallback ke harga saran bila baris pertama masih kosong.
            document.querySelectorAll('[data-apply-group]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const productId = btn.getAttribute('data-apply-group');
                    const forms = [...document.querySelectorAll(`[data-product-id="${productId}"]`)];
                    if (!forms.length) return;

                    const firstId = forms[0].getAttribute('data-row-form');
                    let price = rowRawValue(firstId);
                    if (!price || isNaN(parseFloat(price)) || parseFloat(price) <= 0) {
                        price = btn.getAttribute('data-price') ?? '';
                    }
                    if (!price) return;

                    forms.forEach((form) => {
                        const hidden = form.querySelector('input[type="hidden"][name^="unit_price"]');
                        if (hidden && hidden.name) {
                            window.dispatchEvent(new CustomEvent('update-rupiah-value', {
                                detail: { name: hidden.name, value: price },
                            }));
                        }
                        const display = form.querySelector('input[type="text"]');
                        if (display) {
                            display.classList.add('ring-2', 'ring-green-400');
                            setTimeout(() => display.classList.remove('ring-2', 'ring-green-400'), 1200);
                        }
                    });

                    const original = btn.textContent;
                    btn.textContent = 'Diterapkan ✓';
                    setTimeout(() => { btn.textContent = original; }, 1500);
                });
            });
        </script>
    @endpush
</x-app-layout>
