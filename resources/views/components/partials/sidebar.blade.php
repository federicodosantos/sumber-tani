{{-- Backdrop blur untuk mobile --}}
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:enter="transition-all ease-linear duration-300"
    x-transition:enter-start="opacity-0 backdrop-blur-none" x-transition:enter-end="opacity-100 backdrop-blur-sm"
    x-transition:leave="transition-all ease-linear duration-300" x-transition:leave-start="opacity-100 backdrop-blur-sm"
    x-transition:leave-end="opacity-0 backdrop-blur-none"
    class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm lg:hidden" x-cloak>
</div>

{{-- Sidebar --}}
<aside
    :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        effectiveSidebarCollapsed ? 'is-collapsed lg:w-20 lg:pr-2' : 'lg:w-72 lg:pr-6'
    ]"
    class="w-72 h-screen bg-white py-6 shadow-xl flex flex-col justify-between fixed top-0 left-0 font-nunito font-semibold z-50 transition-transform duration-300 ease-in-out lg:translate-x-0 lg:transition-[width,padding] lg:duration-200 lg:ease-in-out overflow-hidden">

    {{-- Close button untuk mobile --}}
    <button @click="sidebarOpen = false"
        class="lg:hidden absolute top-4 right-4 p-2 rounded-lg text-gray-600 hover:bg-gray-100">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <div class="flex flex-col flex-1 min-h-0">
        <div class="mb-8 flex items-center justify-center relative shrink-0">
            <button @click="toggleSidebarCollapse()"
                class="hidden lg:flex absolute -right-1 top-0 h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100"
                :title="effectiveSidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'">
                <svg class="h-4 w-4 transition-transform duration-300"
                    :class="effectiveSidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <img x-show="!effectiveSidebarCollapsed" src="{{ asset('images/logo-horizontal.svg') }}" alt="Sumber Tani"
                class="h-10">
            <img x-show="effectiveSidebarCollapsed" x-cloak src="{{ asset('favicon.svg') }}" alt="Sumber Tani"
                class="h-8 w-8">
        </div>

        <nav class="flex-1 overflow-y-auto overflow-x-hidden pb-4" x-data="{ 
            inventarisOpen: {{ request()->is('product*', 'purchase*', 'stock*', 'item-category*') ? 'true' : 'false' }},
            penjualanOpen: {{ request()->is('customer-r2*', 'laporan-keuangan*') ? 'true' : 'false' }}
        }">
            <ul class="space-y-2">

                {{-- Dashboard --}}
                <li>
                    <a href="{{ url('dashboard') }}"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
             {{ request()->is('dashboard*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        <div
                            class="{{ request()->is('dashboard*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            class="{{ request()->is('dashboard*') ? 'bg-button-main text-white tracking-wide font-bold' : '' }} flex items-center w-full px-4 py-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span x-show="!effectiveSidebarCollapsed" x-cloak class="ml-3 text-sm">Dashboard</span>
                        </div>
                    </a>
                </li>

                {{-- Inventaris (Parent) --}}
                <li>
                    <div @click="if(effectiveSidebarCollapsed) { toggleSidebarCollapse(); inventarisOpen = true; } else { inventarisOpen = !inventarisOpen }"
                        :class="effectiveSidebarCollapsed && {{ request()->is('product*', 'purchase*', 'stock*', 'item-category*') ? 'true' : 'false' }} ? 'text-white' : '{{ request()->is('product*', 'purchase*', 'stock*', 'item-category*') ? 'text-button-main font-bold' : 'text-gray-600' }}'"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg cursor-pointer hover:bg-gray-100">
                        <div
                            :class="effectiveSidebarCollapsed && {{ request()->is('product*', 'purchase*', 'stock*', 'item-category*') ? 'true' : 'false' }} ? 'bg-button-main rounded-r-lg' : 'bg-transparent'"
                            class="w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            :class="effectiveSidebarCollapsed && {{ request()->is('product*', 'purchase*', 'stock*', 'item-category*') ? 'true' : 'false' }} ? 'bg-button-main text-white' : ''"
                            class="flex items-center w-full px-4 py-3 rounded-lg justify-between">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                </svg>
                                <span x-show="!effectiveSidebarCollapsed" x-cloak class="ml-3 text-sm">Inventaris</span>
                            </div>
                            <svg x-show="!effectiveSidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"
                                class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="inventarisOpen ? 'rotate-180' : ''">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    <ul x-show="inventarisOpen && !effectiveSidebarCollapsed" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="mt-1 space-y-1 ml-4">

                        {{-- Data Pembelian --}}
                        <li>
                            <a href="{{ url('purchase') }}"
                                class="flex items-stretch flex-row transition-colors duration-200 rounded-lg group
                                    {{ request()->is('purchase*') ? 'text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                                <div class="bg-white w-10"></div>
                                <div
                                    class="{{ request()->is('purchase*') ? 'bg-button-main text-white tracking-wide font-extrabold shadow-sm' : '' }} flex items-center w-full px-4 py-2 rounded-lg">
                                    <span x-show="!effectiveSidebarCollapsed" x-cloak class="text-sm">Data Pembelian</span>
                                </div>
                            </a>
                        </li>

                        {{-- Produk --}}
                        <li>
                            <a href="{{ url('product') }}"
                                class="flex items-stretch flex-row transition-colors duration-200 rounded-lg group
                                    {{ request()->is('product*') ? 'text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                                <div class="bg-white w-10"></div>
                                <div
                                    class="{{ request()->is('product*') ? 'bg-button-main text-white tracking-wide font-extrabold shadow-sm' : '' }} flex items-center w-full px-4 py-2 rounded-lg">
                                    <span x-show="!effectiveSidebarCollapsed" x-cloak class="text-sm">Produk</span>
                                </div>
                            </a>
                        </li>

                        {{-- Stok Produk --}}
                        <li>
                            <a href="{{ url('stock') }}"
                                class="flex items-stretch flex-row transition-colors duration-200 rounded-lg group
                                    {{ request()->is('stock*') ? 'text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                                <div class="bg-white w-10"></div>
                                <div
                                    class="{{ request()->is('stock*') ? 'bg-button-main text-white tracking-wide font-extrabold shadow-sm' : '' }} flex items-center w-full px-4 py-2 rounded-lg">
                                    <span x-show="!effectiveSidebarCollapsed" x-cloak class="text-sm">Stok Produk</span>
                                </div>
                            </a>
                        </li>

                        {{-- Kategori Barang --}}
                        <li>
                            <a href="{{ url('item-category') }}"
                                class="flex items-stretch flex-row transition-colors duration-200 rounded-lg group
                                    {{ request()->is('item-category*') ? 'text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                                <div class="bg-white w-10"></div>
                                <div
                                    class="{{ request()->is('item-category*') ? 'bg-button-main text-white tracking-wide font-extrabold shadow-sm' : '' }} flex items-center w-full px-4 py-2 rounded-lg">
                                    <span x-show="!effectiveSidebarCollapsed" x-cloak class="text-sm">Kategori Barang</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Penjualan (Parent) --}}
                <li>
                    <div @click="if(effectiveSidebarCollapsed) { toggleSidebarCollapse(); penjualanOpen = true; } else { penjualanOpen = !penjualanOpen }"
                        :class="effectiveSidebarCollapsed && {{ request()->is('customer-r2*', 'laporan-keuangan*') ? 'true' : 'false' }} ? 'text-white' : '{{ request()->is('customer-r2*', 'laporan-keuangan*') ? 'text-button-main font-bold' : 'text-gray-600' }}'"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg cursor-pointer hover:bg-gray-100">
                        <div
                            :class="effectiveSidebarCollapsed && {{ request()->is('customer-r2*', 'laporan-keuangan*') ? 'true' : 'false' }} ? 'bg-button-main rounded-r-lg' : 'bg-transparent'"
                            class="w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            :class="effectiveSidebarCollapsed && {{ request()->is('customer-r2*', 'laporan-keuangan*') ? 'true' : 'false' }} ? 'bg-button-main text-white' : ''"
                            class="flex items-center w-full px-4 py-3 rounded-lg justify-between">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75m0 0V4.5m0 0H4.125c1.45 0 2.625 1.175 2.625 2.625V6m0 0c0 1.45-1.175 2.625-2.625 2.625H3.75m0 0V8.25m0 0v11.25a.75.75 0 0 0 .75.75h14.75a.75.75 0 0 0 .75-.75V8.25m-15.75 0h15.75M4.125 6H7.125c.345 0 .625.28.625.625V9.375c0 .345-.28.625-.625.625h-3c-.345 0-.625-.28-.625-.625V6.625c0-.345.28-.625.625-.625Zm12.75 0H19.125c.345 0 .625.28.625.625V9.375c0 .345-.28.625-.625.625h-3c-.345 0-.625-.28-.625-.625V6.625c0-.345.28-.625.625-.625Zm-6 0h3c.345 0 .625.28.625.625V9.375c0 .345-.28.625-.625.625h-3c-.345 0-.625-.28-.625-.625V6.625c0-.345.28-.625.625-.625Z" />
                                </svg>
                                <span x-show="!effectiveSidebarCollapsed" x-cloak class="ml-3 text-sm">Penjualan</span>
                            </div>
                            <svg x-show="!effectiveSidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"
                                class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="penjualanOpen ? 'rotate-180' : ''">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    <ul x-show="penjualanOpen && !effectiveSidebarCollapsed" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="mt-1 space-y-1 ml-4">

                        {{-- Pelanggan R1/R2 --}}
                        <li>
                            <a href="{{ url('customer-r2') }}"
                                class="flex items-stretch flex-row transition-colors duration-200 rounded-lg group
                                    {{ request()->is('customer-r2*') ? 'text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                                <div class="bg-white w-10"></div>
                                <div
                                    class="{{ request()->is('customer-r2*') ? 'bg-button-main text-white tracking-wide font-extrabold shadow-sm' : '' }} flex items-center w-full px-4 py-2 rounded-lg">
                                    <span x-show="!effectiveSidebarCollapsed" x-cloak class="text-sm">Pelanggan R1/R2</span>
                                </div>
                            </a>
                        </li>

                        {{-- Laporan Keuangan --}}
                        @if (auth()->user() && auth()->user()->isOwner())
                            <li>
                                <a href="{{ url('laporan-keuangan') }}"
                                    class="flex items-stretch flex-row transition-colors duration-200 rounded-lg group
                                        {{ request()->is('laporan-keuangan*') ? 'text-white' : 'text-gray-500 hover:bg-gray-100' }}">
                                    <div class="bg-white w-10"></div>
                                    <div
                                        class="{{ request()->is('laporan-keuangan*') ? 'bg-button-main text-white tracking-wide font-extrabold shadow-sm' : '' }} flex items-center w-full px-4 py-2 rounded-lg">
                                        <span x-show="!effectiveSidebarCollapsed" x-cloak class="text-sm">Laporan Keuangan</span>
                                    </div>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>

                {{-- Riwayat Aktivitas --}}
                <li>
                    <a href="{{ url('riwayat-aktivitas') }}"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                            {{ request()->is('riwayat-aktivitas*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        <div
                            class="{{ request()->is('riwayat-aktivitas*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            class="{{ request()->is('riwayat-aktivitas*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span x-show="!effectiveSidebarCollapsed" x-cloak class="ml-3 text-sm">Riwayat Aktivitas</span>
                        </div>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    {{-- Bottom menu --}}
    <div>
        <div class="w-full h-0.5 bg-gray-200 ml-2"></div>

        <nav>
            <ul class="space-y-2">
                <li>
                    <a href="{{ route('cashier') }}"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg text-gray-600 hover:bg-gray-100">
                        <div class="bg-transparent w-2 rounded-l-lg"></div>
                        <div class="bg-white w-3"></div>
                        <div class="bg-transparent flex items-center w-full px-4 py-3 rounded-lg">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span x-show="!effectiveSidebarCollapsed" x-cloak class="ml-3">Kasir</span>
                        </div>
                    </a>
                </li>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="flex items-stretch flex-row w-full rounded-lg text-gray-600 hover:bg-red-100 hover:text-red-600 transition-colors duration-200">
                            <div class="bg-transparent w-2 rounded-l-lg"></div>
                            <div class="bg-white w-3"></div>
                            <div class="flex items-center w-full px-4 py-3 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                </svg>
                                <span x-show="!effectiveSidebarCollapsed" x-cloak class="ml-3">Logout</span>
                            </div>
                        </a>
                    </form>
                </li>
            </ul>

            </ul>
        </nav>
    </div>
</aside>
