<x-app-layout>
  <x-slot name="header">
    <h2 class="text-xl font-semibold leading-tight text-gray-800">Laporan Keuangan</h2>
  </x-slot>

  <div class="space-y-6">
    <h1 class="text-2xl font-semibold text-black">Laporan Keuangan</h1>

    {{-- Download Modal --}}
    <x-finance.download-modal  
    :products="$products"
    :categories="$categories"/>
    
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <x-stats-card 
        title="Penjualan Bulanan" 
        value="Rp {{ number_format($stats['monthly_sales'], 0, ',', '.') }}"
        percentage="{{ $stats['monthly_percentage'] }}%" 
        :trend="$stats['monthly_trend']" />

      <x-stats-card 
        title="Penjualan Harian" 
        value="Rp {{ number_format($stats['daily_sales'], 0, ',', '.') }}"
        percentage="{{ $stats['daily_percentage'] }}%" 
        :trend="$stats['daily_trend']" />

      <x-stats-card 
        title="Total Transaksi" 
        value="{{ number_format($stats['total_transactions'], 0, ',', '.') }}"
        percentage="{{ $stats['transaction_percentage'] }}%" 
        :trend="$stats['transaction_trend']" 
        :hasFilter="true"
        filterId="transactionFilterSelect" 
        :currentFilter="$transactionFilter" />
    </div>

    {{-- Sales Chart --}}
    <x-finance.sales-chart :chartData="$chartData" />

    {{-- Transaction Table --}}
    <x-finance.transaction-table :financeReports="$financeReports" />
  </div>
</x-app-layout>