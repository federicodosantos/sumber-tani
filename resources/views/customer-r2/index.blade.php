<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8">
            <!-- Header dengan button -->
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <x-button.add-button @click="$dispatch('open-modal', 'create-customer')" class="w-full sm:w-auto cursor-pointer">
                    <x-slot name="icon">
                        <img src="{{ asset('icon/add-icon.svg') }}" alt="Add Icon" class="h-5 w-5">
                    </x-slot>
                    <span class="font-bold">TAMBAH PELANGGAN</span>
                </x-button.add-button>

                @php
                    $activeType = $type ?? 'all';
                    $tabs = ['all' => 'Semua', 'r1' => 'Pelanggan R1', 'r2' => 'Pelanggan R2'];
                @endphp
                <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm">
                    @foreach ($tabs as $value => $label)
                        @php
                            $params = array_filter([
                                'type' => $value === 'all' ? null : $value,
                                'search' => request('search'),
                                'sort' => request('sort'),
                            ], fn($v) => !is_null($v) && $v !== '');
                        @endphp
                        <a href="{{ route('customer-r2.index', $params) }}"
                            class="rounded-lg px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition-colors
                                {{ $activeType === $value ? 'bg-button-main text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <x-modal name="create-customer" title="TAMBAH PELANGGAN BARU" maxWidth="4xl" 
                x-init="if ($errors->any()) $dispatch('open-modal', 'create-customer')">
                <div class="p-1">
                    @include('customer-r2._form', ['action' => route('customer-r2.store'), 'method' => 'POST'])
                </div>
            </x-modal>

            <x-content.data-table>
                <x-slot name="sortOptions">
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Nama (A → Z)</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Nama (Z → A)
                    </option>
                    <option value="date_new" {{ request('sort') == 'date_new' ? 'selected' : '' }}>Tanggal Terbaru
                    </option>
                    <option value="date_old" {{ request('sort') == 'date_old' ? 'selected' : '' }}>Tanggal Terlama
                    </option>
                </x-slot>

                <x-slot name="header">
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Nama
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Tipe
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Alamat
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Kontak
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
                        Action
                    </th>
                </x-slot>

                <x-slot name="body">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50/50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $customer->name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                @php $isR1 = $customer->type === 'r1'; @endphp
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-black uppercase tracking-wider
                                    {{ $isR1 ? 'bg-sky-100 text-sky-700 ring-1 ring-sky-200' : 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' }}">
                                    {{ strtoupper($customer->type) }}
                                </span>
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm text-gray-700">
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->address) }}" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-1.5 hover:text-blue-600 transition-colors group">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400 group-hover:text-blue-500 transition-colors">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <span class="line-clamp-2">{{ $customer->address }}</span>
                                </a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                @if($customer->phone_number)
                                    @php
                                        $whatsappNumber = preg_replace('/[^0-9]/', '', $customer->phone_number);
                                        if (str_starts_with($whatsappNumber, '0')) {
                                            $whatsappNumber = '62' . substr($whatsappNumber, 1);
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" 
                                       class="inline-flex items-center gap-2 text-gray-700 hover:text-green-600 transition-colors group">
                                        <div class="p-1.5 rounded-full bg-gray-100 group-hover:bg-green-100 group-hover:text-green-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 1-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                            </svg>
                                        </div>
                                        <span class="font-medium">{{ $customer->phone_number }}</span>
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('customer-r2.show', $customer->id) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-button-main transition-colors cursor-pointer"
                                        title="Lihat detail pelanggan">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </a>

                                    <button type="button"
                                        title="Hapus pelanggan"
                                        @click="$dispatch('open-delete-customer-modal', { id: '{{ $customer->id }}', name: '{{ addslashes($customer->name) }}', debt: {{ $customer->total_debt ?? 0 }} })"
                                        class="inline-flex items-center rounded-lg px-2 py-1.5 text-xs font-semibold text-red-500 hover:bg-red-50 transition-colors cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                                Belum ada data pelanggan.
                            </td>
                        </tr>
                    @endforelse
                </x-slot>

                <x-slot name="showing">
                    Showing
                    <span
                        class="font-medium">{{ $customers->firstItem() ?? 0 }}-{{ $customers->lastItem() ?? 0 }}</span>
                    data of
                    <span class="font-medium">{{ $customers->total() }}</span> entries
                </x-slot>

                <x-slot name="pagination">
                    {{ $customers->onEachSide(1)->links() }}
                </x-slot>
            </x-content.data-table>
        </div>
    </div>

    {{-- Confirm Delete Customer Modal --}}
    <div x-data="{ deleteId: '', deleteName: '', deleteDebt: 0 }"
         @open-delete-customer-modal.window="deleteId = $event.detail.id; deleteName = $event.detail.name; deleteDebt = Number($event.detail.debt || 0); $dispatch('open-modal', 'delete-customer-modal')">
        <x-modal name="delete-customer-modal" title="HAPUS PELANGGAN" maxWidth="md">
            <div class="p-2">
                <p class="text-sm text-gray-600">
                    Yakin ingin menghapus pelanggan
                    <span class="font-semibold text-gray-900" x-text="`&quot;${deleteName}&quot;`"></span>?
                </p>

                <template x-if="deleteDebt > 0">
                    <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                        <div>
                            <span class="font-bold block text-amber-900">Pelanggan Memiliki Hutang Aktif!</span>
                            Pelanggan ini masih memiliki tunggakan hutang sebesar <strong x-text="'Rp ' + deleteDebt.toLocaleString('id-ID')"></strong>. Pelanggan tidak dapat dihapus sebelum semua hutang dilunasi.
                        </div>
                    </div>
                </template>

                <template x-if="deleteDebt <= 0">
                    <p class="mt-1 text-xs text-gray-500">
                        Data transaksi dan invoice pelanggan ini akan tetap tersimpan dan tidak akan dihapus.
                    </p>
                </template>

                @if ($errors->has('general'))
                    <div class="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700">
                        {{ $errors->first('general') }}
                    </div>
                @endif

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button"
                        @click="$dispatch('close-modal', 'delete-customer-modal')"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors cursor-pointer">
                        Batal
                    </button>

                    <form method="POST" :action="`{{ url('customer-r2') }}/${deleteId}`">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            :disabled="deleteDebt > 0"
                            :class="deleteDebt > 0 ? 'bg-gray-300 text-gray-400 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700 cursor-pointer'"
                            class="rounded-lg px-4 py-2 text-xs font-semibold transition-colors">
                            Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </x-modal>
    </div>
</x-app-layout>
