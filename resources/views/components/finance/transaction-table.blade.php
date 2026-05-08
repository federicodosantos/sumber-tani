@props(['financeReports'])

<div id="transactions" class="rounded-lg border border-gray-200 bg-white shadow">
  <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between gap-3 flex-wrap">
    <h3 class="text-lg font-medium text-gray-700">Riwayat Transaksi</h3>
    <a href="{{ route('finance.manual.create') }}"
      class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-700 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-amber-600">
        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487 18.549 2.799a2.121 2.121 0 1 1 3 3L5.12 22.227a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
      </svg>
      Tambah Manual
    </a>
  </div>
  
  <x-content.data-table :search="false">
    <x-slot name="header">
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">ID Transaksi</th>
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Tanggal</th>
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Metode Pembayaran</th>
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Stok Terjual</th>
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Diskon</th>
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Pendapatan Masuk</th>
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Sudah Lunas</th>
      <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Action</th>
    </x-slot>

    <x-slot name="sortOptions">
      <option value="trx_id_asc" {{ request('sort') == 'trx_id_asc' ? 'selected' : '' }}>ID Transaksi (⇧)</option>
      <option value="trx_id_desc" {{ request('sort') == 'trx_id_desc' ? 'selected' : '' }}>ID Transaksi (⇩)</option>
      <option value="income_in_asc" {{ request('sort') == 'income_in_asc' ? 'selected' : '' }}>Pendapatan Masuk (⇧)</option>
      <option value="income_in_desc" {{ request('sort') == 'income_in_desc' ? 'selected' : '' }}>Pendapatan Masuk (⇩)</option>
      <option value="date_new" {{ request('sort') == 'date_new' ? 'selected' : '' }}>Tanggal Terbaru</option>
      <option value="date_old" {{ request('sort') == 'date_old' ? 'selected' : '' }}>Tanggal Terlama</option>
    </x-slot>

    <x-slot name="body">
      @forelse ($financeReports as $report)
        <tr class="border-b last:border-0 hover:bg-gray-50">
          <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-semibold">{{ $report->id }}</span>
              @if (!empty($report->is_manual))
                <span class="inline-flex items-center rounded bg-amber-100 border border-amber-200 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700" title="Nota manual (tidak mengurangi stok)">MANUAL</span>
              @endif
              @if (!empty($report->r2_customer))
                <a href="{{ route('customer-r2.show', $report->r2_customer->id) }}"
                   class="inline-flex items-center gap-1 rounded bg-blue-50 border border-blue-200 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-blue-700 hover:bg-blue-100 transition-colors"
                   title="Pelanggan R2: {{ $report->r2_customer->name }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-2.5 w-2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                  </svg>
                  R2 — {{ \Illuminate\Support\Str::limit($report->r2_customer->name, 18) }}
                </a>
              @endif
            </div>
            @if (!empty($report->inv_code))
              <p class="mt-0.5 text-[10px] text-gray-400 font-medium">{{ $report->inv_code }}</p>
            @endif
          </td>
          <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ $report->date->translatedFormat('d F Y') }}</td>
          <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ strtoupper($report->payment_method) }}</td>
          <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ number_format($report->total_items_sold, 0, ',', '.') }} item</td>
          <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">Rp {{ number_format($report->discount, 0, ',', '.') }}</td>
          <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">Rp {{ number_format($report->total_income, 0, ',', '.') }}</td>
          <td class="whitespace-nowrap px-6 py-4 text-sm">
            <span class="px-3 py-1 rounded-full {{ $report->is_paid ? 'bg-green-200 text-green-700 font-bold' : 'bg-red-200 text-red-700 font-bold' }}">
              {{ $report->is_paid ? 'Sudah' : 'Belum' }}
            </span>
          </td>
          <td class="whitespace-nowrap px-6 py-4 text-sm">
            <div class="flex items-center gap-3">
              <a href="{{ route('finance.show', $report->id) }}" class="text-button-main hover:text-button-hover transition-colors" title="Lihat detail aktivitas">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
              </a>
              @if(auth()->check() && auth()->user()->isOwner())
                <a href="{{ route('finance.edit', $report->id) }}" class="text-blue-600 hover:text-blue-800 transition-colors" title="Edit transaksi">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                  </svg>
                </a>
                <button type="button"
                  x-data
                  @click="$dispatch('open-delete-trx-modal', '{{ $report->id }}')"
                  class="text-red-600 hover:text-red-800 transition-colors cursor-pointer" title="Hapus transaksi">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                  </svg>
                </button>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
            <div class="flex flex-col items-center justify-center">
              <svg class="mb-2 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="font-medium">Belum ada data transaksi</p>
              <p class="mt-1 text-xs text-gray-400">Transaksi akan muncul di sini setelah ada penjualan</p>
            </div>
          </td>
        </tr>
      @endforelse
    </x-slot>

    <x-slot name="showing">
      Showing <span class="font-medium">{{ $financeReports->firstItem() ?? 0 }}-{{ $financeReports->lastItem() ?? 0 }}</span> data of <span class="font-medium">{{ $financeReports->total() }}</span> entries
    </x-slot>

    <x-slot name="pagination">
      {{ $financeReports->onEachSide(1)->links() }}
    </x-slot>
  </x-content.data-table>
</div>

@if(auth()->check() && auth()->user()->isOwner())
  {{-- Delete Transaction Modal --}}
  <div x-data="{ deleteId: '', confirmText: '' }" @open-delete-trx-modal.window="deleteId = $event.detail; confirmText = ''; $dispatch('open-modal', 'delete-trx-modal')">
    <x-modal name="delete-trx-modal" maxWidth="md">
      <div class="mb-3 flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900">Hapus Transaksi</h3>
      </div>
      
      <p class="mb-3 text-sm text-gray-600">
        Transaksi <strong x-text="'#' + deleteId" class="text-gray-900"></strong> akan dihapus permanen.
        Stok produk akan dikembalikan, dan invoice serta pembayaran utang yang terkait akan di-reverse.
      </p>
      
      <p class="mb-3 text-xs text-red-600">
        <strong>Aksi ini tidak bisa dibatalkan.</strong> Ketik <code class="rounded bg-gray-100 px-1 py-0.5">HAPUS</code> untuk konfirmasi.
      </p>
      
      <form method="POST" :action="`{{ url('laporan-keuangan') }}/${deleteId}`">
        @csrf
        @method('DELETE')
        
        <input type="text" x-model="confirmText" placeholder="Ketik HAPUS"
          class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500" />
          
        <div class="flex justify-end gap-2">
          <button type="button" @click="$dispatch('close-modal', 'delete-trx-modal')"
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Batal
          </button>
          <button type="submit" :disabled="confirmText !== 'HAPUS'"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300">
            Hapus
          </button>
        </div>
      </form>
    </x-modal>
  </div>
@endif

@push('scripts')
<script>
  const transactionFilterSelect = document.getElementById('transactionFilterSelect');
  if (transactionFilterSelect) {
    transactionFilterSelect.addEventListener('change', function() {
      const url = new URL(window.location.href);
      url.searchParams.set('transaction_filter', this.value);
      window.location.href = url.toString();
    });
  }

  const transactionSortSelect = document.getElementById('transactionSortSelect');
  if (transactionSortSelect) {
    transactionSortSelect.addEventListener('change', function() {
      const url = new URL(window.location.href);
      url.searchParams.set('sort', this.value);
      window.location.href = url.toString() + '#transactions';
    });
  }
</script>
@endpush