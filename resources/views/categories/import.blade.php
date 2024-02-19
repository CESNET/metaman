@extends('layout')
@section('title', __('categories.import'))

@section('content')

    <form action="{{ route('categories.import') }}" method="post">
        @csrf
        <div class="dark:bg-transparent overflow-x-auto bg-white border rounded-lg">
            <table class="min-w-full border-b border-gray-300">
                <thead>
                    <tr>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            &nbsp;
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.file') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.name') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.description') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($categories as $category)
                        <tr x-data class="hover:bg-blue-50 dark:hover:bg-gray-700"
                            @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                            <td class="px-6 py-3 text-sm">
                                <input @click.stop class="rounded" type="checkbox" name="categories[]"
                                    value="{{ $category }}">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <code class="text-sm text-pink-500">
                                    {{ $category }}
                                </code>
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded" type="text" name="names[{{ $category }}]">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded" type="text" name="descriptions[{{ $category }}]">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="dark:bg-transparent px-4 py-4 bg-gray-100">
            <x-button>{{ __('categories.import') }}</x-button>
        </div>
    </form>

@endsection
