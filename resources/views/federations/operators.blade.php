@extends('layout')
@section('title', __('federations.show', ['name' => $federation->name]))

@section('content')

    @include('federations.navigation')

    @can('update', $federation)

        <div class="mb-4">
            <h3 class="text-lg font-semibold">{{ __('common.present_operators') }}</h3>
            <form id="delete_operators" action="{{ route('federations.update', $federation) }}" method="post">
                @csrf
                @method('patch')
                <input type="hidden" name="action" value="delete_operators">
                <div class="bg-gray-100 dark:bg-transparent border rounded-lg overflow-x-auto">
                    <table class="min-w-full border-b border-gray-300">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b bg-gray-100 dark:bg-gray-700 text-left text-xs tracking-widest uppercase">
                                    &nbsp;
                                </th>
                                <th class="px-6 py-3 border-b bg-gray-100 dark:bg-gray-700 text-left text-xs tracking-widest uppercase">
                                    {{ __('common.name') }}
                                </th>
                                <th class="px-6 py-3 border-b bg-gray-100 dark:bg-gray-700 text-left text-xs tracking-widest uppercase">
                                    {{ __('common.email') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 checkable">
                            @forelse ($operators as $operator)
                                <tr class="bg-white" role="button">
                                    <td class="px-6 py-3 text-sm">
                                        <input class="rounded" type="checkbox" name="operators[]" value="{{ $operator->id }}">
                                    </td>
                                    <td class="px-6 py-3 text-sm whitespace-nowrap">
                                        {{ $operator->name }}
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <a class="text-blue-500 hover:underline" href="mailto:{{ $operator->email }}">{{ $operator->email }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-3 bg-white font-bold text-center" colspan="4">{{ __('common.no_operators') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $operators->links() }}
                    @if (count($operators))
                        <div class="px-4 py-3 bg-gray-100">
                            <x-button color="red" target="true" data-target="delete_operators">{{ __('common.delete_operators') }}</x-button>
                            <x-modals.confirm :model="$federation" form="delete_operators"/>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <div>
            <h3 class="text-lg font-semibold">{{ __('common.add_operators') }}</h3>
            <div class="mb-4">
                <form class="">
                    <label class="sr-only" for="search">{{ __('common.search') }}</label>
                    <input class="px-4 py-2 w-full border rounded-lg dark:bg-transparent" type="text" name="search" id="search" value="{{ request('search') }}" placeholder="{{ __('users.searchbox') }}">
                </form>
            </div>
            <form id="add_operators" action="{{ route('federations.update', $federation) }}" method="post">
                @csrf
                @method('patch')
                <input type="hidden" name="action" value="add_operators">
                <div class="bg-gray-100 dark:bg-transparent border rounded-lg overflow-x-auto">
                    <table class="min-w-full border-b border-gray-300">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b bg-gray-100 dark:bg-gray-700 text-left text-xs tracking-widest uppercase">
                                    &nbsp;
                                </th>
                                <th class="px-6 py-3 border-b bg-gray-100 dark:bg-gray-700 text-left text-xs tracking-widest uppercase">
                                    {{ __('common.name') }}
                                </th>
                                <th class="px-6 py-3 border-b bg-gray-100 dark:bg-gray-700 text-left text-xs tracking-widest uppercase">
                                    {{ __('common.email') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 checkable">
                            @forelse ($users as $user)
                                <tr class="bg-white" role="button">
                                    <td class="px-6 py-3 text-sm">
                                        <input class="rounded" type="checkbox" name="operators[]" value="{{ $user->id }}">
                                    </td>
                                    <td class="px-6 py-3 text-sm whitespace-nowrap">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <a class="text-blue-500 hover:underline" href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-3 bg-white font-bold text-center" colspan="4">{{ __('common.no_operators') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $users->links() }}
                    @if (count($users))
                        <div class="px-4 py-3 bg-gray-100">
                            <x-button target="true" data-target="add_operators">{{ __('common.add_operators') }}</x-button>
                            <x-modals.confirm :model="$federation" form="add_operators"/>
                        </div>
                    @endif
                </div>
            </form>
        </div>

    @else

        <h3 class="text-lg font-semibold">{{ __('common.operators_list') }}</h3>
        <div class="bg-gray-100 border rounded-lg overflow-x-auto">
            <table class="min-w-full border-b border-gray-300">
                <thead>
                    <tr>
                        <th class="px-6 py-3 border-b bg-gray-100 text-left text-xs tracking-widest uppercase">{{ __('common.name') }}</th>
                        <th class="px-6 py-3 border-b bg-gray-100 text-left text-xs tracking-widest uppercase">{{ __('common.email') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($operators as $user)
                        <tr class="bg-white hover:bg-blue-50">
                            <td class="px-6 py-3 text-sm whitespace-nowrap">
                                {{ $user->name }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <a class="text-blue-500 hover:underline" href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3 bg-gray-100" colspan="2">
                                {{ __('common.no_operators') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $operators->links() }}
        </div>

    @endcan

@endsection