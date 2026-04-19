@props([
    'action', 
    'method' => 'POST',
    'title' => null,
    'showBatchSelector' => false,
    'batches' => []
])

@php
  $formMethod = strtoupper($method);
@endphp

<div class="rounded-2xl bg-white p-6 shadow-sm sm:p-8" style="border: 1px solid #e5e7eb;">
  <form action="{{ $action }}" method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}" {{ $attributes }}>
    @csrf

    @if (!in_array($formMethod, ['GET', 'POST']))
      @method($formMethod)
    @endif

    {{-- Header Section with Title --}}
    @if($title || $showBatchSelector)
      <div class="mb-6 flex items-center justify-between">
        @if($title)
          <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
        @endif
        
        @if($showBatchSelector)
          <div class="mr-4">
            {{ $batchSelector ?? '' }}
          </div>
        @endif
      </div>
    @endif

    {{-- Dynamic Rows Section (NEW) --}}
    @if(isset($dynamicRows))
      <div class="">
        {{ $dynamicRows }}
      </div>
    @endif

    {{-- Main Content Section with Border --}}
    @if(isset($mainSection))
      <div class="mb-6 rounded-lg border border-gray-200 p-5">
        <div class="grid grid-cols-1 gap-x-6 md:grid-cols-2 lg:gap-x-8">
          <div class="space-y-5">{{ $leftCol ?? '' }}</div>
          <div class="space-y-5">{{ $rightCol ?? '' }}</div>
        </div>
      </div>
    @else
      {{-- Default Layout without Border (for backward compatibility) --}}
      @if(isset($leftCol) || isset($rightCol))
        <div class="grid grid-cols-1 gap-x-6 md:grid-cols-2 lg:gap-x-8">
          <div class="space-y-5">{{ $leftCol ?? '' }}</div>
          <div class="space-y-5">{{ $rightCol ?? '' }}</div>
        </div>
      @endif
    @endif

    {{-- Detail Section with Border and Title --}}
    @if(isset($detailSection))
      <div class="mb-6">
        @if(isset($detailTitle))
          <h3 class="mb-4 text-base font-semibold text-gray-900">{{ $detailTitle }}</h3>
        @endif
        
        <div class="rounded-lg border border-gray-200 p-5">
          {{ $detailSection }}
        </div>
      </div>
    @endif

    {{-- Additional Sections --}}
    {{ $slot ?? '' }}

    {{-- Actions Section --}}
    @if (isset($actions))
      <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6">
        {{ $actions }}
      </div>
    @endif
  </form>
</div>