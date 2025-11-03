@props(['action', 'method' => 'POST'])

@php
  $formMethod = strtoupper($method);
@endphp

<div class="rounded-2xl bg-white p-6 shadow-sm sm:p-8" style="border: 1px solid #e5e7eb;">
  <form action="{{ $action }}" method="{{ $formMethod === 'GET' ? 'GET' : 'POST' }}" {{ $attributes }}>
    @csrf

    @if (!in_array($formMethod, ['GET', 'POST']))
      @method($formMethod)
    @endif

    <div class="grid grid-cols-1 gap-x-6 md:grid-cols-2 lg:gap-x-8">
      <div class="space-y-5">{{ $leftCol ?? '' }}</div>
      <div class="space-y-5">{{ $rightCol ?? '' }}</div>
    </div>

    @if (isset($actions))
      <div class="mt-8 flex items-center justify-between pt-6">
        {{ $actions }}
      </div>
    @endif
  </form>
</div>
