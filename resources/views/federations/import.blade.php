@extends('layout')
@section('title', __('federations.import'))

@section('content')

    <form action="{{ route('federations.import') }}" method="post">
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
                            {{ __('common.name') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.description') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('federations.xml_id') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('federations.xml_name') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('federations.filters') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($federations as $federation)
                        <tr x-data class="hover:bg-blue-50 dark:hover:bg-gray-700" role="button"
                            @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded" type="checkbox" name="federations[]"
                                    value="{{ $federation['cfgfile'] }}">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded" type="text" name="names[{{ $federation['cfgfile'] }}]">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded" type="text" name="descriptions[{{ $federation['cfgfile'] }}]">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $federation['xml_id'] }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $federation['name'] }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $federation['filters'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="dark:bg-transparent px-4 py-4 bg-gray-100">
            <x-button>{{ __('federations.import') }}</x-button>
        </div>
    </form>

@endsection
