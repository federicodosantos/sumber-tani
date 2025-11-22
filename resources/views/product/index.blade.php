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
          <span class="font-medium"> {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</span> data of
          <span class="font-medium">{{ $products->total() }}</span> entries

        </x-slot>
        <x-slot name="pagination">
          {{ $products->onEachSide(1)->links() }}
        </x-slot>
      </x-content.data-table>
    </div>
  </div>
</x-app-layout>
