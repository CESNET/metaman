@extends('layout')
@section('title', __('federations.show', ['name' => $federation->name]))

@section('content')

    @include('federations.navigation')

    @can('update', $federation)

        <div class="mb-4">
            <h3 class="text-lg font-semibold">{{ __('common.present_operators') }}</h3>
            @include('federations.operatorForm.delete')
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
            @include('federations.operatorForm.add')
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
