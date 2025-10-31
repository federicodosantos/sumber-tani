<aside
  class="w-72 h-screen bg-white py-6 pr-6 shadow-xl flex flex-col justify-between fixed top-0 left-0 font-nunito font-semibold">
  <div>
    <div class="mb-8 flex justify-center">
      <img src="{{ asset('images/logo-horizontal.svg') }}" alt="Sumber Tani" class="h-10">
    </div>

    <nav>
      <ul class="space-y-2">
        <li>
          <a href="{{ url('dashboard') }}"
            class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                            {{ request()->is('dashboard*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
            <div
              class="{{ request()->is('dashboard*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
            </div>
            <div class="bg-white w-3"></div>
            <div
              class="{{ request()->is('dashboard*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
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
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
              </svg>
              <span class="ml-3">Product</span>
            </div>
          </a>
        </li>

        <li>
          <a href="{{ url('product-stock') }}"
            class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                                                            {{ request()->is('product-stock*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
            <div
              class="{{ request()->is('product-stock*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
            </div>
            <div class="bg-white w-3"></div>
            <div
              class="{{ request()->is('product-stock*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9" />
              </svg>
              <span class="ml-3">Product Stock</span>
            </div>
          </a>
        </li>

        <li>
          <a href="{{ url('item-categories') }}"
            class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                                                            {{ request()->is('item-categories*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
            <div
              class="{{ request()->is('item-categories*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
            </div>
            <div class="bg-white w-3"></div>
            <div
              class="{{ request()->is('item-categories*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
              </svg>
              <span class="ml-3">Item Categories</span>
            </div>
          </a>
        </li>

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
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.75A.75.75 0 0 1 3 4.5h.75m0 0H21" />
              </svg>
              <span class="ml-3">Laporan Keuangan</span>
            </div>
          </a>
        </li>

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
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
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

  <div>
    <nav>
      <ul class="space-y-2">
        <li>
          <a href="{{ url('settings') }}"
            class="flex items-stretch flex-row transition-colors duration-200 rounded-lg
                                                            {{ request()->is('settings*') ? 'text-white font-semibold' : 'text-gray-600 hover:bg-gray-100' }}">
            <div
              class="{{ request()->is('settings*') ? 'bg-button-main rounded-r-lg' : 'bg-transparent' }} w-2 rounded-l-lg">
            </div>
            <div class="bg-white w-3"></div>
            <div
              class="{{ request()->is('settings*') ? 'bg-button-main text-white tracking-wide font-bold' : 'bg-transparent' }} flex items-center w-full px-4 py-3 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9.594 3.94c.09-.542.56-1.003 1.11-1.226.55-.223 1.156-.223 1.706 0 .55.223 1.02.684 1.11 1.226" />
              </svg>
              <span class="ml-3">Settings</span>
            </div>
          </a>
        </li>

        <li>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();"
              class="flex items-center w-full py-3 px-4 rounded-lg text-gray-600 hover:bg-red-100 hover:text-red-600 transition-colors duration-200">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
              </svg>
              <span class="ml-3">Logout</span>
            </a>
          </form>
        </li>
      </ul>
    </nav>
  </div>
</aside>
