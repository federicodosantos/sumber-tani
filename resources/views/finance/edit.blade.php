<x-app-layout>
  <x-slot name="header">
    <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Transaksi</h2>
  </x-slot>

  <script>
    window.__financeEditConfig = {
      products: @json($products),
      initial: {
        items: @json($initialItems),
        discount: {{ (float) $transaction->discount }},
        payment_method: @json($transaction->payment_method),
        is_paid: {{ $transaction->is_paid ? 'true' : 'false' }},
        cash_received: {{ $transaction->cash_received !== null ? (float) $transaction->cash_received : 'null' }},
        transaction_date: @json(optional($transaction->transaction_date)->format('Y-m-d\\TH:i'))
      }
    };
  </script>

  <div x-data="financeEditHandler(window.__financeEditConfig)">

    {{-- Header strip --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Transaksi</p>
        <h1 class="mt-1 font-display text-3xl font-extrabold tracking-tight text-gray-900">
          #{{ $transaction->id }}
          <span class="ml-2 align-middle rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Mode Edit</span>
        </h1>
        <p class="mt-1 text-sm text-gray-500">
          Dibuat {{ $transaction->created_at->translatedFormat('d F Y, H:i') }}
          <span class="mx-2 text-gray-300">•</span>
          Total awal <span class="font-semibold text-gray-700">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span>
        </p>
      </div>
      <a href="{{ route('finance.index') }}"
         class="self-start inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 hover:text-gray-800 transition">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
        Kembali ke Laporan
      </a>
    </div>

    @if (session('error'))
      <div class="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm-1-7v3a1 1 0 1 0 2 0v-3a1 1 0 1 0-2 0Zm1-6a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('error') }}</span>
      </div>
    @endif

    {{-- Warning banner --}}
    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200/70 bg-gradient-to-r from-amber-50 to-yellow-50 px-5 py-4">
      <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126Z" />
        </svg>
      </div>
      <div class="text-sm">
        <p class="font-semibold text-amber-900">Aksi ini akan menyesuaikan stok &amp; piutang.</p>
        <p class="mt-0.5 text-amber-800/80">
          Stok lama dikembalikan ke gudang, item baru mengurangi stok terbaru (FIFO),
          dan invoice/piutang yang terkait akan dihitung ulang.
        </p>
      </div>
    </div>

    <form method="POST" action="{{ route('finance.update', $transaction->id) }}"
          x-on:submit.prevent="submitForm($event)"
          class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
      @csrf
      @method('PUT')

      {{-- LEFT COLUMN --}}
      <div class="space-y-6">

        {{-- Items Card --}}
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
          <header class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <div>
              <h3 class="font-display text-base font-bold text-gray-900">Daftar Item</h3>
              <p class="text-xs text-gray-500">Pilih produk, atur harga &amp; jumlah.</p>
            </div>
            <button type="button" @click="addRow()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-button-main/15 px-3 py-1.5 text-xs font-semibold text-green-800 hover:bg-button-main/25 transition">
              <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              Tambah Item
            </button>
          </header>

          {{-- Empty state --}}
          <template x-if="items.length === 0">
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
              <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-50 ring-1 ring-gray-100">
                <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
              </div>
              <p class="mt-4 font-display text-lg font-semibold text-gray-900">Belum ada item</p>
              <p class="mt-1 max-w-xs text-sm text-gray-500">Mulai dengan menambahkan minimal satu produk untuk transaksi ini.</p>
              <button type="button" @click="addRow()"
                      class="mt-5 inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-button-hover transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Item Pertama
              </button>
            </div>
          </template>

          {{-- Item rows --}}
          <ul x-show="items.length > 0" class="divide-y divide-gray-100">
            <template x-for="(row, idx) in items" :key="idx">
              <li class="px-6 py-4 transition hover:bg-gray-50/40">
                <div class="grid grid-cols-12 items-start gap-3">
                  {{-- Product picker --}}
                  <div class="col-span-12 md:col-span-5"
                       x-data="{ open: false, search: '' }"
                       @click.outside="open = false">
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-gray-400">Produk</label>
                    <button type="button" @click="open = !open; if(open) $nextTick(() => $refs.search.focus())"
                            class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm hover:border-gray-400 transition focus:border-green-500 focus:ring-1 focus:ring-green-500">
                      <span x-show="row.id" class="truncate font-medium text-gray-900" x-text="productLabel(row)"></span>
                      <span x-show="!row.id" class="text-gray-400">Pilih produk…</span>
                      <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                      </svg>
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" x-transition.opacity
                         class="relative" style="display: none;">
                      <div class="absolute left-0 right-0 z-30 mt-1 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                        <div class="border-b border-gray-100 p-2">
                          <input x-ref="search" type="text" x-model="search" placeholder="Cari produk…"
                                 class="w-full rounded-md border border-gray-200 px-3 py-1.5 text-sm focus:border-green-500 focus:ring-1 focus:ring-green-500" />
                        </div>
                        <ul class="max-h-64 overflow-y-auto py-1">
                          <template x-for="p in filteredProducts(search)" :key="p.id">
                            <li>
                              <button type="button" @click="pickProduct(idx, p); open = false; search = ''"
                                      class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-button-main/10 transition">
                                <span class="font-medium text-gray-800" x-text="p.name"></span>
                                <span class="ml-2 text-xs tabular-nums text-gray-400">stok <span x-text="p.stock"></span></span>
                              </button>
                            </li>
                          </template>
                          <template x-if="filteredProducts(search).length === 0">
                            <li class="px-3 py-3 text-center text-xs text-gray-400">Tidak ada produk</li>
                          </template>
                        </ul>
                      </div>
                    </div>
                  </div>

                  {{-- Price --}}
                  <div class="col-span-6 md:col-span-3">
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-gray-400">Harga</label>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Rp</span>
                      <input type="number" x-model.number="row.price" min="0" step="any"
                             class="w-full rounded-lg border-gray-300 pl-9 pr-2 py-2 text-right text-sm tabular-nums focus:border-green-500 focus:ring-green-500" />
                    </div>
                  </div>

                  {{-- Qty stepper --}}
                  <div class="col-span-4 md:col-span-2">
                    <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-gray-400">Qty</label>
                    <div class="flex items-center rounded-lg border border-gray-300 bg-white">
                      <button type="button" @click="decQty(idx)"
                              class="flex h-9 w-9 items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition">−</button>
                      <input type="text"
                             :value="formatQty(row.qty)"
                             @input="setQty(row, $event.target.value)"
                             @blur="handleQtyBlur(row, $event)"
                             @keydown="const k=$event.key; const nav=['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End','Enter']; const ok=/[0-9]/.test(k)||nav.includes(k)||$event.ctrlKey||$event.metaKey||(k===','&&!$el.value.includes(',')); if(!ok) $event.preventDefault();"
                             class="w-full border-0 bg-transparent text-center text-sm font-semibold tabular-nums focus:ring-0 p-0" />
                      <button type="button" @click="incQty(idx)"
                              class="flex h-9 w-9 items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition">+</button>
                    </div>
                  </div>

                  {{-- Subtotal + remove --}}
                  <div class="col-span-2 md:col-span-2 flex items-end justify-between gap-1">
                    <div class="min-w-0 flex-1">
                      <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-gray-400 text-right">Subtotal</label>
                      <p class="truncate text-right text-sm font-bold tabular-nums text-gray-900"
                         x-text="formatRp((row.price||0)*(row.qty||0))"></p>
                    </div>
                    <button type="button" @click="removeRow(idx)"
                            class="mb-0.5 flex h-9 w-9 items-center justify-center rounded-lg text-gray-300 hover:bg-red-50 hover:text-red-600 transition"
                            title="Hapus baris">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
              </li>
            </template>
          </ul>
        </section>

        {{-- Meta Card --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <header class="mb-5">
            <h3 class="font-display text-base font-bold text-gray-900">Detail Pembayaran</h3>
            <p class="text-xs text-gray-500">Sesuaikan metode, status, dan tanggal transaksi.</p>
          </header>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            {{-- Payment Method --}}
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-gray-700">Metode Pembayaran</label>
              <div class="grid grid-cols-2 gap-2">
                <template x-for="method in ['Cash','QRIS','Transfer','Kredit']" :key="method">
                  <button type="button" @click="payment_method = method"
                          :class="payment_method === method
                            ? 'border-button-main bg-button-main/10 text-green-800 ring-1 ring-button-main'
                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'"
                          class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
                          x-text="method"></button>
                </template>
              </div>
            </div>

            {{-- Date --}}
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-gray-700">Tanggal Transaksi</label>
              <input type="datetime-local" x-model="transaction_date"
                     class="w-full rounded-lg border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:ring-green-500" />
            </div>

            {{-- Discount --}}
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-gray-700">Diskon</label>
              <div class="relative">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Rp</span>
                <input type="number" x-model.number="discount" min="0" step="any"
                       class="w-full rounded-lg border-gray-300 pl-9 py-2 text-sm tabular-nums focus:border-green-500 focus:ring-green-500" />
              </div>
            </div>

            {{-- Paid status --}}
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-gray-700">Status</label>
              <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 cursor-pointer hover:border-gray-300 transition">
                <input type="checkbox" x-model="is_paid" class="h-4 w-4 rounded border-gray-300 text-button-main focus:ring-button-main" />
                <span class="text-sm font-medium text-gray-800">Sudah lunas</span>
                <span x-show="is_paid" class="ml-auto rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">paid</span>
              </label>
            </div>

            {{-- Cash received (conditional) --}}
            <div x-show="payment_method === 'Cash' && is_paid" class="md:col-span-2">
              <label class="mb-1.5 block text-xs font-semibold text-gray-700">Uang Diterima</label>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="relative">
                  <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Rp</span>
                  <input type="number" x-model.number="cash_received" min="0" step="any"
                         class="w-full rounded-lg border-gray-300 pl-9 py-2 text-sm tabular-nums focus:border-green-500 focus:ring-green-500" />
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-2 ring-1 ring-gray-100">
                  <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Kembalian</p>
                  <p class="text-sm font-bold tabular-nums text-gray-900" x-text="formatRp(changeAmount)"></p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      {{-- RIGHT COLUMN: Sticky Summary --}}
      <aside class="lg:sticky lg:top-6 lg:self-start space-y-4">
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
          <header class="border-b border-gray-100 bg-gradient-to-br from-button-main/15 to-transparent px-5 py-4">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-green-800/70">Ringkasan</p>
            <p class="font-display text-base font-bold text-gray-900">Total Transaksi</p>
          </header>

          <div class="px-5 py-5">
            <div class="space-y-2.5 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-gray-500">Total Item</span>
                <span class="tabular-nums font-medium text-gray-900" x-text="formatQty(totalQty) + ' pcs'"></span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-500">Subtotal</span>
                <span class="tabular-nums font-medium text-gray-900" x-text="formatRp(subtotal)"></span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-500">Diskon</span>
                <span class="tabular-nums font-medium text-red-600"
                      x-text="(Number(discount)||0) > 0 ? '−' + formatRp(discount) : formatRp(0)"></span>
              </div>
            </div>

            <div class="my-4 border-t border-dashed border-gray-200"></div>

            <div class="flex items-end justify-between">
              <span class="font-display text-sm font-bold uppercase tracking-wider text-gray-700">Total</span>
              <span class="font-display text-2xl font-extrabold tabular-nums text-gray-900"
                    x-text="formatRp(totalAmount)"></span>
            </div>

            <div class="mt-5 flex flex-col gap-2">
              <button type="submit" :disabled="submitting"
                      class="inline-flex items-center justify-center gap-2 rounded-xl bg-button-main px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-button-hover transition disabled:cursor-not-allowed disabled:opacity-60">
                <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                <svg x-show="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                  <path fill="currentColor" class="opacity-75" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                <span x-text="submitting ? 'Menyimpan…' : 'Simpan Perubahan'"></span>
              </button>
              <a href="{{ route('finance.index') }}"
                 class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                Batal
              </a>
            </div>
          </div>
        </section>

        <p class="px-1 text-[11px] leading-relaxed text-gray-400">
          Riwayat perubahan akan tercatat di Log Aktivitas dengan snapshot data sebelum &amp; sesudah edit.
        </p>
      </aside>
    </form>
  </div>

  <style>
    .font-display { font-family: 'Montserrat', system-ui, sans-serif; letter-spacing: -0.01em; }
  </style>
</x-app-layout>
