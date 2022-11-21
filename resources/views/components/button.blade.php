@props(['color' => 'blue'])

<button
    {{ $attributes->class([
        'px-4 py-2 rounded shadow',
        'bg-blue-600 hover:bg-blue-700 text-blue-50' => $color === 'blue',
        'bg-red-600 hover:bg-red-700 text-red-50' => $color === 'red',
        'bg-gray-600 hover:bg-gray-700 text-gray-50' => $color === 'gray',
        'bg-green-600 hover:bg-green-700 text-green-50' => $color === 'green',
    ]) }}
    type="submit">
    {{ $slot }}
</button>
