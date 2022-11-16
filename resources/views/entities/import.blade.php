@extends('layout')
@section('title', __('entities.import'))

@section('content')

    <form action="{{ route('entities.import') }}" method="post">
        @csrf
        <div class="dark:bg-transparent overflow-x-auto bg-white border rounded-lg">
            <table class="min-w-full border-b border-gray-300" x-data="selectAllCheckboxes()">
                <thead>
                    <tr>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            <input class="rounded" type="checkbox" @click="selectAll = !selectAll">
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.name') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.entityid') }}
                        </th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.federations') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($entities as $entity)
                        <tr class="hover:bg-blue-50 dark:hover:bg-gray-700" role="button">
                            <td class="px-6 py-3 text-sm">
                                <input class="rounded" type="checkbox" name="entities[]" value="{{ $entity['file'] }}"
                                    x-bind:checked="selectAll">
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $entity["name_$locale"] }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $entity['entityid'] }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                @if (isset($entity['federations']))
                                    @foreach ($entity['federations'] as $federation)
                                        {{ $federation }}@if (!$loop->last)
                                            ,
                                        @endif
                                    @endforeach
                                @else
                                @endif
                            </td>
                        </tr>
                        @empty

                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="dark:bg-transparent px-4 py-4 bg-gray-100">
                <x-button>{{ __('entities.import') }}</x-button>
            </div>
        </form>

    @endsection

    @push('scripts')
        <script defer>
            function selectAllCheckboxes() {
                return {
                    selectAll: false,
                };
            }
        </script>
    @endpush
