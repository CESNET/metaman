@extends('layout')
@section('title', __('groups.show', ['name' => $group->name]))

@section('content')

    <h3 class="text-lg font-semibold">
        {{ __('groups.profile') }}
    </h3>
    <div class="sm:rounded-lg mb-6 overflow-hidden bg-white shadow">
        <div>
            <dl>
                <div class="bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.name') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        {{ $group->name }}
                    </dd>
                </div>
                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.description') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        {{ $group->description }}
                    </dd>
                </div>
                <div class="bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.file') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        <code class="text-sm text-pink-500">
                            {{ $group->tagfile }}
                        </code>
                    </dd>
                </div>
                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.entities') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        <ul class="list-decimal list-inside">
                            @forelse ($group->entities as $entity)
                                <li>
                                    <a class="hover:underline text-blue-500"
                                        href="{{ route('entities.show', $entity) }}">{{ $entity->{"name_$locale"} ?: $entity->entityid }}</a>
                                </li>
                            @empty
                                {{ __('groups.no_entities') }}
                            @endforelse
                        </ul>
                    </dd>
                </div>
            </dl>
        </div>
        <div class="px-6 py-3 bg-gray-100">
            <x-buttons.back href="{{ route('groups.index') }}" />
            <a class="hover:bg-yellow-200 inline-block px-4 py-2 text-yellow-600 bg-yellow-300 rounded shadow"
                href="{{ route('groups.edit', $group) }}">{{ __('common.edit') }}</a>

            @if (count($group->entities) === 0)
                <form x-data="{ open: false }" class="inline-block" action="{{ route('groups.destroy', $group) }}"
                    method="POST">
                    @csrf
                    @method('delete')

                    <x-button color="red" @click.prevent="open = !open">{{ __('common.destroy') }}</x-button>

                    <x-modal>
                        <x-slot:title>{{ __('common.destroy_model', ['name' => $group->name]) }}</x-slot:title>
                        {{ __('common.destroy_model_body', ['name' => $group->name, 'type' => 'group']) }}
                    </x-modal>
                </form>
            @endif

        </div>
    </div>

@endsection
