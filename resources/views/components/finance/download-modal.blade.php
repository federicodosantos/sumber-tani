@props(['action' => '#', 'products' => [], 'categories' => []])

<div x-data="{ open: false }">
  <button class="bg-button-main py-2 px-4 rounded-lg text-white font-bold hover:bg-button-hover transition-all duration-200 ease-in-out cursor-pointer active:scale-95"
          @click="open = true">
    Download Laporan Keuangan
  </button>
  
  <div x-show="open" 
       x-cloak 
       x-transition.opacity 
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
       @click.self="open = false">
    
    <div class="bg-white w-full max-w-4xl mx-4 p-6 rounded-lg shadow-xl max-h-[90vh] overflow-y-auto"
         x-data="{
             rangeType: '7days',
             customDate: false,
             startDate: '',
             endDate: '',
             downloadBy: 'category',
             selectedItems: [],
             
             get isCustomDate() {
                 return this.rangeType === 'custom';
             },
             
             get availableItems() {
                 return this.downloadBy === 'product' ? {{ Js::from($products) }} : {{ Js::from($categories) }};
             },
             
             handleRangeChange() {
                 this.customDate = this.rangeType === 'custom';
                 if (!this.customDate) {
                     this.startDate = '';
                     this.endDate = '';
                 }
             },
             
             handleDownloadByChange() {
                 this.selectedItems = [];
             },
             
             toggleSelectAll() {
                 if (this.selectedItems.length === this.availableItems.length) {
                     this.selectedItems = [];
                 } else {
                     this.selectedItems = this.availableItems.map(item => item.id);
                 }
             },
             
             get isAllSelected() {
                 return this.selectedItems.length === this.availableItems.length && this.availableItems.length > 0;
             }
         }"
         @click.away="open = false">
      
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-800">Download Laporan</h2>
        <button @click="open = false" 
                class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form :action="action" method="POST" class="space-y-5">
        @csrf
        
        {{-- Range Waktu --}}
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Range Waktu</label>
          <select x-model="rangeType" 
                  name="range_type"
                  @change="handleRangeChange()"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-button-hover focus:border-button-hover transition-all">
            <option value="7days">7 Hari Terakhir</option>
            <option value="1month">1 Bulan Terakhir</option>
            <option value="3months">3 Bulan Terakhir</option>
            <option value="6months">6 Bulan Terakhir</option>
            <option value="1year">1 Tahun Terakhir</option>
            <option value="custom">Pilih Manual</option>
          </select>
        </div>

        {{-- Custom Date Range --}}
        <div x-show="isCustomDate" x-transition x-cloak
             class="space-y-3 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tanggal Mulai</label>
              <input type="date" x-model="startDate" name="start_date"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-button-hover focus:border-button-hover transition-all"
                     :required="isCustomDate">
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tanggal Akhir</label>
              <input type="date" x-model="endDate" name="end_date"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-button-hover focus:border-button-hover transition-all"
                     :required="isCustomDate" :min="startDate">
            </div>
          </div>
        </div>

        {{-- Download Berdasarkan --}}
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-3">Download Berdasarkan</label>
          <div class="grid grid-cols-2 gap-3">
            <label class="flex items-center p-3 border-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-all"
                   :class="downloadBy === 'category' ? 'bg-indigo-50 border-button-main' : 'border-gray-200'">
              <input type="radio" 
                     name="download_by"
                     x-model="downloadBy" 
                     value="category"
                     @change="handleDownloadByChange()"
                     class="w-4 h-4 text-button-main focus:ring-button-hover">
              <div class="ml-3">
                <span class="text-sm font-medium text-gray-900">Kategori</span>
                <p class="text-xs text-gray-500">Laporan per kategori</p>
              </div>
            </label>

            <label class="flex items-center p-3 border-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-all"
                   :class="downloadBy === 'product' ? 'bg-indigo-50 border-button-main' : 'border-gray-200'">
              <input type="radio" 
                     name="download_by"
                     x-model="downloadBy" 
                     value="product"
                     @change="handleDownloadByChange()"
                     class="w-4 h-4 text-button-main focus:ring-button-hover">
              <div class="ml-3">
                <span class="text-sm font-medium text-gray-900">Produk</span>
                <p class="text-xs text-gray-500">Laporan per produk</p>
              </div>
            </label>
          </div>
        </div>

        {{-- Pilih Items (Produk atau Kategori) --}}
        <div class="border-t pt-4">
          <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-semibold text-gray-700">
              Pilih <span x-text="downloadBy === 'product' ? 'Produk' : 'Kategori'"></span>
              <span class="text-xs font-normal text-gray-500 ml-1" x-show="selectedItems.length > 0">
                (<span x-text="selectedItems.length"></span> dipilih)
              </span>
            </label>
            <button type="button" 
                    @click="toggleSelectAll()"
                    class="text-xs font-semibold text-button-main hover:text-button-hover transition-colors">
              <span x-text="isAllSelected ? 'Batalkan Semua' : 'Pilih Semua'"></span>
            </button>
          </div>

          {{-- Grid Checkboxes --}}
          <div class="max-h-64 overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
              <template x-for="item in availableItems" :key="item.id">
                <label class="flex items-center p-2.5 bg-white border border-gray-200 rounded-lg hover:bg-indigo-50 hover:border-indigo-300 cursor-pointer transition-all"
                       :class="selectedItems.includes(item.id) ? 'bg-indigo-50 border-indigo-400' : ''">
                  <input type="checkbox" 
                         :name="downloadBy === 'product' ? 'product_ids[]' : 'category_ids[]'"
                         :value="item.id"
                         x-model="selectedItems"
                         class="w-4 h-4 text-button-main focus:ring-button-hover rounded">
                  <span class="ml-2 text-sm text-gray-700 truncate" 
                        x-text="item.name"
                        :title="item.name"></span>
                </label>
              </template>
            </div>

            {{-- Empty State --}}
            <div x-show="availableItems.length === 0" class="text-center py-8 text-gray-500">
              <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
              </svg>
              <p class="text-sm">
                Tidak ada <span x-text="downloadBy === 'product' ? 'produk' : 'kategori'"></span> tersedia
              </p>
            </div>
          </div>

          {{-- Validation Message --}}
          <p class="mt-2 text-xs text-red-600" x-show="selectedItems.length === 0">
            * Pilih minimal 1 item untuk melanjutkan
          </p>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col-reverse sm:flex-row gap-3 pt-4 border-t border-gray-200">
          <button type="button" @click="open = false"
                  class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            Batal
          </button>
          
          <button type="submit"
                  :disabled="selectedItems.length === 0"
                  :class="selectedItems.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105 active:scale-95'"
                  class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white bg-button-main hover:bg-button-hover rounded-lg transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download Laporan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>