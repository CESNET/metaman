@extends('layouts.show')
@section('title', __('groups.show', ['name' => $group->name]))
@section('profile', __('groups.profile'))

@section('name',$group->name)

@section('specific_fields')

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.description') }}
        </x-slot>

        <x-slot name="dd">
            {{ $group->description }}
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.file') }}
        </x-slot>

        <x-slot name="dd">
            <code class="text-sm text-pink-500">
                {{ $group->tagfile }}
            </code>
        </x-slot>
    </x-element.body-section>


    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.entities') }}
        </x-slot>

        <x-slot name="dd">

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

        </x-slot>
    </x-element.body-section>

@endsection

@section('control_buttons')
    <x-buttons.back/>
    <x-buttons.edit href="{{ route('groups.edit', $group) }}"/>

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
@endsection

