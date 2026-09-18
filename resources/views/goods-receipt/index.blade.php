@php
    /**
     * Angka qty ditulis tanpa nol ekor: 60.000 -> 60, 1.500 -> 1,5.
     * Orang gudang membaca "60 sak", bukan "60,000 sak".
     */
    $qty = function ($value) {
        $formatted = number_format((float) $value, 3, ',', '.');
        return rtrim(rtrim($formatted, '0'), ',');
    };
@endphp

<x-app-layout>
    <div class="py-4 font-mont lg:py-6">
        <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8"
            x-data="{
                target: null,
                qty: '',

                open(detail) {
                    this.target = detail;
                    this.qty = detail.outstanding;
                    this.$dispatch('open-modal', 'terima-barang');
                },

                openClose(detail) {
                    this.target = detail;
                    this.$dispatch('open-modal', 'tutup-sisa');
                },

                /** Angka yang sedang diketik, menerima koma desimal. */
                get typed() {
                    return Number(String(this.qty).replace(',', '.')) || 0;
                },

                /** Persentase yang sudah diterima sebelum input ini. */
                get donePercent() {
                    if (!this.target || !this.target.ordered) return 0;
                    return Math.min(100, this.target.received / this.target.ordered * 100);
                },

                /** Persentase tambahan dari angka yang sedang diketik. */
                get addingPercent() {
                    if (!this.target || !this.target.ordered) return 0;
                    return Math.min(100 - this.donePercent, this.typed / this.target.ordered * 100);
                },

                get remainingAfter() {
                    if (!this.target) return 0;
                    return Math.max(0, this.target.outstanding - this.typed);
                },
            }">

            {{-- Judul membawa angka yang jadi alasan halaman ini dibuka --}}
            <header class="mb-6 flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">
                    Penerimaan Barang
                </h1>
                <p class="text-sm text-gray-500">
                    <span class="font-bold text-wait tabular-nums">{{ $outstandingCount }}</span>
                    baris masih menunggu barang
                </p>
            </header>

            <form method="GET" action="{{ route('receipt.index') }}" class="mb-6">
                <div class="relative max-w-md">
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Cari produk atau kode..."
                        class="w-full rounded-lg border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm shadow-sm transition-all focus:border-button-hover focus:ring-2 focus:ring-button-main/40">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                </div>
            </form>

            <div class="space-y-3">
                @forelse($details as $detail)
                    @php
                        $ordered = (float) $detail->quantity;
                        $received = (float) $detail->received_quantity;
                        $percent = $ordered > 0 ? min(100, round($received / $ordered * 100, 2)) : 0;
                    @endphp

                    {{-- Barisnya sendiri adalah meteran: bagian hijau = sudah datang,
                         sisanya oker. Progres bukan elemen tambahan di dalam kartu. --}}
                    <article class="relative overflow-hidden rounded-xl border border-wait-edge bg-wait-tint shadow-sm"
                        x-data="{ history: false }">

                        <div class="absolute inset-y-0 left-0 bg-button-main/30" style="width: {{ $percent }}%"
                            aria-hidden="true"></div>

                        <div class="relative flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">

                            <div class="min-w-0">
                                <h2 class="truncate text-base font-bold text-gray-900">
                                    {{ $detail->product_name }}
                                </h2>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600">
                                    <span>Nota #{{ $detail->product_purchase_id }}</span>
                                    <span>{{ $detail->purchase?->purchase_date?->translatedFormat('j M Y') }}</span>
                                    <span>Rp {{ number_format($detail->net_price, 0, ',', '.') }}/{{ strtolower($detail->unit) }}</span>
                                    @if($detail->expired_date)
                                        <span>Exp {{ $detail->expired_date->translatedFormat('j M Y') }}</span>
                                    @endif
                                </div>

                                @if($detail->receipts->isNotEmpty())
                                    <button type="button" @click="history = !history"
                                        class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-wait hover:underline">
                                        <svg class="h-3 w-3 transition-transform" :class="history && 'rotate-90'"
                                            fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                        {{ $detail->receipts->count() }} kedatangan tercatat
                                    </button>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-5">
                                {{-- Angka sisa adalah seluruh alasan halaman ini ada --}}
                                <div class="text-right">
                                    <div class="font-mont text-3xl font-extrabold leading-none tabular-nums text-wait">
                                        {{ $qty($detail->outstanding_quantity) }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        sisa dari {{ $qty($detail->quantity) }} {{ strtolower($detail->unit) }}
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <button type="button"
                                        @click="open({
                                            id: {{ $detail->id }},
                                            name: @js($detail->product_name),
                                            unit: @js(strtolower($detail->unit)),
                                            outstanding: {{ (float) $detail->outstanding_quantity }},
                                            ordered: {{ $ordered }},
                                            received: {{ $received }},
                                            expired: @js($detail->expired_date?->toDateString()),
                                        })"
                                        class="cursor-pointer rounded-lg bg-button-main px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-button-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-button-hover focus-visible:ring-offset-2">
                                        Terima
                                    </button>
                                    <button type="button"
                                        @click="openClose({ id: {{ $detail->id }}, name: @js($detail->product_name), outstanding: {{ (float) $detail->outstanding_quantity }}, unit: @js(strtolower($detail->unit)) })"
                                        class="cursor-pointer rounded-lg px-5 py-1.5 text-xs font-semibold text-settled transition-colors hover:bg-white/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-settled">
                                        Tutup sisa
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Riwayat diberi nomor urut karena kedatangan memang
                             berurutan, dan urutannya menentukan FIFO. --}}
                        <div x-show="history" x-cloak x-transition.opacity.duration.150ms
                            class="relative border-t border-wait-edge bg-white/70 px-4 py-3">
                            <ol class="space-y-2">
                                @foreach($detail->receipts as $i => $receipt)
                                    <li class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-button-main/30 font-bold tabular-nums text-gray-700">
                                                {{ $i + 1 }}
                                            </span>
                                            <span class="font-semibold tabular-nums text-gray-900">
                                                {{ $qty($receipt->quantity) }} {{ strtolower($detail->unit) }}
                                            </span>
                                            <span class="text-gray-500">
                                                {{ $receipt->received_date?->translatedFormat('j M Y') }}
                                            </span>
                                            @if($receipt->note)
                                                <span class="text-gray-500">{{ $receipt->note }}</span>
                                            @endif
                                        </div>

                                        <form method="POST" action="{{ route('receipt.destroy', $receipt) }}"
                                            onsubmit="return confirm('Batalkan kedatangan {{ $qty($receipt->quantity) }} {{ strtolower($detail->unit) }} ini? Stoknya akan ditarik kembali.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="cursor-pointer rounded px-2 py-1 font-semibold text-cancel-button transition-colors hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cancel-button">
                                                Batalkan
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                        <p class="text-base font-bold text-gray-900">Semua barang sudah datang</p>
                        <p class="mt-1 text-sm text-gray-500">
                            Baris pembelian yang ditandai belum masuk stok akan muncul di sini.
                        </p>
                        <a href="{{ route('purchase.index') }}"
                            class="mt-5 inline-block rounded-lg bg-button-main px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-button-hover">
                            Buka data pembelian
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $details->links() }}
            </div>

            {{-- MODAL TERIMA --}}
            <x-modal name="terima-barang" title="TERIMA BARANG" maxWidth="lg">
                <template x-if="target">
                    <form method="POST" :action="`/penerimaan/${target.id}/terima`" class="p-5">
                        @csrf

                        <p class="text-sm text-gray-500">Barang datang</p>
                        <p class="mb-5 text-lg font-bold text-gray-900" x-text="target.name"></p>

                        {{-- Meteran pratinjau: bergerak saat user mengetik, memperlihatkan
                             persis ke mana posisi baris akan berpindah setelah disimpan. --}}
                        <div class="mb-5 overflow-hidden rounded-lg border border-wait-edge bg-wait-tint">
                            <div class="relative h-9">
                                <div class="absolute inset-y-0 left-0 bg-button-main/30"
                                    :style="`width: ${donePercent}%`"></div>
                                <div class="absolute inset-y-0 bg-button-main/70 transition-[width,left] duration-200 ease-out motion-reduce:transition-none"
                                    :style="`left: ${donePercent}%; width: ${addingPercent}%`"></div>
                                <div class="relative flex h-full items-center justify-between px-3 text-xs font-semibold">
                                    <span class="text-gray-700">
                                        sudah <span class="tabular-nums" x-text="target.received.toLocaleString('id-ID')"></span>
                                    </span>
                                    <span class="text-wait">
                                        sisa setelah ini
                                        <span class="tabular-nums" x-text="remainingAfter.toLocaleString('id-ID')"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="receive-qty" class="mb-1.5 block text-sm font-semibold text-gray-700">
                                    Jumlah datang
                                </label>
                                <div class="flex overflow-hidden rounded-lg border border-gray-300 focus-within:border-button-hover focus-within:ring-2 focus-within:ring-button-main/40">
                                    <input type="text" id="receive-qty" name="quantity" x-model="qty" inputmode="decimal"
                                        class="w-full border-none py-2.5 text-sm tabular-nums focus:ring-0"
                                        @keydown="const k=$event.key; const nav=['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End','Enter']; const ok=/[0-9]/.test(k)||nav.includes(k)||$event.ctrlKey||$event.metaKey||(k===','&&!$el.value.includes(',')); if(!ok) $event.preventDefault();"
                                        required>
                                    <span class="flex items-center border-l border-gray-200 bg-gray-50 px-3 text-xs font-semibold text-gray-500"
                                        x-text="target.unit"></span>
                                </div>
                                <button type="button" @click="qty = target.outstanding"
                                    class="mt-1.5 cursor-pointer text-xs font-semibold text-button-hover hover:underline">
                                    Isi seluruh sisa (<span class="tabular-nums" x-text="target.outstanding.toLocaleString('id-ID')"></span>)
                                </button>
                            </div>

                            <div>
                                <label for="receive-date" class="mb-1.5 block text-sm font-semibold text-gray-700">
                                    Tanggal datang
                                </label>
                                <input type="date" id="receive-date" name="received_date" max="{{ now()->toDateString() }}"
                                    value="{{ now()->toDateString() }}"
                                    class="w-full rounded-lg border-gray-300 py-2.5 text-sm focus:border-button-hover focus:ring-2 focus:ring-button-main/40">
                            </div>

                            <div>
                                <label for="receive-expired" class="mb-1.5 block text-sm font-semibold text-gray-700">
                                    Kadaluarsa kiriman ini
                                </label>
                                <input type="date" id="receive-expired" name="expired_date" min="{{ now()->toDateString() }}"
                                    :value="target.expired"
                                    class="w-full rounded-lg border-gray-300 py-2.5 text-sm focus:border-button-hover focus:ring-2 focus:ring-button-main/40">
                            </div>

                            <div>
                                <label for="receive-note" class="mb-1.5 block text-sm font-semibold text-gray-700">
                                    Catatan
                                </label>
                                <input type="text" id="receive-note" name="note" maxlength="255" placeholder="Opsional"
                                    class="w-full rounded-lg border-gray-300 py-2.5 text-sm focus:border-button-hover focus:ring-2 focus:ring-button-main/40">
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="$dispatch('close-modal', 'terima-barang')"
                                class="cursor-pointer rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit"
                                class="cursor-pointer rounded-lg bg-button-main px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:bg-button-hover">
                                Masukkan ke stok
                            </button>
                        </div>
                    </form>
                </template>
            </x-modal>

            {{-- MODAL TUTUP SISA --}}
            <x-modal name="tutup-sisa" title="TUTUP SISA" maxWidth="md">
                <template x-if="target">
                    <form method="POST" :action="`/penerimaan/${target.id}/tutup`" class="p-5">
                        @csrf

                        <p class="text-sm text-gray-600">
                            Sisa
                            <span class="font-bold tabular-nums text-gray-900" x-text="target.outstanding.toLocaleString('id-ID')"></span>
                            <span x-text="target.unit"></span>
                            <span class="font-bold text-gray-900" x-text="target.name"></span>
                            dinyatakan tidak akan datang lagi. Stok tidak berubah dan baris ini keluar dari daftar.
                        </p>

                        <label for="close-reason" class="mb-1.5 mt-5 block text-sm font-semibold text-gray-700">
                            Alasan
                        </label>
                        <input type="text" id="close-reason" name="reason" maxlength="255"
                            placeholder="Contoh: supplier kehabisan stok"
                            class="w-full rounded-lg border-gray-300 py-2.5 text-sm focus:border-button-hover focus:ring-2 focus:ring-button-main/40">

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="$dispatch('close-modal', 'tutup-sisa')"
                                class="cursor-pointer rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit"
                                class="cursor-pointer rounded-lg bg-settled px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors hover:opacity-90">
                                Tutup sisa
                            </button>
                        </div>
                    </form>
                </template>
            </x-modal>
        </div>
    </div>
</x-app-layout>
