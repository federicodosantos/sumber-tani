@props([
    'search' => true,
    'sortOptions' => null,
    'header',
    'body',
    'showing' => null,
    'pagination' => null,
])
<div class="overflow-hidden rounded-3xl bg-white shadow-sm" style="border: 1px solid #e5e7eb;">

    {{-- HEADER: Search + Sort --}}
    <div class="flex flex-col items-center justify-between gap-4 border-b border-gray-200 p-4 sm:flex-row sm:p-5">

        {{-- 🔍 SEARCH FORM (optional) --}}
        @if ($search)
            <form action="{{ route(Route::currentRouteName()) }}" method="GET" class="w-full sm:max-w-xs">
                <div class="relative w-full">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>

                    <input type="text" name="search" placeholder="Search" value="{{ request('search') }}"
                        class="block w-full rounded-lg border border-gray-300 py-2 pl-10 pr-3 text-sm focus:border-blue-500 focus:ring-blue-500">

                    {{-- Keep sort & direction when searching --}}
                    @if (request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if (request('direction'))
                        <input type="hidden" name="direction" value="{{ request('direction') }}">
                    @endif
                </div>
            </form>
        @endif

        {{-- SORT OPTIONS --}}
        @if (isset($sortOptions))
            <form action="{{ route(Route::currentRouteName()) }}" method="GET" class="flex items-center gap-2 text-sm">
                @if (request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif

                <span class="text-gray-600">Sort by:</span>
                <select name="sort"
                    class="rounded-lg border-gray-300 text-sm font-medium focus:border-blue-500 focus:ring-blue-500"
                    onchange="this.form.submit()">
                    {{ $sortOptions }}
                </select>
            </form>
        @endif
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
                <tr>
                    {{ $header }}
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                {{ $body }}
            </tbody>
        </table>
    </div>

    {{-- FOOTER: showing + pagination --}}
    <div class="flex flex-col items-center justify-between border-t border-gray-200 p-4 sm:flex-row sm:p-5">

        {{-- Showing N entries --}}
        @if (isset($showing))
            <p class="mb-4 text-sm text-gray-700 sm:mb-0">
                {{ $showing }}
            </p>
        @else
            <div></div>
        @endif

        {{-- Pagination --}}
        @if (isset($pagination))
            <nav class="flex items-center gap-1">
                {{ $pagination }}
            </nav>
        @endif
    </div>

</div>
