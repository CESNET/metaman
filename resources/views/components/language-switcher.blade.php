<a href="{{$linkUrl }}" class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 whitespace-nowrap block px-4 py-2" >
    @empty(trim($slot))
        {{ strtoupper($switchTo) }}
    @else
        {{ $slot }}
    @endempty
</a>
