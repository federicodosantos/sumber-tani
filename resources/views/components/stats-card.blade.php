@props([
    'title',
    'value',
    'percentage' => null,
    'trend' => 'up',
    'hasFilter' => false,
    'filterId' => null,
    'currentFilter' => 'daily',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow p-6 border border-gray-200']) }}>
    <div class="mb-1 flex items-center justify-between">
        <div class="text-sm font-medium text-gray-500">{{ $title }}</div>

        @if ($hasFilter && $filterId)
            <div class="relative">
                <select id="{{ $filterId }}"
                    class="cursor-pointer appearance-none rounded border border-gray-300 bg-gray-50 px-2 py-1 pr-6 text-xs hover:border-gray-400 focus:border-transparent focus:outline-none focus:ring-1 focus:ring-button-main">
                    <option value="daily" {{ $currentFilter == 'daily' ? 'selected' : '' }}>Harian</option>
                    <option value="weekly" {{ $currentFilter == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                    <option value="monthly" {{ $currentFilter == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    <option value="yearly" {{ $currentFilter == 'yearly' ? 'selected' : '' }}>Tahunan</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-1 text-gray-500">
                    <svg class="h-3 w-3 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                    </svg>
                </div>
            </div>
        @endif
    </div>

    <div class="flex items-end justify-between">
        <div>
            <div class="text-2xl font-bold text-gray-900">{{ $value }}</div>
            @if ($percentage)
                <div class="{{ $trend === 'up' ? 'text-green-600' : 'text-red-600' }} mt-1 text-xs">
                    {{ $trend === 'up' ? '↑' : '↓' }} {{ $percentage }}
                </div>
            @endif
        </div>
    </div>
</div>
