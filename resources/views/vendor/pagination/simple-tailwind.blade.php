@if ($paginator->hasPages())
    <div class="flex justify-center mt-4">
        <div class="inline-flex space-x-4">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                {{-- Don’t render anything, keeps layout centered --}}
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm rounded-lg shadow">
                    Previous
                </a>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm rounded-lg shadow">
                    Next
                </a>
            @endif
        </div>
    </div>
@endif



