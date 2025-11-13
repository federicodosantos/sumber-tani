@props(['name'])

@php
    $currentSort = request('sort');
    $currentDirection = request('direction', 'asc');

    $isSortingThis = $currentSort == $name;
    $newDirection = $isSortingThis && $currentDirection == 'asc' ? 'desc' : 'asc';

    $url = route(
        Route::currentRouteName(),
        array_merge(
            request()->query(),
            ['sort' => $name, 'direction' => $newDirection], 
        ),
    );
@endphp

<th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">
    <a href="{{ $url }}" class="flex items-center gap-1.5">
        {{ $slot }} 

        @if ($isSortingThis)
            @if ($currentDirection == 'asc')
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.03 9.83a.75.75 0 0 1-1.06-1.06l5.25-5.25a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 1 1-1.06 1.06L10.75 5.612V16.25a.75.75 0 0 1-.75.75Z"
                        clip-rule="evenodd" />
                </svg>
            @else
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a.75.75 0 0 1 .75.75v10.638l4.22-4.22a.75.75 0 1 1 1.06 1.06l-5.25 5.25a.75.75 0 0 1-1.06 0l-5.25-5.25a.75.75 0 1 1 1.06-1.06l4.22 4.22V3.75A.75.75 0 0 1 10 3Z"
                        clip-rule="evenodd" />
                </svg>
            @endif
        @endif
    </a>
</th>
