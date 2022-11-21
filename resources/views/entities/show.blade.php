@extends('layout')
@section('title', __('entities.show', ['name' => $entity->{"name_$locale"}]))

@section('content')

    @include('entities.navigation')

    <h3 class="text-lg font-semibold">{{ __('entities.profile') }}</h3>
    <div class="dark:bg-gray-800 sm:rounded-lg mb-6 overflow-hidden bg-white shadow">
        <div>
            <dl>
                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">{{ __('common.name') }}</dt>
                    <dd class="sm:col-span-2">
                        <span class="pr-4">{{ $entity->{"name_$locale"} ?? __('entities.no_name') }}</span>
                        <x-pils.approved :model="$entity" />
                        <x-pils.status :model="$entity" />
                        <x-pils.state :model="$entity" />
                    </dd>
                </div>
                <div class="dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">{{ __('common.description') }}</dt>
                    <dd class="sm:col-span-2">{{ $entity->{"description_$locale"} ?? __('entities.no_description') }}</dd>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">{{ __('common.entityid') }}</dt>
                    <dd class="sm:col-span-2"><code class="text-sm text-pink-500">{{ $entity->entityid }}</code></dd>
                </div>
                <div class="dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">{{ __('common.type') }}</dt>
                    <dd class="sm:col-span-2">{{ $entity->type->name }}</dd>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">{{ __('common.entity_categories_selfasserted') }}</dt>
                    <dd class="sm:col-span-2">
                        <x-pil linethrough="{{ !$entity->cocov1 }}">{{ __('common.cocov1') }}</x-pil>
                        <x-pil linethrough="{{ !$entity->sirtfi }}">{{ __('common.sirtfi') }}</x-pil>
                    </dd>
                </div>
                <div class="dark:bg-gray-800 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                    <dt class="text-sm text-gray-500">{{ __('common.entity_categories_controlled') }}</dt>
                    <dd class="sm:col-span-2">
                        @includeWhen(request()->user()->can('do-everything') &&
                                !$entity->trashed() &&
                                $entity->active &&
                                $entity->approved &&
                                $entity->type->value === 'sp',
                            'entities.partials.rs')

                        @includeWhen(request()->user()->cannot('do-everything') &&
                                request()->user()->can('update', $entity) &&
                                $entity->type->value === 'sp' &&
                                $entity->federations()->where('xml_name', Config::get('git.rs_federation'))->count() &&
                                !$entity->rs,
                            'entities.partials.askrs')

                        <x-pil linethrough="{{ !$entity->rs }}">{{ __('common.rs') }}</x-pil>

                        @includeWhen(request()->user()->can('do-everything') &&
                                !$entity->trashed() &&
                                $entity->active &&
                                $entity->approved &&
                                $entity->type->value === 'idp',
                            'entities.partials.hfd')
                        @if ($entity->type->value === 'idp')
                            <x-pil linethrough="{{ !$entity->hfd }}">{{ __('common.hfd') }}</x-pil>
                        @endif
                    </dd>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">{{ __('common.edugain_membership') }}</dt>
                    <dd class="sm:col-span-2">
                        @includeWhen(request()->user()->can('update', $entity) &&
                                !$entity->trashed() &&
                                $entity->active &&
                                $entity->approved,
                            'entities.partials.edugain')
                        @cannot('update', $entity)
                            @if ($entity->edugain)
                                {{ __('common.yes') }}
                            @else
                                {{ __('common.no') }}
                            @endif
                        @endcannot
                    </dd>
                </div>
                @can('do-everything')
                    @if ($entity->type->value === 'idp')
                        <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                            <dt class="text-sm text-gray-500">
                                {{ __('common.eduidcz_category') }}
                            </dt>
                            <dd class="sm:col-span-2">
                                <form class="inline-block" action="{{ route('entities.update', $entity) }}" method="post">
                                    @csrf
                                    @method('patch')
                                    <input type="hidden" name="action" value="category">
                                    <select class="text-sm rounded" name="category" id="category">
                                        <option value="">{{ __('categories.select_category') }}</option>
                                        @forelse ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                @if ($category->id === $entity->category_id) {{ 'selected' }} @endif>
                                                {{ $category->name }}</option>
                                        @empty
                                            <option value="">{{ __('categories.no_categories') }}</option>
                                        @endforelse
                                    </select>
                                    <button class="hover:bg-gray-200 px-4 py-2 text-gray-600 bg-gray-300 rounded shadow"
                                        type="reset">{{ __('common.reset') }}</button>
                                    <x-button>{{ __('common.update') }}</x-button>
                                </form>
                            </dd>
                        </div>
                    @endif
                @endcan
            </dl>
        </div>
        <div class="px-6 py-3 bg-gray-100">
            <x-buttons.back href="{{ route('entities.index') }}" />
            <a class="hover:bg-blue-700 text-blue-50 inline-block px-4 py-2 bg-blue-600 rounded shadow"
                href="{{ route('entities.metadata', $entity) }}">{{ __('entities.get_metadata') }}</a>
            <a class="hover:bg-blue-700 text-blue-50 inline-block px-4 py-2 bg-blue-600 rounded shadow"
                href="{{ route('entities.showmetadata', $entity) }}"
                target="_blank">{{ __('entities.show_metadata') }}</a>

            @can('update', $entity)
                @unless($entity->trashed())
                    <a class="hover:bg-yellow-200 inline-block px-4 py-2 text-yellow-600 bg-yellow-300 rounded shadow"
                        href="{{ route('entities.edit', $entity) }}">{{ __('common.edit') }}</a>
                @endunless
            @endcan

            @includeWhen(request()->user()->can('do-everything') && $entity->trashed(),
                'entities.partials.destroy')

            @includeWhen(request()->user()->can('update', $entity) &&
                    !$entity->trashed() &&
                    $entity->approved,
                'entities.partials.status')

            @includeWhen(request()->user()->can('update', $entity) &&
                    $entity->approved &&
                    !$entity->active,
                'entities.partials.state')

        </div>
    </div>

@endsection
