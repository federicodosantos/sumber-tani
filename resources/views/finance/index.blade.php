<x-app-layout>
  <x-slot name="header">
    <h2 class="text-xl font-semibold leading-tight text-gray-800">Laporan Keuangan</h2>
  </x-slot>

  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h1 class="text-2xl font-semibold text-black">Laporan Keuangan</h1>

      {{-- Date Range Filter --}}
      <div x-data="{
          open: false,
          rangeKey: '{{ $rangeKey }}',
          startDate: '{{ $startDate->format('Y-m-d') }}',
          endDate: '{{ $endDate->format('Y-m-d') }}',
          label: '{{ $rangeLabel }}',
          presets: [
              { key: 'this_week',    label: 'Minggu Ini' },
              { key: 'this_month',   label: 'Bulan Ini' },
              { key: 'last_month',   label: 'Bulan Lalu' },
              { key: 'this_quarter', label: 'Kuartal Ini' },
          ],
          applyFilter(key) {
              const url = new URL(window.location.href);
              url.searchParams.set('range_filter', key);
              url.searchParams.delete('start_date');
              url.searchParams.delete('end_date');
              url.searchParams.delete('page');
              window.location.href = url.toString();
          },
          applyCustom() {
              const url = new URL(window.location.href);
              url.searchParams.set('range_filter', 'custom');
              url.searchParams.set('start_date', this.startDate);
              url.searchParams.set('end_date', this.endDate);
              url.searchParams.delete('page');
              window.location.href = url.toString();
          }
      }" id="finance-range-dropdown" class="relative cursor-pointer">
        {{-- Trigger Button --}}
        <button @click="open = !open" type="button"
          class="flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">
          <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span x-text="label"></span>
          <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        {{-- Dropdown Panel --}}
        <div x-show="open" @click.outside="open = false"
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
          x-transition:enter-end="opacity-100 scale-100 translate-y-0"
          x-transition:leave="transition ease-in duration-150"
          x-transition:leave-start="opacity-100 scale-100"
          x-transition:leave-end="opacity-0 scale-95"
          class="absolute right-1 z-[100] mt-2 w-72 origin-top-right rounded-2xl border border-gray-200 bg-white shadow-xl ring-1 ring-black/5"
          style="display: none;">

          {{-- Preset Options --}}
          <div class="p-2">
            <template x-for="preset in presets" :key="preset.key">
              <button @click="applyFilter(preset.key)"
                :class="rangeKey === preset.key ? 'bg-button-main text-white font-bold' : 'text-gray-700 hover:bg-gray-100'"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors cursor-pointer">
                <span x-text="preset.label"></span>
                <svg x-show="rangeKey === preset.key" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </template>
          </div>

          {{-- Separator --}}
          <div class="border-t border-gray-100 mx-2"></div>

          {{-- Custom Range --}}
          <div class="p-3">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Rentang Kustom</p>
            <div class="flex flex-col w-full gap-2">
              <div class="relative flex-1">
                <label class="mb-1 block text-xs text-gray-500">Mulai</label>
                <div class="relative">
                    <input type="text" x-model="startDate" id="litepicker-start" readonly
                      class="w-full rounded-lg border border-gray-300 pl-8 pr-2 py-1.5 text-sm focus:border-green-500 focus:ring-green-500 bg-white cursor-pointer shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
              </div>
              
              <div class="relative flex-1">
                <label class="mb-1 block text-xs text-gray-500">Sampai</label>
                <div class="relative">
                    <input type="text" x-model="endDate" id="litepicker-end" readonly
                      class="w-full rounded-lg border border-gray-300 pl-8 pr-2 py-1.5 text-sm focus:border-green-500 focus:ring-green-500 bg-white cursor-pointer shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
              </div>
            </div>
            <button @click="applyCustom()" type="button"
              class="mt-3 w-full rounded-lg bg-button-main hover:bg-button-hover px-3 py-2 text-sm font-semibold text-white transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-md shadow-green-200">
              Terapkan
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Download Modal --}}
    <x-finance.download-modal
      :action="route('finance.download')"
      :products="$products"
      :categories="$categories"/>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <x-stats-card
        title="Penjualan Periode Ini"
        value="Rp {{ number_format($stats['range_sales'], 0, ',', '.') }}"
        percentage="{{ $stats['range_sales_percentage'] }}%"
        :trend="$stats['range_sales_trend']" />

      <x-stats-card
        title="Penjualan Hari Ini"
        value="Rp {{ number_format($stats['daily_sales'], 0, ',', '.') }}"
        percentage="{{ $stats['daily_percentage'] }}%"
        :trend="$stats['daily_trend']" />

      <x-stats-card
        title="Total Transaksi"
        value="{{ number_format($stats['total_transactions'], 0, ',', '.') }}"
        percentage="{{ $stats['transaction_percentage'] }}%"
        :trend="$stats['transaction_trend']" />
    </div>

    {{-- Laba Rugi & Neraca Sections --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      {{-- Laba Rugi Card --}}
      <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Laba Rugi (Periode Ini)</h3>
            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
              Profit & Loss
            </span>
          </div>
        </div>
        <div class="p-6">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-500">Total Pendapatan</span>
              <span class="text-sm font-semibold text-gray-900">Rp {{ number_format($profitLoss['revenue'], 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between border-b border-dashed border-gray-200 pb-4">
              <span class="text-sm text-gray-500">Harga Pokok Penjualan (HPP)</span>
              <span class="text-sm font-semibold text-red-600">-Rp {{ number_format($profitLoss['cogs'], 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between pt-2">
              <span class="text-base font-bold text-gray-900">Laba Kotor</span>
              <span class="text-xl font-extrabold text-button-hover">Rp {{ number_format($profitLoss['gross_profit'], 0, ',', '.') }}</span>
            </div>
          </div>
          <div class="mt-6 rounded-xl bg-button-main/30 p-4">
              <div class="flex gap-3">
                  <svg class="h-5 w-5 text-button-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <p class="text-xs leading-relaxed text-gray-800">
                      Laba kotor dihitung berdasarkan selisih pendapatan dengan modal produk (HPP) yang terjual dalam periode yang dipilih.
                  </p>
              </div>
          </div>
        </div>
      </div>

      {{-- Neraca Card --}}
      <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Neraca Keuangan (Akumulasi)</h3>
            <span class="rounded-full bg-button-main/30 px-2.5 py-0.5 text-xs font-medium text-gray-800">
              Balance Sheet
            </span>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-2 gap-8">
            {{-- Assets --}}
            <div class="space-y-3">
              <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Aset</p>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-xs text-gray-500">Kas</span>
                  <span class="text-xs font-medium text-gray-700">Rp {{ number_format($balanceSheet['assets']['cash'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-xs text-gray-500">Persediaan</span>
                  <span class="text-xs font-medium text-gray-700">Rp {{ number_format($balanceSheet['assets']['inventory'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                  <span class="text-xs text-gray-500">Piutang</span>
                  <span class="text-xs font-medium text-gray-700">Rp {{ number_format($balanceSheet['assets']['receivables'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between pt-1">
                  <span class="text-xs font-bold text-gray-900">Total</span>
                  <span class="text-xs font-bold text-button-hover">Rp {{ number_format($balanceSheet['assets']['total'], 0, ',', '.') }}</span>
                </div>
              </div>
            </div>

            {{-- Liabilities & Equity --}}
            <div class="space-y-3">
              <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Kewajiban & Ekuitas</p>
              <div class="space-y-2">
                <div class="flex justify-between border-b border-gray-100 pb-2">
                  <span class="text-xs text-gray-500">Hutang</span>
                  <span class="text-xs font-medium text-gray-700">Rp {{ number_format($balanceSheet['liabilities']['total'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between pt-1">
                  <span class="text-xs font-bold text-gray-900">Ekuitas</span>
                  <span class="text-xs font-extrabold text-button-hover">Rp {{ number_format($balanceSheet['equity'], 0, ',', '.') }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 rounded-xl bg-gray-50 p-4 border border-gray-100">
              <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                      <div class="h-2 w-2 rounded-full bg-button-hover"></div>
                      <span class="text-xs font-medium text-gray-600">Aset = Kewajiban + Ekuitas</span>
                  </div>
                  <svg class="h-4 w-4 text-button-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
              </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Sales Chart --}}
    <x-finance.sales-chart :chartData="$chartData" />

    {{-- Transaction Table --}}
    <x-finance.transaction-table :financeReports="$financeReports" />
  </div>
@push('scripts')
  {{-- Litepicker CDN --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css" />
  <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>

  <style>
    /* Premium Litepicker Customization */
    :root {
        /* Warna Utama (Solid) */
        --brand-color: #ABD36F;
        /* Warna Range (50% Opacity) */
        --brand-range: rgba(171, 211, 111, 0.5); 
        
        --litepicker-button-prev-month-color: #4b5563;
        --litepicker-button-next-month-color: #4b5563;
        --litepicker-button-prev-month-color-hover: var(--brand-color);
        --litepicker-button-next-month-color-hover: var(--brand-color);
        --litepicker-day-color-hover: #f7fee7;
        
        /* Litepicker Variables */
        --litepicker-is-in-range-color: var(--brand-range);
        --litepicker-is-start-color-bg: var(--brand-color);
        --litepicker-is-end-color-bg: var(--brand-color);
    }

    .litepicker {
        font-family: inherit !important;
        border-radius: 1.25rem !important;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
        border: 1px solid #f3f4f6 !important;
        padding: 12px !important;
    }

    /* Memperbaiki angka yang 'keblok' dan memastikan text white */
    .litepicker .container__days .day-item {
        border-radius: 0.5rem !important;
        transition: all 0.2s;
        color: #374151; /* Warna default tgl biasa */
        z-index: 1;
    }

    /* Styling Start dan End Date */
    .litepicker .container__days .day-item.is-start,
    .litepicker .container__days .day-item.is-end {
        background-color: var(--brand-color) !important;
        color: #ffffff !important; /* Paksa teks jadi putih */
        font-weight: 800 !important;
        box-shadow: 0 4px 6px -1px rgba(171, 211, 111, 0.4);
    }

    /* Styling Range (diantara start & end) */
    .litepicker .container__days .day-item.is-in-range {
        background-color: var(--brand-range) !important;
        color: #3f6212 !important; /* Warna teks gelap dikit biar kebaca di range */
    }

    /* Header & Weekdays */
    .litepicker .container__months .month-item-header {
        padding: 10px 0 !important;
    }

    .litepicker .container__months .month-item-header div {
        font-weight: 700 !important;
        color: #111827 !important;
    }

    .litepicker .container__months .month-item-weekdays-row > div {
        font-weight: 600 !important;
        color: #9ca3af !important;
        padding: 8px 0 !important;
    }
    
    /* Hover effect */
    .litepicker .container__days .day-item:hover {
        background-color: #f0fdf4 !important;
        color: var(--brand-color) !important;
    }
</style>

  <script>
    document.addEventListener('alpine:init', () => {
      // Logic inside Alpine.js component if needed
    });

    // Initialize Litepicker when the dropdown is open or just on load
    window.addEventListener('DOMContentLoaded', () => {
      const picker = new Litepicker({
        element: document.getElementById('litepicker-start'),
        elementEnd: document.getElementById('litepicker-end'),
        singleMode: false,
        allowRepick: true,
        tooltipText: {
          one: 'hari',
          other: 'hari'
        },
        buttonText: {
            apply: 'Terapkan',
            cancel: 'Batal',
        },
        setup: (picker) => {
          picker.on('selected', (date1, date2) => {
             // For Alpine.js sync
             const el = document.querySelector('[x-data]');
             if (el && el.__x && el.__x.$data) {
                el.__x.$data.startDate = date1.format('YYYY-MM-DD');
                el.__x.$data.endDate = date2.format('YYYY-MM-DD');
             }
             // Manual sync if Alpine is not direct
             const scope = document.getElementById('finance-range-dropdown');
             if(scope) {
                const alpineData = Alpine.$data(scope);
                alpineData.startDate = date1.format('YYYY-MM-DD');
                alpineData.endDate = date2.format('YYYY-MM-DD');
             }
          });
        }
      });
    });
  </script>
@endpush
</x-app-layout>