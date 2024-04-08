@extends('layouts.show')
@section('title', __('federations.show', ['name' => $federation->name]))
@section('profile', __('federations.profile'))
@section('name',$federation->name)

@section('navigation')
    @include('federations.navigation')
@endsection

@section('model')
    <x-status :model="$federation" />
@endsection

@section('specific_fields')

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.description') }}
        </x-slot>

        <x-slot name="dd">
            {{ $federation->description }}
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('federations.xml_id') }}
        </x-slot>

        <x-slot name="dd">
            {{ $federation->xml_id }}
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('federations.xml_name') }}
        </x-slot>

        <x-slot name="dd">
            <code class="text-sm text-pink-500">
                {{ $federation->xml_name }}
            </code>
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('federations.metadata_url') }}
        </x-slot>

        <x-slot name="dd">
            <ul>
                @foreach (explode(', ', $federation->filters) as $filter)
                    <li>
                        <a class="font-mono text-sm text-blue-500"
                           href="{{ config('git.metadata_base_url') }}/{{ $filter }}" target="_blank">
                            {{ config('git.metadata_base_url') }}/{{ $filter }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-slot>
    </x-element.body-section>
@endsection

@section('admin_section')

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('federations.tagfile') }}
        </x-slot>

        <x-slot name="dd">
            <code class="text-sm text-pink-500">
                {{ $federation->tagfile }}
            </code>
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('federations.cfgfile') }}
        </x-slot>

        <x-slot name="dd">
            <code class="text-sm text-pink-500">
                {{ $federation->cfgfile }}
            </code>
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('federations.filters') }}
        </x-slot>

        <x-slot name="dd">
            {{ $federation->filters }}
        </x-slot>
    </x-element.body-section>

@endsection

@section('specific_condition')
    @unless($federation->approved)
        <div class="bg-green-50 dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 font-bold">
            <dt class="text-sm text-gray-500">
                {{ __('common.explanation') }}
            </dt>
            <dd class="sm:col-span-2">
                {{ $federation->explanation }}
            </dd>
        </div>
        @endunless
@endsection

@section('control_buttons')
    <x-buttons.back/>

    @can('update', $federation)
        @unless($federation->trashed())
            <a class="hover:bg-yellow-200 inline-block px-4 py-2 text-yellow-600 bg-yellow-300 rounded shadow"
               href="{{ route('federations.edit', $federation) }}">{{ __('common.edit') }}</a>
        @endunless
    @endcan

    @includeWhen(request()->user()->can('do-everything') &&
            !$federation->approved &&
            !$federation->trashed(),
        'federations.partials.approve')

    @includeWhen(request()->user()->can('update', $federation) &&
            !$federation->trashed() &&
            !$federation->approved,
        'federations.partials.reject')

    @includeWhen(request()->user()->can('update', $federation) && $federation->approved,
        'federations.partials.state')

    @includeWhen(request()->user()->can('do-everything') && $federation->trashed(),
        'federations.partials.destroy')

@endsection
