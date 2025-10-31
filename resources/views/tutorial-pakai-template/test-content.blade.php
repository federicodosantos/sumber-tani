<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            'Product'
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-4 flex justify-start">
                <x-button.add-button href="#">
                    <x-slot name="icon">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </x-slot>
                    ADD PRODUCT
                </x-button.add-button>
            </div>

            <x-content.data-table>
                <x-slot name="sortOptions">
                    <option>Stock</option>
                    <option>Name</option>
                    <option>Code</option>
                </x-slot>

                <x-slot name="header">
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        KODE PRODUCT
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Nama Product
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Deskripsi Produk
                    </th>

                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Action
                    </th>
                </x-slot>

                <x-slot name="body">
                    @for ($i = 0; $i < 10; $i++)
                        <tr class="hover:bg-gray-50/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                PPK-120
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                Pupuk 120
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-600">
                                <span class="line-clamp-2">
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris fringilla finibus
                                    eros
                                    sed mattis. Quisque vitae libero porttitor est iaculis porttitor.
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <div class="flex items-center gap-3">
                                    <a href="#" class="text-blue-600 hover:text-blue-800" title="Edit">
                                        <img src="{{ asset('update-button.svg') }}" alt="Edit"
                                            class="inline h-5 w-5">
                                    </a>

                                    <a href="#" class="text-red-600 hover:text-red-800" title="Delete">
                                        <img src="{{ asset('delete-button.svg') }}" alt="Delete"
                                            class="inline h-5 w-5">
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endfor
                </x-slot>
                <x-slot name="showing">
                    Showing data <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span
                        class="font-medium">256K</span> entries
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
