<x-app-layout>
    <div class="space-y-6">
        <div class="flex justify-end">
            <a href="{{ route('cashier') }}"
                class="bg-button-main hover:bg-button-hover inline-flex transform items-center rounded-lg px-6 py-3 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:shadow-xl">
                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Buka Kasir
            </a>
        </div>

        <!-- Stats Cards -->
        @if ($user && $user->isOwner())
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @endif
        <div class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <div class="mb-1 text-sm text-gray-500">Total Produk</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalProducts ?? 0 }}</div>
                </div>
                <div class="rounded-lg bg-blue-50 p-3">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <div class="mb-1 text-sm text-gray-500">Total Stok</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalStock ?? 0 }}</div>
                </div>
                <div class="rounded-lg bg-green-50 p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <div class="mb-1 text-sm text-gray-500">Total Kategori</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $totalCategories ?? 0 }}</div>
                </div>
                <div class="rounded-lg bg-purple-50 p-3">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
            </div>
        </div>

        @if ($user && $user->isOwner())
            <div class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="mb-1 text-sm text-gray-500">Penghasilan Bulanan</div>
                        <div class="text-2xl font-bold text-gray-800">
                            @if (!is_null($monthlyIncome))
                                Rp {{ number_format($monthlyIncome, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-3">
                        <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Category Stats -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-3 flex items-center font-semibold text-gray-800">
                <span class="mr-2 h-2 w-2 rounded-full bg-green-100 text-green-600"></span>
                Kategori Terbanyak
            </h3>
            <div class="flex items-center justify-between">
                <span class="text-lg font-medium text-gray-700">{{ $mostItemCategory->name ?? '-' }}</span>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600">
                    {{ $mostItemCategory->products_count ?? 0 }} produk
                </span>
            </div>
        </div>

        <div class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-3 flex items-center font-semibold text-gray-800">
                <span class="mr-2 h-2 w-2 rounded-full bg-red-100 text-red-600"></span>
                Kategori Tersedikit
            </h3>
            <div class="flex items-center justify-between">
                <span class="text-lg font-medium text-gray-700">{{ $leastItemCategory->name ?? '-' }}</span>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600">
                    {{ $leastItemCategory->products_count ?? 0 }} produk
                </span>
            </div>
        </div>
    </div>

    <!-- Low Stock Table -->
    <div class="overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-6">
            <h3 class="flex items-center font-semibold text-gray-800">
                <svg class="mr-2 h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                5 Produk dengan Stok Terendah
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr class="text-sm text-gray-600">
                        <th class="px-6 py-3 text-left font-medium">ID</th>
                        <th class="px-6 py-3 text-left font-medium">Nama Produk</th>
                        <th class="px-6 py-3 text-left font-medium">Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($fiveLowest as $p)
                        <tr class="transition-colors hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $p->id }}</td>
                            <td class="px-6 py-3 text-sm font-medium text-gray-800">{{ $p->name }}</td>
                            <td class="px-6 py-3">
                                <span
                                    class="{{ $p->stock_opname < 10 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ $p->stock_opname }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-gray-500">
                                <svg class="mx-auto mb-2 h-12 w-12 text-gray-300" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
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
