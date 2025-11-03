@props([
  'module' => 'item',
  'name' => null,
  'action' => '#',
])

<div x-data="{ open: false }" class="inline">
  <a href="#" @click.prevent="open = true" class="text-red-600 hover:text-red-800" title="Delete">
    <img src="{{ asset('delete-button.svg') }}" alt="Delete" class="inline h-5 w-5">
  </a>

  <div x-show="open"
       x-cloak
       x-transition.opacity
       class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/30">

    <div @click.away="open = false"
         class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">

      <h2 class="text-lg font-semibold text-gray-800 mb-4">
        Konfirmasi Hapus
      </h2>

      <p class="text-gray-600 mb-6">
        Anda yakin akan menghapus {{ strtolower($module) }}
        <span class="font-semibold text-gray-800">
          "{{ $name }}"
        </span>?
      </p>

      <div class="flex justify-end gap-3">
        <button @click="open = false"
                class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md cursor-pointer transition-colors duration-300">
          Batal
        </button>

        <form method="POST" action="{{ $action }}">
          @csrf
          @method('DELETE')
          <button type="submit"
                  class="px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-md cursor-pointer transition-colors duration-300">
            Hapus
          </button>
        </form>
      </div>

    </div>
  </div>
</div>
