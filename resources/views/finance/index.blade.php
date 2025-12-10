<x-app-layout>
  <x-slot name="header">
    <h2 class="text-xl font-semibold leading-tight text-gray-800">
      Laporan Keuangan
    </h2>
  </x-slot>

  <div class="space-y-6">
    <h1 class="text-2xl font-semibold text-black">Laporan Keuangan</h1>

    <div class="space-y-4">

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-stats-card title="Penjualan Bulanan" value="Rp {{ number_format($stats['monthly_sales'], 0, ',', '.') }}"
          percentage="{{ $stats['monthly_percentage'] }}%" :trend="$stats['monthly_trend']" />

        <x-stats-card title="Penjualan Harian" value="Rp {{ number_format($stats['daily_sales'], 0, ',', '.') }}"
          percentage="{{ $stats['daily_percentage'] }}%" :trend="$stats['daily_trend']" />

        <x-stats-card title="Total Transaksi" value="{{ number_format($stats['total_transactions'], 0, ',', '.') }}"
          percentage="{{ $stats['transaction_percentage'] }}%" :trend="$stats['transaction_trend']" :hasFilter="true"
          filterId="transactionFilterSelect" :currentFilter="$transactionFilter" />
      </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-700">
          Grafik Penjualan
        </h3>
      </div>
      <div class="h-64">
        <canvas id="salesChart"></canvas>
      </div>
    </div>

    <div id="transactions" class="rounded-lg border border-gray-200 bg-white shadow">
      <div class="border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-medium text-gray-700">Riwayat Transaksi</h3>
      </div>
      <x-content.data-table :search="false">
        <x-slot name="header">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            ID Transaksi
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Tanggal
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Stok Terjual
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Pendapatan Masuk
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Action
          </th>
        </x-slot>

        <x-slot name="sortOptions">
          <option value="trx_id_asc" {{ request('sort') == 'trx_id_asc' ? 'selected' : '' }}>ID Transaksi (⇧)</option>
          <option value="trx_id_desc" {{ request('sort') == 'trx_id_desc' ? 'selected' : '' }}>ID Transaksi (⇩)
          </option>
          <option value="income_in_asc" {{ request('sort') == 'income_in_asc' ? 'selected' : '' }}>Pendapatan Masuk
            (⇧)</option>
          <option value="income_in_desc" {{ request('sort') == 'income_in_desc' ? 'selected' : '' }}>Pendapatan Masuk
            (⇩)</option>
          <option value="date_new" {{ request('sort') == 'date_new' ? 'selected' : '' }}>Tanggal Terbaru</option>
          <option value="date_old" {{ request('sort') == 'date_old' ? 'selected' : '' }}>Tanggal Terlama</option>
        </x-slot>

        <x-slot name="body">
          @forelse ($financeReports as $report)
            <tr class="border-b last:border-0 hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ $report->id }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ $report->date->translatedFormat('d F Y') }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ number_format($report->total_items_sold, 0, ',', '.') }} item
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                Rp {{ number_format($report->total_income, 0, ',', '.') }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <a href="{{ route('finance.show', $report->id) }}"
                  class="text-button-main hover:text-button-hover transition-colors" title="Lihat detail aktivitas">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                <div class="flex flex-col items-center justify-center">
                  <svg class="mb-2 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <p class="font-medium">Belum ada data transaksi</p>
                  <p class="mt-1 text-xs text-gray-400">Transaksi akan muncul di sini setelah ada
                    penjualan</p>
                </div>
              </td>
            </tr>
          @endforelse
        </x-slot>
        <x-slot name="showing">
          Showing
          <span class="font-medium"> {{ $financeReports->firstItem() ?? 0 }}-{{ $financeReports->lastItem() ?? 0 }}</span> data of
          <span class="font-medium">{{ $financeReports->total() }}</span> entries

        </x-slot>
        <x-slot name="pagination">
          {{ $financeReports->onEachSide(1)->links() }}
        </x-slot>
      </x-content.data-table>
    </div>
  </div>

  @push('scripts')
    <script>
      let salesChart = null;

      function initChart() {
        if (salesChart) {
          salesChart.destroy();
        }

        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        const chartData = @json($chartData);

        salesChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: chartData.labels,
            datasets: [{
              label: 'Penjualan (Rp)',
              data: chartData.values,
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.4,
              fill: true,
              pointBackgroundColor: 'rgb(59, 130, 246)',
              pointBorderColor: '#fff',
              pointBorderWidth: 2,
              pointRadius: 4,
              pointHoverRadius: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                  }
                }
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) {
                    if (value >= 1000000) {
                      return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                    } else if (value >= 1000) {
                      return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                    }
                    return 'Rp ' + value.toLocaleString('id-ID');
                  }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.05)'
                }
              },
              x: {
                grid: {
                  display: false
                }
              }
            }
          }
        });
      }

      // Init chart
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChart);
      } else {
        initChart();
      }

      // Transaction Filter Handler (di dalam card)
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
</x-app-layout>
