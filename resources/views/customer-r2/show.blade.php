@php use Illuminate\Support\Number; @endphp
<x-app-layout>
    <div class="py-4 lg:py-6 flex justify-center items-start min-h-screen font-mont">
        <div class="mx-auto w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back Button --}}
            <div>
                <a href="{{ route('customer-r2.index') }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke Daftar Pelanggan
                </a>
            </div>

            {{-- Top-level Error Banner (for destroy operations / general errors not owned by a modal) --}}
            @if ($errors->any() && ! session('open_modal'))
                <div x-data="{ show: true }" x-show="show"
                     class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div class="flex-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-red-700 mb-1">Terjadi Kesalahan</p>
                            <ul class="text-xs text-red-700 space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>&bull; {{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-600 cursor-pointer">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Success Message --}}
            @if (session('success'))
                <div x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 3000)"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-green-500 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="text-sm font-medium text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            {{-- Customer Info Card --}}
            <div class="rounded-2xl bg-white p-6 sm:p-8 shadow-sm" style="border: 1px solid #e5e7eb;">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        {{-- Avatar --}}
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-7 w-7 text-blue-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>

                        {{-- Customer Details --}}
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-bold text-gray-900">{{ $customer->name }}</h2>
                                @php $isR1Type = ($customer->type ?? 'r2') === 'r1'; @endphp
                                <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider
                                    {{ $isR1Type ? 'bg-sky-100 text-sky-700 ring-1 ring-sky-200' : 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' }}">
                                    {{ strtoupper($customer->type ?? 'r2') }}
                                </span>
                                <button type="button" @click="$dispatch('open-modal', 'edit-customer')"
                                    class="ml-1 inline-flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-blue-600 cursor-pointer"
                                    title="Edit Pelanggan">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>
                                <button type="button" @click="$dispatch('open-modal', 'delete-customer-modal')"
                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 cursor-pointer"
                                    title="Hapus Pelanggan">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                            <div class="mt-2 space-y-1">
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($customer->address) }}" 
                                   target="_blank" 
                                   class="flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors group">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400 group-hover:text-blue-500 transition-colors">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    {{ $customer->address }}
                                </a>
                                @if($customer->phone_number)
                                    @php
                                        $whatsappNumber = preg_replace('/[^0-9]/', '', $customer->phone_number);
                                        if (str_starts_with($whatsappNumber, '0')) {
                                            $whatsappNumber = '62' . substr($whatsappNumber, 1);
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" 
                                       class="flex items-center gap-2 text-sm text-gray-600 hover:text-green-600 transition-colors group">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400 group-hover:text-green-500 transition-colors">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                        </svg>
                                        <span class="font-medium">{{ $customer->phone_number }}</span>
                                    </a>
                                @else
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-gray-400">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                        </svg>
                                        -
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Debt Summary --}}
                    <div class="flex flex-col sm:flex-row gap-3 sm:min-w-[300px]">
                        {{-- Total Hutang Card --}}
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Hutang</p>
                            <p class="mt-1 text-2xl font-bold {{ $totalDebt > 0 ? 'text-red-600' : 'text-green-600' }}">
                                Rp {{ Number::format((float) ($totalDebt), null, 3, 'id') }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">{{ $customer->invoices->count() }} invoice</p>

                            @if ($totalDebt > 0)
                                <button @click="$dispatch('open-modal', 'pay-debt')"
                                    class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-button-main px-4 py-3 text-xs font-bold text-gray-800 transition-colors hover:bg-button-hover cursor-pointer shadow-sm active:scale-[0.98]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                    </svg>
                                    BAYAR HUTANG
                                </button>
                            @endif
                        </div>

                        {{-- Sisa Saldo (Credit Balance) Card — only shown when credit > 0 --}}
                        @if ($creditBalance > 0)
                            <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 flex-1">
                                <p class="text-xs font-medium uppercase tracking-wider text-green-600">Sisa Saldo</p>
                                <p class="mt-1 text-2xl font-bold text-green-700">
                                    Rp {{ Number::format((float) ($creditBalance), null, 3, 'id') }}
                                </p>
                                <p class="mt-1 text-xs text-green-500">Kredit tersedia</p>
                                @if ($totalDebt > 0)
                                    <p class="mt-3 text-[10px] text-green-600 leading-relaxed">
                                        Gunakan saldo ini melalui tombol <strong>Bayar Hutang</strong>.
                                    </p>
                                @else
                                    <p class="mt-3 text-[10px] text-green-600 italic">Gunakan saat ada hutang baru</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action Buttons: Manual Entry --}}
            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('customer-r2.invoice.create', $customer->id) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-700 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                    Tambah Nota Manual
                </a>
                <button type="button" @click="$dispatch('open-modal', 'add-debt')"
                    class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-200 px-4 py-2 text-xs font-bold uppercase tracking-wider text-gray-700 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-red-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    Tambah Hutang Langsung
                </button>
            </div>

            {{-- Invoice List Section --}}
            <div x-data="{ currentType: '{{ request('type', 'all') }}' }">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 pb-4">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Invoice</h3>

                    {{-- Toggle Switch / Segmented Control --}}
                    <div class="flex items-center gap-1 rounded-xl bg-gray-100 p-1 self-start sm:self-auto">
                        <a href="{{ route('customer-r2.show', array_merge([$customer->id], request()->only(['date_from', 'date_to']))) }}"
                            class="rounded-lg px-4 py-2 text-[10px] font-bold tracking-wider transition-all cursor-pointer {{ !request('type') ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            SEMUA
                        </a>
                        <a href="{{ route('customer-r2.show', array_merge([$customer->id, 'type' => 'purchasement'], request()->only(['date_from', 'date_to']))) }}"
                            class="rounded-lg px-4 py-2 text-[10px] font-bold tracking-wider transition-all cursor-pointer {{ request('type') === 'purchasement' ? 'bg-button-main text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            PEMBELIAN
                        </a>
                        <a href="{{ route('customer-r2.show', array_merge([$customer->id, 'type' => 'debt_payment'], request()->only(['date_from', 'date_to']))) }}"
                            class="rounded-lg px-4 py-2 text-[10px] font-bold tracking-wider transition-all cursor-pointer {{ request('type') === 'debt_payment' ? 'bg-button-main text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            PEMBAYARAN
                        </a>
                    </div>
                </div>

                {{-- Date Filter Form --}}
                <div class="mb-6 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                    <form method="GET" action="{{ route('customer-r2.show', $customer->id) }}" class="flex flex-wrap items-end gap-5">
                        @if(request('type'))
                            <input type="hidden" name="type" value="{{ request('type') }}">
                        @endif

                        {{-- From Date --}}
                        <div class="flex min-w-40 flex-1 flex-col gap-1.5 sm:flex-none">
                            <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Dari Tanggal
                            </label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm transition-all focus:border-button-hover focus:bg-white focus:ring-2 focus:ring-button-main/20">
                        </div>

                        {{-- To Date --}}
                        <div class="flex min-w-[160px] flex-1 flex-col gap-1.5 sm:flex-none">
                            <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500">
                                Sampai Tanggal
                            </label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm transition-all focus:border-button-hover focus:bg-white focus:ring-2 focus:ring-button-main/20">
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-2 pt-1">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-button-main px-5 py-2.5 text-sm font-bold text-gray-800 shadow-sm transition-all hover:bg-button-hover cursor-pointer hover:shadow-md active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Cari
                            </button>

                            <a href="{{ route('customer-r2.show', array_merge([$customer->id], request()->only('type'))) }}"
                                class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-600 transition-all hover:bg-gray-50 hover:text-gray-800 active:scale-95 cursor-pointer"
                                title="Reset Filter">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                @if ($invoices->count() > 0)
                    <div class="rounded-2xl bg-white shadow-sm overflow-hidden" style="border: 1px solid #e5e7eb;">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Invoice</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Metode</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-center">Tanggal</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Total</th>
                                        @if (!request('type') || request('type') === 'purchasement')
                                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Sisa</th>
                                        @endif
                                        @if (!request('type') || request('type') === 'debt_payment')
                                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-center">Invoice Terlibat</th>
                                        @endif
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 text-center">Status</th>
                                        <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoices as $invoice)
                                        @php
                                            $isManualDebt = $invoice->type === 'purchasement' && !$invoice->transaction;
                                            $isManualInvoice = $invoice->type === 'purchasement' && $invoice->transaction && $invoice->transaction->is_manual;
                                            $rowTotal = $invoice->type === 'debt_payment'
                                                ? ($invoice->debtPayment?->amount ?? 0)
                                                : ($invoice->transaction?->total_price ?? ($invoice->debts + ($invoice->debtPaymentDetails?->sum('amount_paid') ?? 0)));
                                        @endphp
                                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                            <td class="px-5 py-3.5">
                                                <div class="flex items-center gap-3">
                                                    @if ($invoice->type === 'debt_payment')
                                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $invoice->debts > 0 ? 'bg-red-50 text-red-500' : 'bg-green-50 text-green-500' }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <div class="flex flex-col">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-sm font-bold text-gray-900">{{ $invoice->inv_code ?? '#' . $invoice->id }}</span>
                                                            @if ($isManualDebt || $isManualInvoice)
                                                                <span class="inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700" title="Entri manual oleh owner">MANUAL</span>
                                                            @endif
                                                        </div>
                                                        @if ($invoice->note)
                                                            <span class="text-[10px] text-gray-400 italic max-w-[200px] truncate" title="{{ $invoice->note }}">{{ $invoice->note }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                @if ($invoice->type === 'debt_payment' && $invoice->debtPayment)
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                                                {{ $invoice->debtPayment->payment_method === 'Cash' ? 'bg-green-100 text-green-700' : ($invoice->debtPayment->payment_method === 'Transfer' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700') }}">
                                                        {{ $invoice->debtPayment->payment_method }}
                                                    </span>
                                                @elseif ($invoice->type === 'purchasement' && $invoice->transaction)
                                                    @php
                                                        $method = $invoice->transaction->payment_method;
                                                        $bgClass = match($method) {
                                                            'Cash' => 'bg-green-100 text-green-700',
                                                            'Transfer' => 'bg-blue-100 text-blue-700',
                                                            'QRIS' => 'bg-purple-100 text-purple-700',
                                                            'Kredit' => 'bg-red-100 text-red-700',
                                                            default => 'bg-gray-100 text-gray-700'
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center rounded-full {{ $bgClass }} px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                                                        {{ $method }}
                                                    </span>
                                                @elseif ($isManualDebt)
                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-700">HUTANG</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700 uppercase tracking-wider">UNKNOWN</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <p class="text-sm font-medium text-gray-900">{{ $invoice->created_at->translatedFormat('d M Y') }}</p>
                                                <p class="text-[10px] text-gray-400">{{ $invoice->created_at->translatedFormat('H:i') }}</p>
                                            </td>
                                            <td class="px-5 py-3.5 text-right font-bold text-gray-900 text-sm">
                                                Rp {{ Number::format((float) ($rowTotal), null, 3, 'id') }}
                                            </td>
                                            
                                            @if (!request('type') || request('type') === 'purchasement')
                                                <td class="px-5 py-3.5 text-right font-medium text-sm">
                                                    @if ($invoice->type === 'purchasement')
                                                        <span class="{{ $invoice->debts > 0 ? 'text-red-600 font-bold' : 'text-green-600' }}">
                                                            Rp {{ Number::format((float) ($invoice->debts), null, 3, 'id') }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-300">-</span>
                                                    @endif
                                                </td>
                                            @endif

                                            @if (!request('type') || request('type') === 'debt_payment')
                                                <td class="px-5 py-3.5">
                                                    @if ($invoice->type === 'debt_payment' && $invoice->debtPayment)
                                                        <div class="flex flex-wrap justify-center gap-1">
                                                            @foreach ($invoice->debtPayment->details as $detail)
                                                                <span class="inline-flex items-center rounded bg-gray-50 border border-gray-200 px-1.5 py-0.5 text-[10px] font-medium text-gray-600">
                                                                    {{ $detail->invoice->inv_code ?? '#' . $detail->invoice_id }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="text-center text-gray-300">-</div>
                                                    @endif
                                                </td>
                                            @endif

                                            <td class="px-5 py-3.5 text-center">
                                                @if ($invoice->type === 'purchasement')
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $invoice->debts > 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                                        {{ $invoice->debts > 0 ? 'BELUM LUNAS' : 'LUNAS' }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-[10px] font-bold text-green-700 uppercase tracking-wider">SUKSES</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <div class="inline-flex items-center gap-1">
                                                <button @click="$dispatch('open-modal', 'preview-invoice-{{ $invoice->id }}')"
                                                    class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-button-main transition-colors cursor-pointer"
                                                    title="Lihat Detail">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                </button>
                                                @if(auth()->check() && auth()->user()->isOwner())
                                                    @if($invoice->type === 'purchasement' && $invoice->transaction)
                                                        {{-- Edit/Delete transaksi --}}
                                                        <a href="{{ route('finance.edit', $invoice->transaction->id) }}"
                                                            class="inline-flex items-center rounded-lg px-2 py-1.5 text-blue-600 hover:text-blue-800 transition-colors cursor-pointer" title="Edit transaksi">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                            </svg>
                                                        </a>
                                                        <button type="button" x-data
                                                            @click="$dispatch('open-delete-trx-modal', '{{ $invoice->transaction->id }}')"
                                                            class="inline-flex items-center rounded-lg px-2 py-1.5 text-red-600 hover:text-red-800 transition-colors cursor-pointer" title="Hapus transaksi">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    @elseif($isManualDebt)
                                                        @php
                                                            $debtHasPayments = ($invoice->debtPaymentDetails ?? collect())->isNotEmpty();
                                                        @endphp
                                                        {{-- Edit hutang (always available; modal locks form when has payments) --}}
                                                        <button type="button" @click="$dispatch('open-modal', 'edit-debt-{{ $invoice->id }}')"
                                                            class="inline-flex items-center rounded-lg px-2 py-1.5 {{ $debtHasPayments ? 'text-gray-400 hover:text-gray-600' : 'text-blue-600 hover:text-blue-800' }} transition-colors cursor-pointer"
                                                            title="{{ $debtHasPayments ? 'Lihat pembayaran terkait' : 'Edit hutang' }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                            </svg>
                                                        </button>
                                                        @if(! $debtHasPayments)
                                                            <button type="button" x-data
                                                                @click="$dispatch('open-delete-debt-modal', { id: '{{ $invoice->id }}', code: '{{ $invoice->inv_code ?? '#' . $invoice->id }}' })"
                                                                class="inline-flex items-center rounded-lg px-2 py-1.5 text-red-600 hover:text-red-800 transition-colors cursor-pointer" title="Hapus hutang">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        @else
                                                            <span class="inline-flex items-center rounded-lg px-2 py-1.5 text-gray-300 cursor-not-allowed" title="Tidak bisa dihapus: ada pembayaran terkait">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                                </svg>
                                                            </span>
                                                        @endif
                                                    @elseif($invoice->type === 'debt_payment' && $invoice->debtPayment)
                                                        {{-- Edit/Delete pembayaran hutang --}}
                                                        <button type="button" @click="$dispatch('open-modal', 'edit-payment-{{ $invoice->id }}')"
                                                            class="inline-flex items-center rounded-lg px-2 py-1.5 text-blue-600 hover:text-blue-800 transition-colors cursor-pointer" title="Edit pembayaran">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                            </svg>
                                                        </button>
                                                        <button type="button" x-data
                                                            @click="$dispatch('open-delete-payment-modal', { id: '{{ $invoice->id }}', code: '{{ $invoice->inv_code ?? '#' . $invoice->id }}' })"
                                                            class="inline-flex items-center rounded-lg px-2 py-1.5 text-red-600 hover:text-red-800 transition-colors cursor-pointer" title="Batalkan pembayaran">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    @endif
                                                @endif
                                                </div>

                                                <x-modal name="preview-invoice-{{ $invoice->id }}" title="PREVIEW {{ $invoice->inv_code ?? 'NOTA #' . $invoice->id }}" maxWidth="2xl">
                                                    <div class="p-1">
                                                        @include('customer-r2.partials._invoice-content', [
                                                            'invoice' => $invoice,
                                                            'customer' => $customer,
                                                            'transaction' => $invoice->transaction,
                                                            'details' => $invoice->transaction ? $invoice->transaction->transactionDetails : collect()
                                                        ])
                                                    </div>
                                                    <div class="p-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                                                        <x-button.remove-button x-on:click="$dispatch('close-modal', 'preview-invoice-{{ $invoice->id }}')" type="button">
                                                            <span class="font-bold text-gray-800">TUTUP</span>
                                                        </x-button.remove-button>
                                                        <a href="{{ route('customer-r2.invoice.pdf', $invoice->id) }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            x-data="{ loading: false }"
                                                            x-on:click="loading = true; setTimeout(() => loading = false, 4000)"
                                                            :class="loading ? 'opacity-70 pointer-events-none' : ''"
                                                            class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-200 px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer">
                                                            <template x-if="!loading">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-red-500">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                                </svg>
                                                            </template>
                                                            <template x-if="loading">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-4 h-4 text-red-500 animate-spin">
                                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-opacity="0.25" />
                                                                    <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
                                                                </svg>
                                                            </template>
                                                            <span x-text="loading ? 'MENYIAPKAN...' : 'DOWNLOAD PDF'"></span>
                                                        </a>

                                                        @unless ($isManualDebt)
                                                            <button type="button" @click="ThermalPrinter.printR2Invoice({{ $invoice->id }})"
                                                                class="inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-bold text-gray-800 hover:bg-button-hover transition-colors shadow-sm cursor-pointer">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 9H5.25" />
                                                                </svg>
                                                                CETAK NOTA
                                                            </button>
                                                        @endunless
                                                    </div>
                                                </x-modal>

                                                @if(auth()->check() && auth()->user()->isOwner() && $isManualDebt)
                                                    @php
                                                        $relatedPayments = $invoice->debtPaymentDetails ?? collect();
                                                        $alreadyPaidForDebt = (float) $relatedPayments->sum('amount_paid');
                                                        $hasPayments = $alreadyPaidForDebt > 0;
                                                        $modalKey = 'edit-debt-' . $invoice->id;
                                                        $isCurrentModal = session('open_modal') === $modalKey;
                                                    @endphp
                                                    {{-- Edit Manual Debt Modal --}}
                                                    <x-modal name="{{ $modalKey }}" title="EDIT HUTANG MANUAL" maxWidth="lg"
                                                        x-init="if ('{{ session('open_modal') }}' === '{{ $modalKey }}') $dispatch('open-modal', '{{ $modalKey }}')">

                                                        {{-- Validation error banner (only when this modal owns the error state) --}}
                                                        @if($isCurrentModal && $errors->any())
                                                            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
                                                                <div class="flex items-start gap-3">
                                                                    <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                                    </svg>
                                                                    <div class="flex-1">
                                                                        <p class="text-xs font-bold uppercase tracking-wider text-red-700 mb-1">Gagal Menyimpan</p>
                                                                        <ul class="text-xs text-red-700 space-y-0.5">
                                                                            @foreach($errors->all() as $err)
                                                                                <li>&bull; {{ $err }}</li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        {{-- Invoice header --}}
                                                        <div class="mb-4 rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between">
                                                            <div>
                                                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Invoice</p>
                                                                <p class="text-sm font-bold text-gray-900">{{ $invoice->inv_code ?? '#' . $invoice->id }}</p>
                                                            </div>
                                                            <div class="text-right">
                                                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Sisa Hutang</p>
                                                                <p class="text-sm font-bold {{ $invoice->debts > 0 ? 'text-red-600' : 'text-green-600' }}">Rp {{ Number::format((float) ($invoice->debts), null, 3, 'id') }}</p>
                                                            </div>
                                                        </div>

                                                        {{-- Related Payments Section --}}
                                                        <div class="mb-4">
                                                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-2">Pembayaran Terkait</p>
                                                            @if($hasPayments)
                                                                <div class="space-y-1.5">
                                                                    @foreach($relatedPayments as $detail)
                                                                        @php
                                                                            $payment = $detail->debtPayment;
                                                                            $payInv = $payment?->paymentInvoice;
                                                                            $payInvId = $payInv?->id;
                                                                            $payInvCode = $payInv?->inv_code ?? '#PAY-' . ($payment?->id ?? '?');
                                                                            $inlineEditKey = 'edit-payment-inline-' . $invoice->id . '-' . ($payInvId ?? 0);
                                                                            $inlineIsCurrent = session('open_modal') === $inlineEditKey;
                                                                        @endphp
                                                                        <div class="flex items-center justify-between gap-3 rounded-lg border border-blue-100 bg-blue-50/50 px-3 py-2 transition-colors hover:bg-blue-50">
                                                                            <div class="flex min-w-0 items-center gap-2">
                                                                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25" />
                                                                                    </svg>
                                                                                </span>
                                                                                <div class="min-w-0">
                                                                                    <p class="text-xs font-bold text-gray-900 truncate">{{ $payInvCode }}</p>
                                                                                    <p class="text-[10px] text-gray-500 truncate">
                                                                                        {{ $payment ? $payment->payment_method : '-' }}
                                                                                        @if($payInv) &middot; {{ $payInv->created_at->translatedFormat('d M Y') }} @endif
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            <div class="flex items-center gap-2 shrink-0">
                                                                                <p class="text-xs font-bold text-blue-700 whitespace-nowrap">Rp {{ Number::format((float) ($detail->amount_paid), null, 3, 'id') }}</p>
                                                                                @if($payInvId && $payment)
                                                                                    <div class="flex items-center gap-0.5 border-l border-blue-200/60 pl-2">
                                                                                        <button type="button"
                                                                                            @click="$dispatch('close-modal', '{{ $modalKey }}'); setTimeout(() => $dispatch('open-modal', '{{ $inlineEditKey }}'), 220)"
                                                                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 hover:bg-blue-100 hover:text-blue-800 transition-colors cursor-pointer"
                                                                                            title="Edit pembayaran">
                                                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                                                            </svg>
                                                                                        </button>
                                                                                        <button type="button"
                                                                                            @click="$dispatch('close-modal', '{{ $modalKey }}'); setTimeout(() => $dispatch('open-delete-payment-modal', { id: '{{ $payInvId }}', code: '{{ $payInvCode }}' }), 220)"
                                                                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-500 hover:bg-red-100 hover:text-red-700 transition-colors cursor-pointer"
                                                                                            title="Hapus pembayaran">
                                                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                                            </svg>
                                                                                        </button>
                                                                                    </div>
                                                                                @endif
                                                                            </div>
                                                                        </div>

                                                                        {{-- Inline Edit-Payment Modal (pagination-independent, teleported to <body> to escape parent display:none) --}}
                                                                        @if($payInvId && $payment)
                                                                            <template x-teleport="body">
                                                                            <x-modal name="{{ $inlineEditKey }}" title="EDIT PEMBAYARAN HUTANG" maxWidth="md"
                                                                                x-init="if ('{{ session('open_modal') }}' === '{{ $inlineEditKey }}') $dispatch('open-modal', '{{ $inlineEditKey }}')">
                                                                                @if($inlineIsCurrent && $errors->any())
                                                                                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
                                                                                        <div class="flex items-start gap-3">
                                                                                            <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                                                            </svg>
                                                                                            <div class="flex-1">
                                                                                                <p class="text-xs font-bold uppercase tracking-wider text-red-700 mb-1">Gagal Menyimpan</p>
                                                                                                <ul class="text-xs text-red-700 space-y-0.5">
                                                                                                    @foreach($errors->all() as $err)
                                                                                                        <li>&bull; {{ $err }}</li>
                                                                                                    @endforeach
                                                                                                </ul>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                @endif

                                                                                @php
                                                                                    $inlineMaxAllowed = (float) $totalDebt + (float) $payment->amount;
                                                                                    $inlineRupiahName = 'amount';
                                                                                @endphp
                                                                                <form method="POST" action="{{ route('customer-r2.debt-payment.update', $payInvId) }}" class="space-y-4"
                                                                                    x-data="{
                                                                                        maxAmount: {{ $inlineMaxAllowed }},
                                                                                        originalAmount: {{ (float) $payment->amount }},
                                                                                        originalMethod: '{{ $payment->payment_method }}',
                                                                                        originalDate: '{{ $payInv->created_at->format('Y-m-d') }}',
                                                                                        currentAmount: {{ (float) old('amount', $payment->amount) }},
                                                                                        formatRp(n) { return Number(n||0).toLocaleString('id-ID'); },
                                                                                        get exceedsLimit() { return Number(this.currentAmount||0) > Number(this.maxAmount||0); },
                                                                                        resetToOriginal() {
                                                                                            this.currentAmount = this.originalAmount;
                                                                                            this.$dispatch('update-rupiah-value', { name: 'amount', value: String(this.originalAmount) });
                                                                                            this.$el.querySelectorAll('input[name=payment_method]').forEach(r => { r.checked = (r.value === this.originalMethod); });
                                                                                            const d = this.$el.querySelector('input[type=date][name=created_at]');
                                                                                            if (d) d.value = this.originalDate;
                                                                                        }
                                                                                    }"
                                                                                    @rupiah-change="if ($event.detail.name === 'amount') currentAmount = parseFloat($event.detail.value) || 0"
                                                                                    @submit="if (exceedsLimit) { $event.preventDefault(); }"
                                                                                    @modal-closed.window="if ($event.detail === '{{ $inlineEditKey }}') resetToOriginal()">
                                                                                    @csrf
                                                                                    @method('PUT')
                                                                                    <input type="hidden" name="open_modal_on_error" value="{{ $inlineEditKey }}">

                                                                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                                                                        <p class="text-xs text-amber-800">
                                                                                            Mengubah pembayaran akan rollback alokasi lama lalu re-distribusi via FIFO ke invoice tertua.
                                                                                            Berpengaruh ke kas, piutang, dan status lunas invoice terkait.
                                                                                            <br>Maks pembayaran: <strong>Rp {{ Number::format((float) ($inlineMaxAllowed), null, 3, 'id') }}</strong> (sisa hutang + nominal pembayaran ini).
                                                                                        </p>
                                                                                    </div>

                                                                                    {{-- Real-time client-side validation banner --}}
                                                                                    <template x-if="exceedsLimit">
                                                                                        <div class="rounded-xl border border-red-300 bg-red-50 p-3">
                                                                                            <div class="flex items-start gap-2">
                                                                                                <svg class="h-4 w-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                                                                </svg>
                                                                                                <p class="text-xs text-red-700 leading-relaxed">
                                                                                                    Nominal pembayaran (Rp <span x-text="formatRp(currentAmount)"></span>) melebihi total hutang yang tersedia (Rp <span x-text="formatRp(maxAmount)"></span>).
                                                                                                </p>
                                                                                            </div>
                                                                                        </div>
                                                                                    </template>

                                                                                    <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between">
                                                                                        <div>
                                                                                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Invoice Pembayaran</p>
                                                                                            <p class="text-sm font-bold text-gray-900">{{ $payInvCode }}</p>
                                                                                        </div>
                                                                                        <div class="text-right">
                                                                                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Untuk Hutang</p>
                                                                                            <p class="text-sm font-bold text-gray-900">{{ $invoice->inv_code ?? '#' . $invoice->id }}</p>
                                                                                        </div>
                                                                                    </div>

                                                                                    <x-input-rupiah
                                                                                        name="amount"
                                                                                        label="Nominal Pembayaran"
                                                                                        id="inline-amt-{{ $invoice->id }}-{{ $payInvId }}"
                                                                                        required
                                                                                        decimals="3"
                                                                                        value="{{ old('amount', $payment->amount) }}"
                                                                                    />

                                                                                    <div>
                                                                                        <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Metode Pembayaran</label>
                                                                                        <div class="mt-1 grid grid-cols-3 gap-2">
                                                                                            @foreach (['Cash', 'Transfer', 'QRIS'] as $pm)
                                                                                                <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold uppercase tracking-wider text-gray-600 transition-colors has-[:checked]:border-button-main has-[:checked]:bg-button-main/20 has-[:checked]:text-gray-900">
                                                                                                    <input type="radio" name="payment_method" value="{{ $pm }}" required
                                                                                                        {{ old('payment_method', $payment->payment_method) === $pm ? 'checked' : '' }}
                                                                                                        class="sr-only">
                                                                                                    {{ strtoupper($pm) }}
                                                                                                </label>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </div>

                                                                                    <div>
                                                                                        <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Tanggal Pembayaran</label>
                                                                                        <input type="date" name="created_at" required
                                                                                            value="{{ old('created_at', $payInv->created_at->format('Y-m-d')) }}"
                                                                                            max="{{ now()->format('Y-m-d') }}"
                                                                                            @click="$el.showPicker()"
                                                                                            onkeydown="return false"
                                                                                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-button-main cursor-pointer">
                                                                                    </div>

                                                                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                                                                                        <button type="button" @click="$dispatch('close-modal', '{{ $inlineEditKey }}')"
                                                                                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer">
                                                                                            BATAL
                                                                                        </button>
                                                                                        <button type="submit"
                                                                                            :disabled="exceedsLimit"
                                                                                            :class="exceedsLimit
                                                                                                ? 'inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-sm font-bold text-gray-400 cursor-not-allowed'
                                                                                                : 'inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-bold text-gray-900 hover:bg-button-hover transition-colors shadow-sm cursor-pointer'">
                                                                                            SIMPAN PERUBAHAN
                                                                                        </button>
                                                                                    </div>
                                                                                </form>
                                                                            </x-modal>
                                                                            </template>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-3 text-center">
                                                                    <p class="text-[11px] italic text-gray-400">Belum ada pembayaran terkait. Hutang aman untuk diedit/dihapus.</p>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        @if($hasPayments)
                                                            {{-- Locked state --}}
                                                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                                                <div class="flex items-start gap-3">
                                                                    <svg class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                                    </svg>
                                                                    <div>
                                                                        <p class="text-xs font-bold uppercase tracking-wider text-amber-700 mb-1">Tidak Bisa Diedit / Dihapus</p>
                                                                        <p class="text-xs text-amber-800 leading-relaxed">
                                                                            Hutang ini sudah memiliki pembayaran terkait sebesar
                                                                            <strong>Rp {{ Number::format((float) ($alreadyPaidForDebt), null, 3, 'id') }}</strong>.
                                                                            Hapus dulu pembayaran di atas (klik tombol PAY... di tabel) sebelum mengedit hutang ini.
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center justify-end gap-2 pt-4 mt-4 border-t border-gray-100">
                                                                <button type="button" @click="$dispatch('close-modal', '{{ $modalKey }}')"
                                                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer">
                                                                    Tutup
                                                                </button>
                                                            </div>
                                                        @else
                                                            {{-- Editable form --}}
                                                            <form method="POST" action="{{ route('customer-r2.debt.update', $invoice->id) }}" class="space-y-4"
                                                                x-data="{
                                                                    originalAmount: {{ (float) $invoice->debts }},
                                                                    originalNote: @js($invoice->note ?? ''),
                                                                    originalDate: '{{ $invoice->created_at->format('Y-m-d') }}',
                                                                    resetToOriginal() {
                                                                        this.$dispatch('update-rupiah-value', { name: 'amount', value: String(this.originalAmount) });
                                                                        const t = this.$el.querySelector('textarea[name=note]');
                                                                        if (t) t.value = this.originalNote;
                                                                        const d = this.$el.querySelector('input[type=date][name=created_at]');
                                                                        if (d) d.value = this.originalDate;
                                                                    }
                                                                }"
                                                                @modal-closed.window="if ($event.detail === '{{ $modalKey }}') resetToOriginal()">
                                                                @csrf
                                                                @method('PUT')

                                                                <x-input-rupiah
                                                                    name="amount"
                                                                    label="Nominal Hutang"
                                                                    id="edit-debt-amount-{{ $invoice->id }}"
                                                                    required
                                                                    decimals="3"
                                                                    value="{{ old('amount', $invoice->debts) }}"
                                                                />

                                                                <div>
                                                                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Tanggal</label>
                                                                    <input type="date" name="created_at" required
                                                                        value="{{ old('created_at', $invoice->created_at->format('Y-m-d')) }}"
                                                                        max="{{ now()->format('Y-m-d') }}"
                                                                        @click="$el.showPicker()"
                                                                        onkeydown="return false"
                                                                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-button-main cursor-pointer">
                                                                    @error('created_at')
                                                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                                    @enderror
                                                                </div>

                                                                <div>
                                                                    <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Keterangan</label>
                                                                    <textarea name="note" rows="3" required
                                                                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-button-main">{{ old('note', $invoice->note) }}</textarea>
                                                                    @error('note')
                                                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                                    @enderror
                                                                </div>

                                                                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                                                                    <button type="button" @click="$dispatch('close-modal', '{{ $modalKey }}')"
                                                                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 cursor-pointer">
                                                                        BATAL
                                                                    </button>
                                                                    <button type="submit"
                                                                        class="inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-bold text-gray-900 hover:bg-button-hover transition-colors shadow-sm cursor-pointer">
                                                                        SIMPAN PERUBAHAN
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        @endif
                                                    </x-modal>
                                                @endif

                                                @if(auth()->check() && auth()->user()->isOwner() && $invoice->type === 'debt_payment' && $invoice->debtPayment)
                                                    @php
                                                        $payModalKey = 'edit-payment-' . $invoice->id;
                                                        $payIsCurrent = session('open_modal') === $payModalKey;
                                                    @endphp
                                                    {{-- Edit Debt Payment Modal --}}
                                                    <x-modal name="{{ $payModalKey }}" title="EDIT PEMBAYARAN HUTANG" maxWidth="md"
                                                        x-init="if ('{{ session('open_modal') }}' === '{{ $payModalKey }}') $dispatch('open-modal', '{{ $payModalKey }}')">

                                                        @if($payIsCurrent && $errors->any())
                                                            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
                                                                <div class="flex items-start gap-3">
                                                                    <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                                    </svg>
                                                                    <div class="flex-1">
                                                                        <p class="text-xs font-bold uppercase tracking-wider text-red-700 mb-1">Gagal Menyimpan</p>
                                                                        <ul class="text-xs text-red-700 space-y-0.5">
                                                                            @foreach($errors->all() as $err)
                                                                                <li>&bull; {{ $err }}</li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @php
                                                            $standaloneMaxAllowed = (float) $totalDebt + (float) $invoice->debtPayment->amount;
                                                        @endphp
                                                        <form method="POST" action="{{ route('customer-r2.debt-payment.update', $invoice->id) }}" class="space-y-4"
                                                            x-data="{
                                                                maxAmount: {{ $standaloneMaxAllowed }},
                                                                originalAmount: {{ (float) $invoice->debtPayment->amount }},
                                                                originalMethod: '{{ $invoice->debtPayment->payment_method }}',
                                                                originalDate: '{{ $invoice->created_at->format('Y-m-d') }}',
                                                                currentAmount: {{ (float) old('amount', $invoice->debtPayment->amount) }},
                                                                formatRp(n) { return Number(n||0).toLocaleString('id-ID'); },
                                                                get exceedsLimit() { return Number(this.currentAmount||0) > Number(this.maxAmount||0); },
                                                                resetToOriginal() {
                                                                    this.currentAmount = this.originalAmount;
                                                                    this.$dispatch('update-rupiah-value', { name: 'amount', value: String(this.originalAmount) });
                                                                    this.$el.querySelectorAll('input[name=payment_method]').forEach(r => { r.checked = (r.value === this.originalMethod); });
                                                                    const d = this.$el.querySelector('input[type=date][name=created_at]');
                                                                    if (d) d.value = this.originalDate;
                                                                }
                                                            }"
                                                            @rupiah-change="if ($event.detail.name === 'amount') currentAmount = parseFloat($event.detail.value) || 0"
                                                            @submit="if (exceedsLimit) { $event.preventDefault(); }"
                                                            @modal-closed.window="if ($event.detail === '{{ $payModalKey }}') resetToOriginal()">
                                                            @csrf
                                                            @method('PUT')

                                                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                                                <p class="text-xs text-amber-800">
                                                                    Mengubah pembayaran akan rollback alokasi lama lalu re-distribusi via FIFO ke invoice tertua.
                                                                    Berpengaruh ke kas, piutang, dan status lunas invoice terkait.
                                                                    <br>Maks pembayaran: <strong>Rp {{ Number::format((float) ($standaloneMaxAllowed), null, 3, 'id') }}</strong> (sisa hutang + nominal pembayaran ini).
                                                                </p>
                                                            </div>

                                                            {{-- Real-time client-side validation banner --}}
                                                            <template x-if="exceedsLimit">
                                                                <div class="rounded-xl border border-red-300 bg-red-50 p-3">
                                                                    <div class="flex items-start gap-2">
                                                                        <svg class="h-4 w-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                                                        </svg>
                                                                        <p class="text-xs text-red-700 leading-relaxed">
                                                                            Nominal pembayaran (Rp <span x-text="formatRp(currentAmount)"></span>) melebihi total hutang yang tersedia (Rp <span x-text="formatRp(maxAmount)"></span>).
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <div>
                                                                <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Invoice Pembayaran</label>
                                                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $invoice->inv_code ?? '#' . $invoice->id }}</p>
                                                            </div>

                                                            <x-input-rupiah
                                                                name="amount"
                                                                label="Nominal Pembayaran"
                                                                id="edit-payment-amount-{{ $invoice->id }}"
                                                                required
                                                                decimals="3"
                                                                value="{{ old('amount', $invoice->debtPayment->amount) }}"
                                                            />

                                                            <div>
                                                                <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Metode Pembayaran</label>
                                                                <div class="mt-1 grid grid-cols-3 gap-2">
                                                                    @foreach (['Cash', 'Transfer', 'QRIS'] as $pm)
                                                                        <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-xs font-bold uppercase tracking-wider text-gray-600 transition-colors has-[:checked]:border-button-main has-[:checked]:bg-button-main/20 has-[:checked]:text-gray-900">
                                                                            <input type="radio" name="payment_method" value="{{ $pm }}" required
                                                                                {{ old('payment_method', $invoice->debtPayment->payment_method) === $pm ? 'checked' : '' }}
                                                                                class="sr-only">
                                                                            {{ strtoupper($pm) }}
                                                                        </label>
                                                                    @endforeach
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Tanggal Pembayaran</label>
                                                                <input type="date" name="created_at" required
                                                                    value="{{ old('created_at', $invoice->created_at->format('Y-m-d')) }}"
                                                                    max="{{ now()->format('Y-m-d') }}"
                                                                    @click="$el.showPicker()"
                                                                    onkeydown="return false"
                                                                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-button-main cursor-pointer">
                                                            </div>

                                                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                                                                <x-button.remove-button x-on:click="$dispatch('close-modal', 'edit-payment-{{ $invoice->id }}')" type="button">
                                                                    <span class="font-bold text-gray-800">BATAL</span>
                                                                </x-button.remove-button>
                                                                <button type="submit"
                                                                    :disabled="exceedsLimit"
                                                                    :class="exceedsLimit
                                                                        ? 'inline-flex items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-sm font-bold text-gray-400 cursor-not-allowed'
                                                                        : 'inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-bold text-gray-900 hover:bg-button-hover transition-colors shadow-sm cursor-pointer'">
                                                                    SIMPAN PERUBAHAN
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </x-modal>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl bg-white p-12 text-center shadow-sm" style="border: 1px solid #e5e7eb;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto h-12 w-12 text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 italic">Tidak ada invoice untuk kriteria ini.</p>
                    </div>
                @endif

                <div class="mt-4 flex justify-end">
                    {{ $invoices->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Add Direct Debt Modal --}}
    <x-modal name="add-debt" title="TAMBAH HUTANG LANGSUNG" maxWidth="md" x-init="if ('{{ session('open_modal') }}' === 'add-debt') $dispatch('open-modal', 'add-debt')">
        @if(session('open_modal') === 'add-debt' && $errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-xs font-bold uppercase tracking-wider text-red-700 mb-1">Gagal Menyimpan</p>
                        <ul class="text-xs text-red-700 space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>&bull; {{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        <form method="POST" action="{{ route('customer-r2.debt.store', $customer->id) }}" class="space-y-4">
            @csrf

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                <p class="text-xs text-amber-800">
                    Mencatat hutang lama tanpa rincian item. Nominal yang diisi akan langsung menjadi hutang aktif pelanggan.
                </p>
            </div>

            <div>
                <label class="text-xs font-bold uppercase tracking-wider text-gray-700">Pelanggan</label>
                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $customer->name }}</p>
            </div>

            <x-input-rupiah 
                name="amount" 
                label="Nominal Hutang" 
                id="add-debt-amount"
                required
                decimals="3"
                value="{{ old('amount') }}"
            />

            <div>
                <label for="add-debt-date" class="text-xs font-bold uppercase tracking-wider text-gray-700">Tanggal</label>
                <input id="add-debt-date" type="date" name="created_at" required
                    value="{{ old('created_at', now()->format('Y-m-d')) }}"
                    max="{{ now()->format('Y-m-d') }}"
                    @click="$el.showPicker()"
                    onkeydown="return false"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-button-main cursor-pointer">
                @error('created_at')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="add-debt-note" class="text-xs font-bold uppercase tracking-wider text-gray-700">Keterangan</label>
                <textarea id="add-debt-note" name="note" rows="3" required
                    placeholder="Contoh: hutang akumulasi periode Jan-Mar 2026"
                    class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:border-button-main">{{ old('note') }}</textarea>
                @error('note')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                <x-button.remove-button x-on:click="$dispatch('close-modal', 'add-debt')" type="button">
                    <span class="font-bold text-gray-800">BATAL</span>
                </x-button.remove-button>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-bold text-gray-900 hover:bg-button-hover transition-colors shadow-sm cursor-pointer">
                    SIMPAN HUTANG
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Pay Debt Modal --}}
    <x-modal name="pay-debt" title="PEMBAYARAN HUTANG PELANGGAN" maxWidth="xl" x-init="if ($errors->any() && (!'{{ session('open_modal') }}' || '{{ session('open_modal') }}' === 'pay-debt')) $dispatch('open-modal', 'pay-debt')">
        <div>
            @php
                $payDebtIsCurrent = $errors->any() && (! session('open_modal') || session('open_modal') === 'pay-debt');
            @endphp
            @if($payDebtIsCurrent)
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div class="flex-1">
                            <p class="text-xs font-bold uppercase tracking-wider text-red-700 mb-1">Gagal Memproses Pembayaran</p>
                            <ul class="text-xs text-red-700 space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>&bull; {{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
            {{-- Customer Summary Info within Modal --}}
            <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $customer->name }}</p>
                        @if($customer->phone_number)
                            @php
                                $whatsappNumberModal = preg_replace('/[^0-9]/', '', $customer->phone_number);
                                if (str_starts_with($whatsappNumberModal, '0')) {
                                    $whatsappNumberModal = '62' . substr($whatsappNumberModal, 1);
                                }
                            @endphp
                            <a href="https://wa.me/{{ $whatsappNumberModal }}" target="_blank" class="mt-0.5 block text-xs text-gray-500 hover:text-green-600 transition-colors">
                                {{ $customer->phone_number }}
                            </a>
                        @else
                            <p class="mt-0.5 text-xs text-gray-500">-</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Hutang</p>
                        <p class="mt-0.5 text-xl font-bold text-red-600">
                            Rp {{ Number::format((float) ($totalDebt), null, 3, 'id') }}
                        </p>
                    </div>
                </div>
            </div>

            @include('customer-r2.partials._payment-form', [
                'customer'      => $customer,
                'totalDebt'     => $totalDebt,
                'creditBalance' => $creditBalance,
                'isModal'       => true,
            ])
        </div>
    </x-modal>
    @if(auth()->check() && auth()->user()->isOwner())
        {{-- Delete Transaction Modal --}}
        <div x-data="{ deleteId: '', confirmText: '' }" @open-delete-trx-modal.window="deleteId = $event.detail; confirmText = ''; $dispatch('open-modal', 'delete-trx-modal')">
            <x-modal name="delete-trx-modal" maxWidth="md">
                <div class="p-6">
                    <div class="mb-3 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Hapus Transaksi</h3>
                    </div>
                    <p class="mb-3 text-sm text-gray-600">
                        Transaksi <strong x-text="'#' + deleteId" class="text-gray-900"></strong> akan dihapus permanen.
                        Stok produk akan dikembalikan, dan invoice serta pembayaran utang yang terkait akan di-reverse.
                    </p>
                    <p class="mb-3 text-xs text-red-600">
                        <strong>Aksi ini tidak bisa dibatalkan.</strong> Ketik <code class="rounded bg-gray-100 px-1 py-0.5">HAPUS</code> untuk konfirmasi.
                    </p>
                    <form method="POST" :action="`{{ url('laporan-keuangan') }}/${deleteId}`">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                        <input type="text" x-model="confirmText" placeholder="Ketik HAPUS"
                            class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500" />
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$dispatch('close-modal', 'delete-trx-modal')"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="confirmText !== 'HAPUS'"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300">
                                Hapus
                            </button>
                        </div>
                    </form>
                </div>
            </x-modal>
        </div>

        {{-- Delete Manual Debt Modal --}}
        <div x-data="{ deleteId: '', deleteCode: '', confirmText: '' }"
             @open-delete-debt-modal.window="deleteId = $event.detail.id; deleteCode = $event.detail.code; confirmText = ''; $dispatch('open-modal', 'delete-debt-modal')">
            <x-modal name="delete-debt-modal" maxWidth="md">
                <div class="p-6">
                    <div class="mb-3 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Hapus Hutang Manual</h3>
                    </div>
                    <p class="mb-3 text-sm text-gray-600">
                        Hutang <strong x-text="deleteCode" class="text-gray-900"></strong> akan dihapus permanen.
                        Total piutang & ekuitas akan ikut berkurang.
                    </p>
                    <p class="mb-3 text-xs text-red-600">
                        <strong>Aksi tidak bisa dibatalkan.</strong> Ketik <code class="rounded bg-gray-100 px-1 py-0.5">HAPUS</code> untuk konfirmasi.
                    </p>
                    <form method="POST" :action="`{{ url('customer-r2/debt') }}/${deleteId}`">
                        @csrf
                        @method('DELETE')
                        <input type="text" x-model="confirmText" placeholder="Ketik HAPUS"
                            class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500" />
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$dispatch('close-modal', 'delete-debt-modal')"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                            <button type="submit" :disabled="confirmText !== 'HAPUS'"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300">Hapus</button>
                        </div>
                    </form>
                </div>
            </x-modal>
        </div>

        {{-- Delete Debt Payment Modal --}}
        <div x-data="{ deleteId: '', deleteCode: '', confirmText: '' }"
             @open-delete-payment-modal.window="deleteId = $event.detail.id; deleteCode = $event.detail.code; confirmText = ''; $dispatch('open-modal', 'delete-payment-modal')">
            <x-modal name="delete-payment-modal" maxWidth="md">
                <div class="p-6">
                    <div class="mb-3 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Batalkan Pembayaran Hutang</h3>
                    </div>
                    <p class="mb-3 text-sm text-gray-600">
                        Pembayaran <strong x-text="deleteCode" class="text-gray-900"></strong> akan dihapus dan hutang pada invoice yang dibayar akan dipulihkan.
                        Kas berkurang, piutang bertambah, status lunas invoice terkait akan dicabut.
                    </p>
                    <p class="mb-3 text-xs text-red-600">
                        <strong>Aksi tidak bisa dibatalkan.</strong> Ketik <code class="rounded bg-gray-100 px-1 py-0.5">HAPUS</code> untuk konfirmasi.
                    </p>
                    <form method="POST" :action="`{{ url('customer-r2/debt-payment') }}/${deleteId}`">
                        @csrf
                        @method('DELETE')
                        <input type="text" x-model="confirmText" placeholder="Ketik HAPUS"
                            class="mb-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500" />
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$dispatch('close-modal', 'delete-payment-modal')"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                            <button type="submit" :disabled="confirmText !== 'HAPUS'"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-red-300">Hapus</button>
                        </div>
                    </form>
                </div>
            </x-modal>
        </div>
    @endif

    {{-- Edit Customer Modal --}}
    <x-modal name="edit-customer" title="EDIT DATA PELANGGAN" maxWidth="4xl"
        x-init="if ('{{ session('open_modal') }}' === 'edit-customer') $dispatch('open-modal', 'edit-customer')">
        <div class="p-1">
            @include('customer-r2._form', [
                'action' => route('customer-r2.update', $customer->id),
                'method' => 'POST',
                'customer' => $customer,
                'isEdit' => true,
                'modalName' => 'edit-customer',
            ])
        </div>
    </x-modal>

    {{-- Delete Customer Modal --}}
    <x-modal name="delete-customer-modal" title="HAPUS PELANGGAN" maxWidth="md">
        <div class="p-2">
            <p class="text-sm text-gray-600">
                Yakin ingin menghapus pelanggan
                <span class="font-semibold text-gray-900">"{{ $customer->name }}"</span>?
            </p>

            @if (($totalDebt ?? 0) > 0)
                <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <div>
                        <span class="font-bold block text-amber-900">Pelanggan Memiliki Hutang Aktif!</span>
                        Pelanggan ini masih memiliki tunggakan hutang sebesar <strong>Rp {{ Number::format((float) ($totalDebt), null, 3, 'id') }}</strong>. Pelanggan tidak dapat dihapus sebelum semua hutang dilunasi.
                    </div>
                </div>
            @else
                <p class="mt-1 text-xs text-gray-500">
                    Data transaksi dan invoice pelanggan ini akan tetap tersimpan dan tidak akan dihapus.
                </p>
            @endif

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

                <form method="POST" action="{{ route('customer-r2.destroy', $customer->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        @if (($totalDebt ?? 0) > 0) disabled @endif
                        class="rounded-lg px-4 py-2 text-xs font-semibold transition-colors {{ ($totalDebt ?? 0) > 0 ? 'bg-gray-300 text-gray-400 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700 cursor-pointer' }}">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </x-modal>

    @push('scripts')
        <script src="{{ asset('qz/qz-tray.js') }}"></script>
        <script src="{{ asset('qz/qz-config.js') }}"></script>
        <script src="{{ asset('qz/printer-utils.js') }}"></script>
        <script src="{{ asset('qz/layouts/cashier-layout.js') }}"></script>
        <script src="{{ asset('qz/layouts/r2-layout.js') }}"></script>
        <script src="{{ asset('qz/printer-main.js') }}"></script>
    @endpush
</x-app-layout>
