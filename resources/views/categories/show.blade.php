@extends('layouts.show')
@section('title', __('categories.show', ['name' => $category->name]))
@section('profile', __('categories.profile'))


@section('name',$category->name)

@section('specific_fields')

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.description') }}
        </x-slot>

        <x-slot name="dd">
            {{ $category->description }}
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.file') }}
        </x-slot>

        <x-slot name="dd">
            <code class="text-sm text-pink-500">
                {{ $category->tagfile }}
            </code>
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.entities') }}
        </x-slot>

        <x-slot name="dd">
            <ul class="list-decimal list-inside">
                @forelse ($category->entities as $entity)
                    <li><a class="hover:underline text-blue-500"
                           href="{{ route('entities.show', $entity) }}">{{ $entity->{"name_$locale"} ?: $entity->entityid }}</a>
                    </li>
                @empty
                    {{ __('categories.no_entities') }}
                @endforelse
            </ul>
        </x-slot>
    </x-element.body-section>

@endsection

@section('control_buttons')
    <x-buttons.back/>
    <x-buttons.edit href="{{ route('categories.edit', $category) }}"/>

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

@endsection



