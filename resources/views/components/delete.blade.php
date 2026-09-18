@props([
    'module' => 'item',
    'name' => null,
    'action' => '#',
    // URL JSON opsional yang melaporkan dampak penghapusan terhadap stok.
    // Tanpa prop ini, komponen berperilaku persis seperti sebelumnya.
    'preview' => null,
])

<div class="inline" x-data="{
    open: false,
    loading: false,
    canDelete: true,
    impact: [],
    blockers: [],
    previewUrl: @js($preview),

    show() {
        this.open = true;
        if (!this.previewUrl) return;

        this.loading = true;
        this.canDelete = true;
        this.impact = [];
        this.blockers = [];

        fetch(this.previewUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                this.canDelete = data.can_delete;
                this.impact = data.impact ?? [];
                this.blockers = data.blockers ?? [];
            })
            .catch(() => {
                // Gagal memuat pratinjau bukan alasan memblokir penghapusan;
                // server tetap menolak kalau memang tidak boleh.
                this.canDelete = true;
            })
            .finally(() => this.loading = false);
    },
}">
    <a href="#" @click.prevent="show()" class="text-red-600 hover:text-red-800" title="Delete">
        <img src="{{ asset('delete-button.svg') }}" alt="Delete" class="inline h-5 w-5">
    </a>

    <div x-show="open" x-cloak x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">

        <div @click.away="open = false" class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">

            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Konfirmasi Hapus
            </h2>

            <p class="text-gray-600 mb-4 wrap-break-words whitespace-normal">
                Anda yakin akan menghapus {{ strtolower($module) }}
                <span class="font-semibold text-gray-800 break-all">
                    "{{ $name }}"
                </span>?
            </p>

            <template x-if="loading">
                <p class="mb-4 text-sm text-gray-500">Memeriksa dampak ke stok...</p>
            </template>

            {{-- Penghapusan yang menarik stok harus menyebut angkanya sebelum
                 ditekan, bukan sesudah. --}}
            <template x-if="!loading && canDelete && impact.length">
                <div class="mb-4 rounded-lg border border-wait-edge bg-wait-tint p-3">
                    <p class="mb-2 text-sm font-semibold text-gray-800">Stok berikut akan ikut ditarik kembali:</p>
                    <ul class="space-y-1">
                        <template x-for="row in impact" :key="row.product_name + row.quantity">
                            <li class="flex justify-between text-sm">
                                <span class="text-gray-700" x-text="row.product_name"></span>
                                <span class="font-semibold tabular-nums text-wait"
                                    x-text="'-' + Number(row.quantity).toLocaleString('id-ID') + ' ' + (row.unit ?? '').toLowerCase()"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            <template x-if="!loading && !canDelete">
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3">
                    <p class="mb-2 text-sm font-semibold text-red-800">Tidak bisa dihapus:</p>
                    <ul class="list-disc space-y-1 pl-5">
                        <template x-for="reason in blockers" :key="reason">
                            <li class="text-sm text-red-700" x-text="reason"></li>
                        </template>
                    </ul>
                </div>
            </template>

            <div class="flex justify-end gap-3">
                <button @click="open = false"
                    class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md cursor-pointer transition-colors duration-300">
                    Batal
                </button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" x-bind:disabled="loading || !canDelete"
                        class="px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-md cursor-pointer transition-colors duration-300 disabled:cursor-not-allowed disabled:opacity-50">
                        Hapus
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
