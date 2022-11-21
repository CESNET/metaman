@extends('layout')
@section('title', __('categories.show', ['name' => $category->name]))

@section('content')

    <h3 class="text-lg font-semibold">{{ __('categories.profile') }}</h3>
    <div class="sm:rounded-lg mb-6 overflow-hidden bg-white shadow">
        <div>
            <dl>
                <div class="bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.name') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        {{ $category->name }}
                    </dd>
                </div>
                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.description') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        {{ $category->description }}
                    </dd>
                </div>
                <div class="bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.file') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        <code class="text-sm text-pink-500">
                            {{ $category->tagfile }}
                        </code>
                    </dd>
                </div>
                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.entities') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        <ul class="list-decimal list-inside">
                            @forelse ($category->entities as $entity)
                                <li><a class="hover:underline text-blue-500"
                                        href="{{ route('entities.show', $entity) }}">{{ $entity->{"name_$locale"} ?: $entity->entityid }}</a>
                                </li>
                            @empty
                                {{ __('categories.no_entities') }}
                            @endforelse
                        </ul>
                    </dd>
                </div>
            </dl>
        </div>
        <div class="px-6 py-3 bg-gray-100">
            <x-buttons.back href="{{ route('categories.index') }}" />
            <a class="hover:bg-yellow-200 inline-block px-4 py-2 text-yellow-600 bg-yellow-300 rounded shadow"
                href="{{ route('categories.edit', $category) }}">{{ __('common.edit') }}</a>

            @if (count($category->entities) === 0)
                <form x-data="{ open: false }" class="inline-block" action="{{ route('categories.destroy', $category) }}"
                    method="POST">
                    @csrf
                    @method('delete')

                    <x-button color="red" @click.prevent="open = !open">{{ __('common.destroy') }}</x-button>

                    <x-modal>
                        <x-slot:title>{{ __('common.destroy_model', ['name' => $category->name]) }}</x-slot:title>
                        {{ __('common.destroy_model_body', ['name' => $category->name, 'type' => 'category']) }}
                    </x-modal>
                </form>
            @endif
        </div>
    </div>

@endsection
