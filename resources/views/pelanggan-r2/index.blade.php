<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            <!-- Header dengan button -->
            <div class="mb-4 flex justify-start">
                <x-button.add-button href="pelanggan-r2/create" class="w-full sm:w-auto">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add Icon" class="h-5 w-5">
                    </x-slot>
                    <span class="font-bold">TAMBAH PELANGGAN</span>
                </x-button.add-button>
            </div>

            <x-content.data-table>
                <x-slot name="sortOptions">
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama (A → Z)</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama (Z → A)</option>
                    <option value="date_new" {{ request('sort') == 'date_new' ? 'selected' : '' }}>Tanggal Terbaru</option>
                    <option value="date_old" {{ request('sort') == 'date_old' ? 'selected' : '' }}>Tanggal Terlama</option>
                </x-slot>

                <x-slot name="header">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Nama
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Alamat
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Kontak
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Action
                    </th>
                </x-slot>

                <x-slot name="body">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $customer->name }}
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-700">
                                <span class="line-clamp-2">{{ $customer->address }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                {{ $customer->phone_number }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <a href="{{ route('pelanggan-r2.show', $customer->id) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    View Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                                Belum ada data pelanggan R2.
                            </td>
                        </tr>
                    @endforelse
                </x-slot>

                <x-slot name="showing">
                    Showing
                    <span class="font-medium">{{ $customers->firstItem() ?? 0 }}-{{ $customers->lastItem() ?? 0 }}</span> data of
                    <span class="font-medium">{{ $customers->total() }}</span> entries
                </x-slot>

                <x-slot name="pagination">
                    {{ $customers->onEachSide(1)->links() }}
                </x-slot>
            </x-content.data-table>
        </div>
    </div>
</x-app-layout>
