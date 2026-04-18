@props(['customer', 'totalDebt', 'isModal' => false])

<form action="{{ route('customer-r2.process', $customer->id) }}" method="POST"
    x-data="{ 
        paymentMethod: '{{ old('payment_method', 'Cash') }}',
        methodLabel: {
            'Cash': 'CASH / TUNAI',
            'Transfer': 'TRANSFER BANK',
            'QRIS': 'QRIS'
        },
        methodIcon: {
            'Cash': '💵',
            'Transfer': '💳',
            'QRIS': '📱'
        }
    }">
    @csrf

    <x-input-rupiah name="amount" label="Nominal Pembayaran" placeholder="0" />

    {{-- Payment Method Selection --}}
    <div class="mb-6">
        <label class="mb-1.5 block text-xs font-bold text-gray-600">Metode Pembayaran</label>
        <input type="hidden" name="payment_method" :value="paymentMethod">
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            {{-- Cash Button --}}
            <button type="button" @click="paymentMethod = 'Cash'"
                class="cursor-pointer flex items-center justify-center gap-3 rounded-xl border-2 p-3 transition-all"
                :class="paymentMethod === 'Cash' ? 'border-button-hover bg-green-50 text-green-700' : 'border-gray-100 bg-gray-50 text-gray-400 hover:border-gray-200'">
                <span class="text-xl">💵</span>
                <span class="text-xs font-bold">CASH</span>
            </button>

            {{-- Transfer Button --}}
            <button type="button" @click="paymentMethod = 'Transfer'"
                class="cursor-pointer flex items-center justify-center gap-3 rounded-xl border-2 p-3 transition-all"
                :class="paymentMethod === 'Transfer' ? 'border-button-hover bg-blue-50 text-blue-700' : 'border-gray-100 bg-gray-50 text-gray-400 hover:border-gray-200'">
                <span class="text-xl">💳</span>
                <span class="text-xs font-bold">TRANSFER</span>
            </button>

            {{-- QRIS Button --}}
            <button type="button" @click="paymentMethod = 'QRIS'"
                class="cursor-pointer flex items-center justify-center gap-3 rounded-xl border-2 p-3 transition-all"
                :class="paymentMethod === 'QRIS' ? 'border-button-hover bg-purple-50 text-purple-700' : 'border-gray-100 bg-gray-50 text-gray-400 hover:border-gray-200'">
                <span class="text-xl">📱</span>
                <span class="text-xs font-bold">QRIS</span>
            </button>
        </div>
        
        @error('payment_method')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    {{-- Info Box --}}
    <div class="mb-6 rounded-lg bg-blue-50 border border-blue-200 p-4">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-blue-500 shrink-0 mt-0.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <p class="text-[10px] leading-relaxed text-blue-700">
                Sistem <strong>FIFO</strong> akan melunasi invoice tertua terlebih dahulu. Metode pembayaran yang dipilih adalah <span class="font-bold underline" x-text="methodLabel[paymentMethod]"></span>.
            </p>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center cursor-pointer justify-between border-t border-gray-200 pt-6">
        @if($isModal)
            <x-button.remove-button x-on:click="$dispatch('close-modal', 'pay-debt')" type="button" class="cursor-pointer justify-center min-w-[100px]">
                <span class="cursor-pointer text-gray-800 font-semibold ">BATAL</span>
            </x-button.remove-button>
        @else
            <x-button.remove-button href="{{ route('customer-r2.show', $customer->id) }}" class="cursor-pointer justify-center min-w-[100px]">
                <span class="cursor-pointer text-gray-800 font-semibold ">BATAL</span>
            </x-button.remove-button>
        @endif

        <x-button.add-button type="submit" class="justify-center cursor-pointer shadow-lg">
            <x-slot name="icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-gray-800">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
            </x-slot>
            <span class="cursor-pointer text-gray-800 font-semibold ">PROSES PEMBAYARAN</span>
        </x-button.add-button>
    </div>
</form>
