<!-- resources/views/dashboard/index.blade.php -->
<x-app-layout>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500 mb-1">Total Produk</div>
            <div class="text-3xl font-bold text-gray-800">{{ $totalProducts ?? 0 }}</div>
          </div>
          <div class="bg-blue-50 p-3 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500 mb-1">Total Stok</div>
            <div class="text-3xl font-bold text-gray-800">{{ $totalStock ?? 0 }}</div>
          </div>
          <div class="bg-green-50 p-3 rounded-lg">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500 mb-1">Total Kategori</div>
            <div class="text-3xl font-bold text-gray-800">{{ $totalCategories ?? 0 }}</div>
          </div>
          <div class="bg-purple-50 p-3 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500 mb-1">Penghasilan Bulanan</div>
            <div class="text-2xl font-bold text-gray-800">
              @if(!is_null($monthlyIncome))
                Rp {{ number_format($monthlyIncome, 0, ',', '.') }}
              @else
                <span class="text-gray-400">-</span>
              @endif
            </div>
          </div>
          <div class="bg-amber-50 p-3 rounded-lg">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
          <span class="bg-green-100 text-green-600 rounded-full w-2 h-2 mr-2"></span>
          Kategori Terbanyak
        </h3>
        <div class="flex items-center justify-between">
          <span class="text-lg font-medium text-gray-700">{{ $mostItemCategory->name ?? '-' }}</span>
          <span class="bg-gray-100 px-3 py-1 rounded-full text-sm text-gray-600">
            {{ $mostItemCategory->products_count ?? 0 }} produk
          </span>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
          <span class="bg-red-100 text-red-600 rounded-full w-2 h-2 mr-2"></span>
          Kategori Tersedikit
        </h3>
        <div class="flex items-center justify-between">
          <span class="text-lg font-medium text-gray-700">{{ $leastItemCategory->name ?? '-' }}</span>
          <span class="bg-gray-100 px-3 py-1 rounded-full text-sm text-gray-600">
            {{ $leastItemCategory->products_count ?? 0 }} produk
          </span>
        </div>
      </div>
    </div>

    <!-- Low Stock Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800 flex items-center">
          <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          5 Produk dengan Stok Terendah
        </h3>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr class="text-sm text-gray-600">
              <th class="py-3 px-6 text-left font-medium">ID</th>
              <th class="py-3 px-6 text-left font-medium">Nama Produk</th>
              <th class="py-3 px-6 text-left font-medium">Stok</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse($fiveLowest as $p)
              <tr class="hover:bg-gray-50 transition-colors">
                <td class="py-3 px-6 text-sm text-gray-600">{{ $p->id }}</td>
                <td class="py-3 px-6 text-sm font-medium text-gray-800">{{ $p->name }}</td>
                <td class="py-3 px-6">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    {{ $p->stock_opname < 10 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ $p->stock_opname }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="py-8 text-center text-gray-500">
                  <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                  </svg>
                  Tidak ada produk
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</x-app-layout>