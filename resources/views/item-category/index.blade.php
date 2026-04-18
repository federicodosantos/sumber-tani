<x-app-layout>
  <div class="py-6 flex justify-center items-start min-h-screen font-mont">
    <div class="mx-auto w-full sm:px-6 lg:px-8">
      <div class="mb-4 flex justify-start">
        <x-button.add-button @click="$dispatch('open-modal', 'create-item-category')" class="cursor-pointer">
          <x-slot name="icon">
            <img src="{{ asset('icon/add-icon.svg') }}" alt="Add Icon" class="h-5 w-5">
          </x-slot>
          <span class="font-bold">TAMBAH KATEGORI<span>
        </x-button.add-button>
      </div>

      <x-modal name="create-item-category" title="TAMBAH KATEGORI BARU" maxWidth="2xl" 
        x-init="if ($errors->any()) $dispatch('open-modal', 'create-item-category')">
        <div class="p-1">
          @include('item-category._form', ['action' => route('item-category.store'), 'method' => 'POST'])
        </div>
      </x-modal>

      <x-content.data-table>
        <x-slot name="sortOptions">
          <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama (A → Z)</option>
          <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama (Z → A)</option>
          <option value="date_new" {{ request('sort') == 'date_new' ? 'selected' : '' }}>Tanggal Terbaru</option>
          <option value="date_old" {{ request('sort') == 'date_old' ? 'selected' : '' }}>Tanggal Terlama</option>
        </x-slot>

        <x-slot name="header">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            NO.
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Nama Kategori
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Deskripsi Kategori
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Dibuat Pada
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Diubah Pada
          </th>

          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Action
          </th>
        </x-slot>

        <x-slot name="body">
          @forelse($categories as $i => $category)
            <tr class="hover:bg-gray-50/50">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ ($categories->currentPage() - 1) * $categories->perPage() + $i + 1 }}
              </td>

              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                {{ $category->name }}
              </td>
              <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                <span class="line-clamp-2">
                  {{ $category->description ?? '-' }}
                </span>
              </td>
              <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                <span class="line-clamp-2">
                  {{ $category->created_at->translatedFormat('l, d M Y | H:i') }}
                </span>
              </td>
              <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                <span class="line-clamp-2">
                  {{ $category->updated_at->translatedFormat('l, d M Y | H:i') }}
                </span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                <div class="flex items-center gap-3">
                  <a href="{{ route('item-category.edit', $category->id) }}" class="text-blue-600 hover:text-blue-800"
                    title="Edit">
                    <img src="{{ asset('update-button.svg') }}" alt="Edit" class="inline h-5 w-5">
                  </a>

                  <x-delete :module="'kategori'" :name="$category->name" :action="route('item-category.destroy', $category->id)" />

                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                Belum ada data kategori.
              </td>
            </tr>
          @endforelse
        </x-slot>
        <x-slot name="showing">
          Showing
          <span class="font-medium"> {{ $categories->count() }}</span> data of
          <span class="font-medium">{{ $categories->count() }}</span> entries

        </x-slot>
        <x-slot name="pagination">
          {{ $categories->onEachSide(1)->links() }}
        </x-slot>
      </x-content.data-table>
    </div>
  </div>
</x-app-layout>
