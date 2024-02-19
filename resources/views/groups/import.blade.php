@extends('layout')
@section('title', __('groups.import'))

@section('content')

    <form action="{{ route('groups.import') }}" method="post">
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
                    @foreach ($groups as $group)
                        <tr x-data class="hover:bg-blue-50 dark:hover:bg-gray-700"
                            @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                            <td class="px-6 py-3 text-sm">
                                <input @click.stop class="rounded" type="checkbox" name="groups[]"
                                    value="{{ $group }}">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <code class="text-sm text-pink-500">
                                    {{ $group }}
                                </code>
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded-lg" type="text" name="names[{{ $group }}]">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded-lg" type="text" name="descriptions[{{ $group }}]">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="dark:bg-transparent px-4 py-4 bg-gray-100">
            <x-button>{{ __('groups.import') }}</x-button>
        </div>
    </form>

@endsection
