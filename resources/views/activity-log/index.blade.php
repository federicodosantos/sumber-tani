@php
  use Illuminate\Support\Str;
@endphp

<x-app-layout>
  <div class="py-6 flex justify-center font-mont">
    <div class="mx-auto w-full sm:px-6 lg:px-8">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Riwayat Aktivitas</h1>
          <p class="text-sm text-gray-500">Pantau aktivitas terbaru di sistem.</p>
        </div>
        <div class="text-sm text-gray-600">
          Total <span class="font-semibold text-gray-800">{{ $activities->total() }}</span> log
        </div>
      </div>

      <x-content.data-table>
        <x-slot name="sortOptions">
          <option value="created_at" {{ $currentSort === 'created_at' ? 'selected' : '' }}>Terbaru</option>
          <option value="description" {{ $currentSort === 'description' ? 'selected' : '' }}>Jenis Kegiatan</option>
          <option value="module" {{ $currentSort === 'module' ? 'selected' : '' }}>Modul</option>
          <option value="causer" {{ $currentSort === 'causer' ? 'selected' : '' }}>Pengguna</option>
        </x-slot>

        <x-slot name="header">
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">No</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Jenis Kegiatan</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Dilakukan Oleh</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Module</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Dibuat Pada</th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Action</th>
        </x-slot>

        <x-slot name="body">
          @forelse ($activities as $activity)
            @php
              $module = $activity->subject_type ? Str::headline(class_basename($activity->subject_type)) : '-';
              $performedBy = $activity->causer_username ?? $activity->causer?->username ?? 'System';
            @endphp
            <tr class="hover:bg-gray-50/60">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-700">
                {{ ($activities->firstItem() ?? 0) + $loop->index }}.
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-gray-800 uppercase tracking-wide">
                {{ $activity->description ?? $activity->event ?? '-' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                {{ $performedBy }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                {{ $module }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                {{ optional($activity->created_at)->translatedFormat('d F Y, H:i:s') }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <a href="{{ route('activity-log.show', $activity->id) }}"
                  class="text-button-main hover:text-button-hover transition-colors" title="Lihat detail aktivitas">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-6 py-6 text-center text-sm text-gray-500 italic">
                Belum ada riwayat aktivitas.
              </td>
            </tr>
          @endforelse
        </x-slot>

        <x-slot name="showing">
          Showing
          <span class="font-medium">{{ $activities->firstItem() ?? 0 }}-{{ $activities->lastItem() ?? 0 }}</span>
          data of
          <span class="font-medium">{{ $activities->total() }}</span> entries
        </x-slot>

        <x-slot name="pagination">
          {{ $activities->onEachSide(1)->links() }}
        </x-slot>
      </x-content.data-table>
    </div>
  </div>
</x-app-layout>
