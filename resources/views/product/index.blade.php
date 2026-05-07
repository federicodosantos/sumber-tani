<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            <!-- Header dengan button -->
            <div class="mb-4 flex justify-start">
                <x-button.add-button @click="$dispatch('open-modal', 'create-product')" class="w-full sm:w-auto cursor-pointer">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add Icon" class="h-5 w-5">
                    </x-slot>
                    <span class="font-bold">TAMBAH PRODUK</span>
                </x-button.add-button>
            </div>

            <x-modal name="create-product" title="TAMBAH PRODUK BARU" maxWidth="4xl" 
                x-init="if ($errors->any()) $dispatch('open-modal', 'create-product')">
                <div class="p-1">
                    @include('product._form', ['action' => route('product.store'), 'method' => 'POST', 'categories' => $categories])
                </div>
            </x-modal>

            <x-content.data-table>
                <x-slot name="sortOptions">
                    <option value="product_code_asc" {{ request('sort') == 'product_code_asc' ? 'selected' : '' }}>Kode Produk (A → Z)</option>
                    <option value="product_code_desc" {{ request('sort') == 'product_code_desc' ? 'selected' : '' }}>Kode Produk (Z → A)</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama (A → Z)</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama (Z → A)</option>
                    <option value="date_new" {{ request('sort') == 'date_new' ? 'selected' : '' }}>Tanggal Terbaru</option>
                    <option value="date_old" {{ request('sort') == 'date_old' ? 'selected' : '' }}>Tanggal Terlama</option>
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
                                {{ $product->code_id }}
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
                                    <button @click="$dispatch('open-modal', 'edit-product-{{ $product->id }}')" class="text-blue-600 hover:text-blue-800 cursor-pointer"
                                        title="Edit">
                                        <img src="{{ asset('update-button.svg') }}" alt="Edit" class="inline h-5 w-5">
                                    </button>

                                    <x-delete :module="'produk'" :name="$product->name" :action="route('product.destroy', $product->id)" />
                                </div>
                            </td>
                        </tr>

                        <x-modal name="edit-product-{{ $product->id }}" title="EDIT PRODUK" maxWidth="4xl">
                            <div class="p-1">
                                @include('product._form', [
                                    'action' => route('product.update', $product->id),
                                    'method' => 'POST',
                                    'product' => $product,
                                    'categories' => $categories,
                                    'isEdit' => true,
                                    'editModalName' => 'edit-product-' . $product->id
                                ])
                            </div>
                        </x-modal>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                                Belum ada data produk.
                            </td>
                        </tr>
                    @endforelse
                </x-slot>
                
                <x-slot name="showing">
                    Showing
                    <span class="font-medium">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</span> data of
                    <span class="font-medium">{{ $products->total() }}</span> entries
                </x-slot>
                
                <x-slot name="pagination">
                    {{ $products->onEachSide(1)->links() }}
                </x-slot>
            </x-content.data-table>
        </div>
    </div>
</x-app-layout>