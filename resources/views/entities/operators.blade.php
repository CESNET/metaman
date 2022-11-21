@extends('layout')
@section('title', __('entities.show', ['name' => $entity->{"name_$locale"}]))

@section('content')

    @include('entities.navigation')

    @can('update', $entity)

        <div class="mb-4">
            <h3 class="text-lg font-semibold">{{ __('common.present_operators') }}</h3>
            <form x-data="{ open: false }" id="delete_operators" action="{{ route('entities.update', $entity) }}" method="post">
                @csrf
                @method('patch')
                <input type="hidden" name="action" value="delete_operators">
                <div class="dark:bg-transparent overflow-x-auto bg-gray-100 border rounded-lg">
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
                                    {{ __('common.email') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($operators->sortBy('name') as $operator)
                                <tr x-data class="bg-white" role="button"
                                    @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                                    <td class="px-6 py-3 text-sm">
                                        <input class="rounded" type="checkbox" name="operators[]" value="{{ $operator->id }}">
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-sm">
                                        {{ $operator->name }}
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <a class="hover:underline text-blue-500"
                                            href="mailto:{{ $operator->email }}">{{ $operator->email }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-3 font-bold text-center bg-white" colspan="4">
                                        {{ __('common.no_operators') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $operators->links() }}
                    @if (count($operators))
                        <div class="px-4 py-3 bg-gray-100">
                            <x-button @click.prevent="open = !open" color="red">{{ __('common.delete_operators') }}
                            </x-button>

                            <x-modal>
                                <x-slot:title>
                                    {{ __('common.confirm_delete_operators') }}
                                </x-slot:title>
                                {{ __('common.confirm_delete_operators_body') }}
                            </x-modal>
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
                    <input class="dark:bg-transparent w-full px-4 py-2 border rounded-lg" type="text" name="search"
                        id="search" value="{{ request('search') }}" placeholder="{{ __('users.searchbox') }}">
                </form>
            </div>
            <form x-data="{ open: false }" id="add_operators" action="{{ route('entities.update', $entity) }}" method="post">
                @csrf
                @method('patch')
                <input type="hidden" name="action" value="add_operators">
                <div class="dark:bg-transparent overflow-x-auto bg-gray-100 border rounded-lg">
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
                                    {{ __('common.email') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($users as $user)
                                <tr x-data class="bg-white" role="button"
                                    @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                                    <td class="px-6 py-3 text-sm">
                                        <input class="rounded" type="checkbox" name="operators[]" value="{{ $user->id }}">
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-sm">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        <a class="hover:underline text-blue-500"
                                            href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-6 py-3 font-bold text-center bg-white" colspan="4">
                                        {{ __('common.no_operators') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $users->links() }}
                    @if (count($users))
                        <div class="px-4 py-3 bg-gray-100">
                            <x-button @click.prevent="open = !open">{{ __('common.add_operators') }}
                            </x-button>

                            <x-modal>
                                <x-slot:title>
                                    {{ __('common.confirm_add_operators') }}
                                </x-slot:title>
                                {{ __('common.confirm_add_operators_body') }}
                            </x-modal>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    @else
        <h3 class="text-lg font-semibold">{{ __('common.operators_list') }}</h3>
        <div class="overflow-x-auto bg-gray-100 border rounded-lg">
            <table class="min-w-full border-b border-gray-300">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.name') }}</th>
                        <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.email') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($operators as $user)
                        <tr class="hover:bg-blue-50 bg-white">
                            <td class="whitespace-nowrap px-6 py-3 text-sm">
                                {{ $user->name }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <a class="hover:underline text-blue-500"
                                    href="mailto:{{ $user->email }}">{{ $user->email }}</a>
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
