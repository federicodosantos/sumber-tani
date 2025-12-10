@php
  use Illuminate\Support\Str;
@endphp

<x-app-layout>
  <div class="py-6 flex justify-center font-mont">
    <div class="mx-auto w-full sm:px-6 lg:px-8">
      <div class="mb-4 flex items-center justify-between">
        <div class="flex flex-col">
          <h1 class="text-2xl font-bold text-gray-800">Detail Transaksi | #{{ $transaction->id }}</h1>
          <p>Jam {{ $transaction->created_at->format('H:i:s, d F Y') }}</p>
        </div>
        <a href="{{ route('finance.index') }}"
          class="inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-medium text-white hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-button-hover focus:ring-offset-2 active:scale-95 transition-transform">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12l7.5-7.5M3 12h18" />
          </svg>
          Back
        </a>
      </div>

      <x-content.data-table :search="false">
        <x-slot name="header">
          <h1 class="p-5 text-sm uppercase">Total Transaksi: 
            <span class="font-semibold">Rp {{ number_format($transaction->total_price, 0, ',', '.') }}</span></h1>

          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Nama Produk
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Harga Produk
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Jumlah
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
            Subtotal
          </th>
        </x-slot>

        <x-slot name="body">
          @forelse ($transactionDetails as $details)
            <tr class="border-b last:border-0 hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ $details->product->name }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                Rp {{ number_format($details->product_price, 0, ',', '.') }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ $details->quantity }} pcs
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                Rp {{ number_format($details->total_price, 0, ',', '.') }}
              </td>
            </tr>
          
        @endforeach
        </x-slot>
      </x-content.data-table>

    </div>
</x-app-layout>
