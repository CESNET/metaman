@extends('layout')
@section('title', __('federations.show', ['name' => $federation->name]))

@section('content')

    @include('federations.navigation')

    <h3 class="text-lg font-semibold">{{ __('federations.profile') }}</h3>
    <div class="dark:bg-gray-800 sm:rounded-lg mb-6 overflow-hidden bg-white shadow">
        <div>
            <dl>
                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">{{ __('common.name') }}</dt>
                    <dd class="sm:col-span-2">
                        <span class="pr-4">{{ $federation->name }}</span>
                        <x-pils.approved :model="$federation" />
                        <x-pils.status :model="$federation" />
                        <x-pils.state :model="$federation" />
                    </dd>
                </div>
                <div class="dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">
                        {{ __('common.description') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        {{ $federation->description }}
                    </dd>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">
                        {{ __('federations.xml_id') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        {{ $federation->xml_id }}
                    </dd>
                </div>
                <div class="dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">
                        {{ __('federations.xml_name') }}
                    </dt>
                    <dd class="sm:col-span-2">
                        <code class="text-sm text-pink-500">
                            {{ $federation->xml_name }}
                        </code>
                    </dd>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">
                        {{ __('federations.metadata_url') }}
                    </dt>
                    <dd class="sm:col-span-2">
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
                    </dd>
                </div>
                @can('do-everything')
                    <div class="dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                        <dt class="text-sm text-gray-500">
                            {{ __('federations.tagfile') }}
                        </dt>
                        <dd class="sm:col-span-2">
                            <code class="text-sm text-pink-500">
                                {{ $federation->tagfile }}
                            </code>
                        </dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                        <dt class="text-sm text-gray-500">
                            {{ __('federations.cfgfile') }}
                        </dt>
                        <dd class="sm:col-span-2">
                            <code class="text-sm text-pink-500">
                                {{ $federation->cfgfile }}
                            </code>
                        </dd>
                    </div>
                    <div class="dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                        <dt class="text-sm text-gray-500">
                            {{ __('federations.filters') }}
                        </dt>
                        <dd class="sm:col-span-2">
                            {{ $federation->filters }}
                        </dd>
                    </div>
                @endcan
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
            </dl>
        </div>
        <div class="px-6 py-3 bg-gray-100">
            <x-buttons.back href="{{ route('federations.index') }}" />

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

            @includeWhen(request()->user()->can('do-everything') && $federation->trashed(),
                'federations.partials.destroy')

            @includeWhen(request()->user()->can('update', $federation) &&
                    !$federation->trashed() &&
                    !$federation->approved,
                'federations.partials.reject')

            @includeWhen(request()->user()->can('update', $federation) &&
                    !$federation->trashed() &&
                    $federation->approved,
                'federations.partials.status')

            @includeWhen(request()->user()->can('update', $federation) &&
                    $federation->approved &&
                    !$federation->active,
                'federations.partials.state')

        </div>
    </div>

@endsection
