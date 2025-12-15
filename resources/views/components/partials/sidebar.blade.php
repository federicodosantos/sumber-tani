{{-- Backdrop blur untuk mobile --}}
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:enter="transition-all ease-linear duration-300"
    x-transition:enter-start="opacity-0 backdrop-blur-none" x-transition:enter-end="opacity-100 backdrop-blur-sm"
    x-transition:leave="transition-all ease-linear duration-300" x-transition:leave-start="opacity-100 backdrop-blur-sm"
    x-transition:leave-end="opacity-0 backdrop-blur-none"
    class="fixed inset-0 z-40 bg-black/20 backdrop-blur-sm lg:hidden" x-cloak>
</div>

{{-- Sidebar --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="w-72 h-screen bg-white py-6 pr-6 shadow-xl flex flex-col justify-between fixed top-0 left-0 font-nunito font-semibold z-50 transition-transform duration-300 ease-in-out lg:translate-x-0">

    {{-- Close button untuk mobile --}}
    <button @click="sidebarOpen = false"
        class="lg:hidden absolute top-4 right-4 p-2 rounded-lg text-gray-600 hover:bg-gray-100">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <div>
        <div class="mb-8 flex justify-center">
            <img src="{{ asset('images/logo-horizontal.svg') }}" alt="Sumber Tani" class="h-10">
        </div>

        <nav>
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
                            <span class="ml-3">Dashboard</span>
                        </div>
                    </a>
                </li>

                <li>
                    <a href="{{ url('product') }}"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                            {{ request()->is('product*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        <div
                            class="{{ request()->is('product*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            class="{{ request()->is('product*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                            <span class="ml-3">Produk</span>
                        </div>
                    </a>
                </li>

                <li>
                    <a href="{{ url('purchase') }}"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                            {{ request()->is('purchase*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        <div
                            class="{{ request()->is('purchase*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            class="{{ request()->is('purchase*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="1.5" viewBox="0 0 64 64"
                                stroke="currentColor" class="w-6 h-6">
                                <path
                                    d="M29.38,9.97H9.81a2.006,2.006,0,0,0-2,2v4.47a2.006,2.006,0,0,0,2,2H29.38a2,2,0,0,0,2-2V11.97A2,2,0,0,0,29.38,9.97Zm0,6.47H9.81V11.97H29.38Z M10.97,21.83a3.16,3.16,0,1,0,3.16,3.16A3.167,3.167,0,0,0,10.97,21.83Zm0,4.32a1.16,1.16,0,1,1,1.16-1.16A1.161,1.161,0,0,1,10.97,26.15Z M19.6,21.83a3.16,3.16,0,1,0,3.15,3.16A3.167,3.167,0,0,0,19.6,21.83Zm0,4.32a1.16,1.16,0,1,1,1.15-1.16A1.161,1.161,0,0,1,19.6,26.15Z
                                        M28.23,21.83a3.16,3.16,0,1,0,3.15,3.16A3.16,3.16,0,0,0,28.23,21.83Zm0,4.32a1.16,1.16,0,1,1,1.15-1.16A1.161,1.161,0,0,1,28.23,26.15Z
                                        M10.97,30.46a3.155,3.155,0,1,0,3.16,3.16A3.167,3.167,0,0,0,10.97,30.46Zm0,4.31a1.155,1.155,0,1,1,1.16-1.15A1.159,1.159,0,0,1,10.97,34.77Z
                                        M19.6,30.46a3.155,3.155,0,1,0,3.15,3.16A3.167,3.167,0,0,0,19.6,30.46Zm0,4.31a1.155,1.155,0,1,1,1.15-1.15A1.159,1.159,0,0,1,19.6,34.77Z
                                        M28.23,30.46a3.155,3.155,0,1,0,3.15,3.16A3.16,3.16,0,0,0,28.23,30.46Zm0,4.31a1.155,1.155,0,1,1,1.15-1.15A1.159,1.159,0,0,1,28.23,34.77Z
                                        M10.97,39.09a3.155,3.155,0,1,0,3.16,3.16A3.16,3.16,0,0,0,10.97,39.09Zm0,4.31a1.155,1.155,0,1,1,1.16-1.15A1.159,1.159,0,0,1,10.97,43.4Z
                                        M19.6,39.09a3.155,3.155,0,1,0,3.15,3.16A3.16,3.16,0,0,0,19.6,39.09Zm0,4.31a1.155,1.155,0,1,1,1.15-1.15A1.159,1.159,0,0,1,19.6,43.4Z
                                        M10.97,47.72a3.155,3.155,0,1,0,3.16,3.15A3.158,3.158,0,0,0,10.97,47.72Zm0,4.31a1.155,1.155,0,1,1,1.16-1.16A1.161,1.161,0,0,1,10.97,52.03Z
                                        M19.6,47.72a3.155,3.155,0,1,0,3.15,3.15A3.158,3.158,0,0,0,19.6,47.72Zm0,4.31a1.155,1.155,0,1,1,0-2.31,1.155,1.155,0,0,1,0,2.31Z
                                        M29.38,39.09H27.07a2.006,2.006,0,0,0-2,2V52.03a2.006,2.006,0,0,0,2,2h2.31a2,2,0,0,0,2-2V41.09A2,2,0,0,0,29.38,39.09Zm0,12.94H27.07V41.09h2.31Z
                                        M56.26,11.05H35.7V9.58a5.008,5.008,0,0,0-5-5H8.5a5,5,0,0,0-5,5V54.42a5,5,0,0,0,5,5H30.7a5.008,5.008,0,0,0,5-5V52.95H56.26a4.24,4.24,0,0,0,4.24-4.23V15.28A4.24,4.24,0,0,0,56.26,11.05ZM33.7,54.42a3,3,0,0,1-3,3H8.5a3,3,0,0,1-3-3V9.58a3,3,0,0,1,3-3H30.7a3,3,0,0,1,3,3Zm6.47-3.47H35.7V13.05h4.47ZM58.5,48.72a2.234,2.234,0,0,1-2.24,2.23H42.17V13.05H56.26a2.234,2.234,0,0,1,2.24,2.23Z
                                        M56.19,36.31a5.855,5.855,0,0,0-11.71,0,5.768,5.768,0,0,0,1,3.24,5.737,5.737,0,0,0-1,3.23,5.855,5.855,0,1,0,11.71,0,5.737,5.737,0,0,0-1-3.23A5.768,5.768,0,0,0,56.19,36.31ZM50.33,46.64a3.86,3.86,0,0,1-3.85-3.86,3.722,3.722,0,0,1,.99-2.55v-.01l.01-.01a3.824,3.824,0,0,1,5.71,0v.01c.01,0,.01,0,.01.01a3.722,3.722,0,0,1,.99,2.55A3.862,3.862,0,0,1,50.33,46.64Zm3.44-8.59a4.889,4.889,0,0,0-.69-.42,1.618,1.618,0,0,0-.18-.1c-.24-.11-.49-.22-.75-.31a6.991,6.991,0,0,0-.79-.19l-.2-.03a5.69,5.69,0,0,0-.83-.07,5.554,5.554,0,0,0-.82.07l-.2.03a6.508,6.508,0,0,0-.79.19h-.01a7.038,7.038,0,0,0-.75.32.556.556,0,0,0-.17.09,4.285,4.285,0,0,0-.68.42h-.02a3.811,3.811,0,0,1-.41-1.74,3.855,3.855,0,0,1,7.71,0A3.819,3.819,0,0,1,53.77,38.05Z" />

                            </svg>
                            <span class="ml-3">Data Pembelian</span>
                        </div>
                    </a>
                </li>

                <li>
                    <a href="{{ url('stock') }}"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                            {{ request()->is('stock*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        <div
                            class="{{ request()->is('stock*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            class="{{ request()->is('stock*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9" />
                            </svg>
                            <span class="ml-3">Stok Produk</span>
                        </div>
                    </a>
                </li>

                <li>
                    <a href="{{ url('item-category') }}"
                        class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                            {{ request()->is('item-category*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                        <div
                            class="{{ request()->is('item-category*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
                        </div>
                        <div class="bg-white w-3"></div>
                        <div
                            class="{{ request()->is('item-category*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            <span class="ml-3">Kategori Barang</span>
                        </div>
                    </a>
                </li>

                @if (auth()->user() && auth()->user()->isOwner())
                    <li>
                        <a href="{{ url('laporan-keuangan') }}"
                            class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                            {{ request()->is('laporan-keuangan*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
                            <div
                                class="{{ request()->is('laporan-keuangan*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
                            </div>
                            <div class="bg-white w-3"></div>
                            <div
                                class="{{ request()->is('laporan-keuangan*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
                                <img src="/icon/finance.svg" alt="finance icon" class="w-6 h-6">
                                <span class="ml-3">Laporan Keuangan</span>
                            </div>
                        </a>
                    </li>
                @endif

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
                            <span class="ml-3">Riwayat Aktivitas</span>
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
                        <div class="bg-transparent flex items-center w-full px-5 py-3 rounded-lg">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Kasir
                        </div>
                    </a>
                </li>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="flex items-center w-full py-3 px-4 rounded-lg text-gray-600 hover:bg-red-100 hover:text-red-600 transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                            </svg>
                            <span class="ml-3">Logout</span>
                        </a>
                    </form>
                </li>
            </ul>

            </ul>
        </nav>
    </div>
</aside>
