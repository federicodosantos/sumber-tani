@php
  use Illuminate\Support\Str;
@endphp

<x-app-layout>
  <div class="py-6 flex justify-center font-mont">
    <div class="mx-auto w-full sm:px-6 lg:px-8">
      <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">History Activity</h1>
        <a href="{{ route('activity-log.index') }}"
          class="inline-flex items-center gap-2 rounded-lg bg-button-main px-4 py-2 text-sm font-medium text-white hover:bg-button-hover focus:outline-none focus:ring-2 focus:ring-button-hover focus:ring-offset-2 active:scale-95 transition-transform">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12l7.5-7.5M3 12h18" />
          </svg>
          Back
        </a>
      </div>

      @php
        $properties = $activity->properties?->toArray() ?? [];
        $changes = $activity->changes();
      @endphp

      <div class="rounded-3xl bg-white shadow-sm border border-gray-200 overflow-hidden">
        <div class="grid grid-cols-1">
          <div class="grid grid-cols-12 border-b border-gray-200">
            <div class="col-span-3 px-6 py-5 text-xs uppercase tracking-wide text-gray-600 font-semibold">Kegiatan</div>
            <div class="col-span-9 px-6 py-5 text-sm text-gray-900 font-semibold">
              {{ $activity->description ?? $activity->event ?? '-' }}
            </div>
          </div>
          <div class="grid grid-cols-12 border-b border-gray-200">
            <div class="col-span-3 px-6 py-5 text-xs uppercase tracking-wide text-gray-600 font-semibold">Causer</div>
            <div class="col-span-9 px-6 py-5 text-sm text-gray-900">
              {{ $activity->causer?->username ?? 'System' }}
              @if ($activity->role ?? $activity->causer?->role)
                <span class="ml-2 text-gray-500">({{ $activity->role ?? $activity->causer?->role }})</span>
              @endif
            </div>
          </div>
          <div class="grid grid-cols-12 border-b border-gray-200">
            <div class="col-span-3 px-6 py-5 text-xs uppercase tracking-wide text-gray-600 font-semibold">Module</div>
            <div class="col-span-9 px-6 py-5 text-sm text-gray-900">{{ $module }}</div>
          </div>
          <div class="grid grid-cols-12 border-b border-gray-200">
            <div class="col-span-3 px-6 py-5 text-xs uppercase tracking-wide text-gray-600 font-semibold">Properties</div>
            <div class="col-span-9 px-6 py-5 text-sm text-gray-900">
              @if (empty($properties))
                <p class="text-gray-500 italic">Tidak ada properties.</p>
              @else
                <pre class="whitespace-pre-wrap break-words font-mono text-xs bg-gray-50 border border-gray-200 rounded-lg p-4 leading-relaxed text-gray-800">{{ json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
              @endif
            </div>
          </div>

          @if ($changes && ($changes->get('attributes') || $changes->get('old')))
            <div class="grid grid-cols-12 border-b border-gray-200">
              <div class="col-span-3 px-6 py-5 text-xs uppercase tracking-wide text-gray-600 font-semibold">Data Baru</div>
              <div class="col-span-9 px-6 py-5 text-sm text-gray-900">
                @forelse ($changes->get('attributes', []) as $field => $value)
                  <div class="mb-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">{{ Str::headline($field) }}</p>
                    <pre class="whitespace-pre-wrap break-words font-mono text-xs bg-green-50 border border-green-200 rounded-lg p-4 leading-relaxed text-gray-800">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                  </div>
                @empty
                  <p class="text-gray-500 italic">Tidak ada data baru.</p>
                @endforelse
              </div>
            </div>

            <div class="grid grid-cols-12 border-b border-gray-200">
              <div class="col-span-3 px-6 py-5 text-xs uppercase tracking-wide text-gray-600 font-semibold">Data Lama</div>
              <div class="col-span-9 px-6 py-5 text-sm text-gray-900">
                @forelse ($changes->get('old', []) as $field => $value)
                  <div class="mb-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">{{ Str::headline($field) }}</p>
                    <pre class="whitespace-pre-wrap break-words font-mono text-xs bg-red-50 border border-red-200 rounded-lg p-4 leading-relaxed text-gray-800">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                  </div>
                @empty
                  <p class="text-gray-500 italic">Tidak ada data lama.</p>
                @endforelse
              </div>
            </div>
          @endif

          <div class="grid grid-cols-12">
            <div class="col-span-3 px-6 py-5 text-xs uppercase tracking-wide text-gray-600 font-semibold">Created At</div>
            <div class="col-span-9 px-6 py-5 text-sm text-gray-900">
              {{ optional($activity->created_at)->translatedFormat('d F Y, H:i') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
