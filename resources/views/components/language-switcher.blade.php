<a href="{{ $linkUrl }}"
    class="hover:bg-gray-400 hover:text-gray-900 whitespace-nowrap inline-block px-4 py-2 rounded">
    @empty(trim($slot))
        {{ strtoupper($switchTo) }}
    @else
        {{ $slot }}
    @endempty
</a>
