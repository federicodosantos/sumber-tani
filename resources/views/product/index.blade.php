<x-app-layout>
    <div class="py-2 flex justify-center items-center max-h-screen font-mont">
    <div class="mx-auto w-full sm:px-6 lg:px-8">
      <div class="mb-4 flex justify-start">
        <x-button.add-button href="product/create">
          <x-slot name="icon">
            <img src="{{ asset('icon/add-icon.svg') }}" alt="Add Icon" class="h-5 w-5">
          </x-slot>
          <span class="font-bold">ADD PRODUCT</span>
        </x-button.add-button>
      </div>

      <x-content.data-table>
        <x-slot name="sortOptions">
          <option>Name</option>
          <option>Tanggal</option>
        </x-slot>
        <x-slot name="header">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Kode Produk
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Nama Produk
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Deskripsi Produk
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Kategori Produk
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
          @forelse($products as $product)
            <tr class="hover:bg-gray-50/50">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ $product->id }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                {{ $product->name }}
              </td>
              <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                <span class="line-clamp-2">
                  {{ $product->description ?? '-' }}
                </span>
              </td>
              <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                <span class="line-clamp-2">
                  {{ $product->category->name ?? '-' }}
                </span>
              </td>
              <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                <span class="line-clamp-2">
                  {{ $product->created_at->translatedFormat('l, d M Y | H:i') }}
                </span>
              </td>
              <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                <span class="line-clamp-2">
                  {{ $product->updated_at->translatedFormat('l, d M Y | H:i') }}
                </span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                <div class="flex items-center gap-3">
                  <a href="{{ route('product.edit', $product->id) }}" class="text-blue-600 hover:text-blue-800"
                    title="Edit">
                    <img src="{{ asset('update-button.svg') }}" alt="Edit" class="inline h-5 w-5">
                  </a>

                  <x-delete :module="'produk'" :name="$product->name" :action="route('product.destroy', $product->id)" />

                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                No products found.
              </td>
            </tr>
          @endforelse
        </x-slot>
        <x-slot name="showing">
          Showing
          <span class="font-medium"> {{ $products->count() }}</span> data of
          <span class="font-medium">{{ $products->count() }}</span> entries

        </x-slot>
        <x-slot name="pagination">
          <a href="#"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-sm text-gray-500 hover:bg-gray-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd"
                d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z"
                clip-rule="evenodd" />
            </svg>
          </a>

          {{-- Active Page --}}
          <a href="#"
            class="border-button-main bg-button-main inline-flex h-8 w-8 items-center justify-center rounded-md border text-sm font-medium text-white"
            aria-current="page">
            1
          </a>
          <a href="#"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
            2
          </a>
          <a href="#"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
            3
          </a>
          <a href="#"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
            4
          </a>
          <span class="inline-flex h-8 w-8 items-center justify-center text-sm text-gray-500">
            ...
          </span>
          <a href="#"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50">
            26
          </a>
          <a href="#"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white text-sm text-gray-500 hover:bg-gray-50">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd"
                d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z"
                clip-rule="evenodd" />
            </svg>
          </a>
        </x-slot>
      </x-content.data-table>
    </div>
  </div>
</x-app-layout>
