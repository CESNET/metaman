@extends('layout')
@section('title', __('federations.show', ['name' => $federation->name]))

@section('content')

    @include('federations.navigation')

    @can('update', $federation)

        <div class="mb-4">
            <h3 class="text-lg font-semibold">
                {{ __('common.delete_members') }}
            </h3>
            @include('federations.entityForm.delete')
        </div>

        <div>
            <h3 class="text-lg font-semibold">
                {{ __('common.add_members') }}
            </h3>
            <div class="mb-4">
                <form>
                    <label class="sr-only" for="search">{{ __('common.search') }}</label>
                    <input class="dark:bg-transparent w-full px-4 py-2 border rounded-lg" type="text" name="search"
                        id="search" value="{{ request('search') }}" placeholder="{{ __('entities.searchbox') }}">
                </form>
            </div>
            @include('federations.entityForm.add')
        </div>
    @else
        <h3 class="text-lg font-semibold">{{ __('common.entities_list') }}</h3>
        <div class="overflow-x-auto bg-gray-100 border rounded-lg">
            <table class="min-w-full border-b border-gray-300">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">{{ __('common.name') }}
                        </th>
                        <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">
                            {{ __('common.description') }}</th>
                        <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">
                            {{ __('common.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($members as $entity)
                        <tr class="hover:bg-blue-50 bg-white">
                            <td class="px-6 py-3 text-sm">
                                {{ $entity->{"name_$locale"} }}
                                <div class="text-gray-500">
                                    {{ $entity->entityid }}
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $entity->{"description_$locale"} ?: __('entities.no_description') }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <x-status :model="$federation" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3 bg-gray-100" colspan="3">
                                {{ __('common.no_entities') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $members->links() }}
        </div>

    @endcan

@endsection
