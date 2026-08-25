@props(['customer', 'totalDebt', 'creditBalance' => 0, 'isModal' => false])

<form action="{{ route('customer-r2.process', $customer->id) }}" method="POST"
    x-data="{
        paymentMethod: '{{ old('payment_method', 'Cash') }}',
        paymentDate: '{{ old('payment_date', now()->format('Y-m-d')) }}',
        maxDate: '{{ now()->format('Y-m-d') }}',
        totalDebt: {{ (float) $totalDebt }},
        availableCredit: {{ (float) $creditBalance }},
        currentAmount: {{ (float) old('amount', 0) }},
        creditUsed: {{ (float) old('use_credit_amount', 0) }},
        overpaymentAction: '{{ old('overpayment_action', '') }}',
        formatRp(n) { return Number(n||0).toLocaleString('id-ID'); },
        get effectivePayment() { return Number(this.currentAmount||0) + Number(this.creditUsed||0); },
        get overpayment() { return Math.max(0, this.effectivePayment - this.totalDebt); },
        get isOverpay() { return this.overpayment > 0.009; },
        get needsOverpayAction() { return this.isOverpay && !['credit','refund'].includes(this.overpaymentAction); },
        get canSubmit() {
            return this.effectivePayment > 0.009 && !this.needsOverpayAction;
        },
        useCreditBalance() {
            const use = Math.min(this.availableCredit, this.totalDebt);
            this.creditUsed = use;
            const cashNeeded = Math.max(0, this.totalDebt - use);
            this.currentAmount = cashNeeded;
            this.$dispatch('update-rupiah-value', { name: 'amount', value: String(cashNeeded) });
        },
        clearCreditUsed() {
            this.creditUsed = 0;
            this.$dispatch('update-rupiah-value', { name: 'amount', value: String(this.currentAmount) });
        },
        methodLabel: {
            'Cash': 'CASH / TUNAI',
            'Transfer': 'TRANSFER BANK',
            'QRIS': 'QRIS'
        },
        resetToOriginal() {
            this.currentAmount = 0;
            this.creditUsed = 0;
            this.overpaymentAction = '';
            this.paymentMethod = 'Cash';
            this.paymentDate = '{{ now()->format('Y-m-d') }}';
            this.$dispatch('update-rupiah-value', { name: 'amount', value: '' });
        }
    }"
    @rupiah-change="if ($event.detail.name === 'amount') currentAmount = parseFloat($event.detail.value) || 0"
    @submit="if (!canSubmit) { $event.preventDefault(); }"
    @modal-closed.window="if ($event.detail === 'pay-debt') resetToOriginal()">
    @csrf

    {{-- Hidden fields untuk kredit & overpayment action --}}
    <input type="hidden" name="use_credit_amount" :value="creditUsed">
    <input type="hidden" name="overpayment_action" :value="overpaymentAction">

    {{-- Banner: Saldo Kredit Tersedia --}}
    @if ($creditBalance > 0)
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-green-600 shrink-0 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <div>
                        <p class="text-xs font-bold text-green-800">Sisa Saldo Tersedia</p>
                        <p class="text-sm font-bold text-green-700">Rp {{ number_format($creditBalance, 0, ',', '.') }}</p>
                        <p class="text-[10px] text-green-600 mt-0.5" x-show="creditUsed > 0">
                            Menggunakan: Rp <span x-text="formatRp(creditUsed)"></span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-col gap-1.5 shrink-0">
                    <button type="button" @click="useCreditBalance()" x-show="creditUsed <= 0"
                        style="background-color:#16a34a;color:#fff;"
                        class="rounded-lg px-3 py-1.5 text-[10px] font-bold transition-colors cursor-pointer hover:opacity-90">
                        GUNAKAN SALDO
                    </button>
                    <button type="button" @click="clearCreditUsed()" x-show="creditUsed > 0"
                        style="background-color:#fff;color:#15803d;border:2px solid #16a34a;"
                        class="rounded-lg px-3 py-1.5 text-[10px] font-bold transition-colors cursor-pointer hover:opacity-80">
                        BATALKAN
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Nominal Pembayaran (Tunai) --}}
    <x-input-rupiah name="amount" label="Nominal Pembayaran Tunai" placeholder="0" containerClass="mb-4" decimals="3" />

    {{-- Ringkasan Efektif --}}
    <template x-if="creditUsed > 0">
        <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-2.5 flex items-center justify-between">
            <p class="text-xs text-blue-700">Total efektif (tunai + saldo):</p>
            <p class="text-sm font-bold text-blue-800">Rp <span x-text="formatRp(effectivePayment)"></span></p>
        </div>
    </template>

    {{-- Banner: Overpayment — pilih aksi --}}
    <template x-if="isOverpay">
        <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 p-4">
            <div class="flex items-start gap-2 mb-3">
                <svg class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <p class="text-xs font-bold text-amber-800">Kelebihan Bayar: Rp <span x-text="formatRp(overpayment)"></span></p>
                    <p class="text-[10px] text-amber-700 mt-0.5">Pilih aksi untuk kelebihan ini:</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="overpaymentAction = 'refund'"
                    class="flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2.5 text-xs font-bold transition-all cursor-pointer"
                    :class="overpaymentAction === 'refund'
                        ? 'border-orange-400 bg-orange-100 text-orange-800'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-orange-300'">
                    <span class="text-base">💵</span>
                    <span>Refund Tunai</span>
                </button>
                <button type="button" @click="overpaymentAction = 'credit'"
                    class="flex items-center justify-center gap-2 rounded-lg border-2 px-3 py-2.5 text-xs font-bold transition-all cursor-pointer"
                    :class="overpaymentAction === 'credit'
                        ? 'border-green-400 bg-green-100 text-green-800'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-green-300'">
                    <span class="text-base">🏦</span>
                    <span>Simpan Saldo</span>
                </button>
            </div>
            {{-- Konfirmasi aksi terpilih --}}
            <template x-if="overpaymentAction === 'refund'">
                <p class="mt-2 text-[10px] text-orange-700 font-medium">
                    ✓ Rp <span x-text="formatRp(overpayment)"></span> akan dicatat sebagai refund tunai kepada pelanggan.
                </p>
            </template>
            <template x-if="overpaymentAction === 'credit'">
                <p class="mt-2 text-[10px] text-green-700 font-medium">
                    ✓ Rp <span x-text="formatRp(overpayment)"></span> akan disimpan sebagai saldo kredit pelanggan.
                </p>
            </template>
        </div>
    </template>

    {{-- Date Selection --}}
    <div class="mb-6">
        <label class="mb-1.5 block text-sm font-semibold text-black">Tanggal Pembayaran</label>
        <input type="date" name="payment_date" x-model="paymentDate" :max="maxDate"
            @click="$el.showPicker()"
            onkeydown="return false"
            class="block w-full rounded-md border border-gray-300 focus:border-button-hover px-3 py-2 text-sm focus:outline-none transition-all duration-100 font-semibold text-gray-900 bg-white cursor-pointer">
        @error('payment_date')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    {{-- Payment Method Selection --}}
    <div class="mb-6">
        <label class="mb-1.5 block text-sm font-semibold text-black">Metode Pembayaran</label>
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
                <span class="cursor-pointer text-gray-800 font-semibold">BATAL</span>
            </x-button.remove-button>
        @else
            <x-button.remove-button href="{{ route('customer-r2.show', $customer->id) }}" class="cursor-pointer justify-center min-w-[100px]">
                <span class="cursor-pointer text-gray-800 font-semibold">BATAL</span>
            </x-button.remove-button>
        @endif

        <button type="submit"
            :disabled="!canSubmit"
            :class="!canSubmit
                ? 'inline-flex items-center justify-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-sm font-bold text-gray-400 cursor-not-allowed shadow-sm min-w-[180px]'
                : 'inline-flex items-center justify-center gap-2 rounded-lg bg-button-main hover:bg-button-hover px-4 py-2 text-sm font-bold text-gray-800 cursor-pointer shadow-lg min-w-[180px]'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
            </svg>
            <span class="font-semibold"
                x-text="needsOverpayAction
                    ? 'PILIH AKSI KELEBIHAN'
                    : (isOverpay
                        ? (overpaymentAction === 'refund' ? 'PROSES + REFUND' : 'PROSES + SIMPAN SALDO')
                        : (creditUsed > 0 ? 'BAYAR (KREDIT + TUNAI)' : 'PROSES PEMBAYARAN'))">
            </span>
        </button>
    </div>
</form>
