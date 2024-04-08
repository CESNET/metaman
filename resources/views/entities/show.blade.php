@extends('layouts.show')
@section('title', __('entities.show', ['name' => $entity->{"name_$locale"}]))
@section('profile', __('entities.profile'))

@section('name')
    {{$entity->{"name_$locale"} ?? __('entities.no_name')}}
@endsection

@section('navigation')
    @include('entities.navigation')
@endsection

@section('model')
    <x-status :model="$entity" />
@endsection

@section('specific_fields')

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.description') }}
        </x-slot>

        <x-slot name="dd">
            {{ $entity->{"description_$locale"} ?? __('entities.no_description') }}
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.entityid') }}
        </x-slot>

        <x-slot name="dd">
            <code class="text-sm text-pink-500">{{ $entity->entityid }}</code>
        </x-slot>
    </x-element.body-section>


    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.type') }}
        </x-slot>

        <x-slot name="dd">
            {{ $entity->type->name }}
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.entity_categories_selfasserted') }}
        </x-slot>

        <x-slot name="dd">
            <x-pil linethrough="{{ !$entity->cocov1 }}">{{ __('common.cocov1') }}</x-pil>
            <x-pil linethrough="{{ !$entity->sirtfi }}">{{ __('common.sirtfi') }}</x-pil>
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.entity_categories_controlled') }}
        </x-slot>

        <x-slot name="dd">
            @includeWhen(request()->user()->can('do-everything') &&
         !$entity->trashed() &&
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
                    $entity->approved &&
                    $entity->type->value === 'idp',
                'entities.partials.hfd')
            @if ($entity->type->value === 'idp')
                <x-pil linethrough="{{ !$entity->hfd }}">{{ __('common.hfd') }}</x-pil>
            @endif
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.edugain_membership') }}
        </x-slot>

        <x-slot name="dd">
            @includeWhen(request()->user()->can('update', $entity) &&
          !$entity->trashed() &&
          $entity->approved,
                'entities.partials.edugain')
            @cannot('update', $entity)
                @if ($entity->edugain)
                    {{ __('common.yes') }}
                @else
                    {{ __('common.no') }}
                @endif
            @endcannot
        </x-slot>
    </x-element.body-section>
@endsection

@section('admin_section')
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

@endsection

@section('specific_condition')
    @env(['production', 'local'])
        @if ($entity->type->value === 'idp' && !$entity->hfd)
            <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                <dt class="text-sm text-gray-500">{{ __('common.organization') }}</dt>
                <dd class="sm:col-span-2">
                    @if ($eduidczOrganization)
                        <a class="hover:underline text-blue-500"
                           href="https://ciselnik.cesnet.cz/organizations/{{ $eduidczOrganization->getFirstAttribute('oPointer') }}">{{ $cesnetOrganization->getFirstAttribute('o') }}</a>
                    @else
                        <form class="inline-block" action="{{ route('entities.organization', $entity) }}"
                              method="post">
                            @csrf
                            <input type="hidden" name="action" value="organization">
                            <input class="w-96 px-4 py-2 mb-2 border rounded shadow" type="text"
                                   name="organization" id="organization" list="cesnetOrganizations" required>
                            <datalist id="cesnetOrganizations">
                                @foreach ($cesnetOrganizations as $o)
                                    <option value="{{ Str::remove('dc=', $o->getRdn()) }}">
                                        {{ $o->getFirstAttribute('o') }}</option>
                                @endforeach
                            </datalist>
                            <button class="hover:bg-gray-200 px-4 py-2 text-gray-600 bg-gray-300 rounded shadow"
                                    type="reset">{{ __('common.reset') }}</button>
                            <x-button>{{ __('common.update') }}</x-button>
                        </form>
                        {!! $errors->first('organization', '<div class="float-left text-sm font-semibold text-red-600">:message</div>') !!}
                    @endif
                </dd>
            </div>
        @endif
    @endenv
@endsection


@section('control_buttons')
    <x-buttons.back/>

    @if ($entity->approved)
        <a class="hover:bg-blue-700 text-blue-50 inline-block px-4 py-2 bg-blue-600 rounded shadow"
           href="{{ route('entities.metadata', $entity) }}">{{ __('entities.get_metadata') }}</a>
        <a class="hover:bg-blue-700 text-blue-50 inline-block px-4 py-2 bg-blue-600 rounded shadow"
           href="{{ route('entities.showmetadata', $entity) }}"
           target="_blank">{{ __('entities.show_metadata') }}</a>
    @else
        <a class="hover:bg-blue-700 text-blue-50 inline-block px-4 py-2 bg-blue-600 rounded shadow"
           href="{{ route('entities.previewmetadata', $entity) }}"
           target="_blank">{{ __('entities.show_metadata') }}</a>
    @endif

    @can('update', $entity)
        @unless ($entity->trashed())
            <a class="hover:bg-yellow-200 inline-block px-4 py-2 text-yellow-600 bg-yellow-300 rounded shadow"
               href="{{ route('entities.edit', $entity) }}">{{ __('common.edit') }}</a>
        @endunless
    @endcan

    @includeWhen(request()->user()->can('update', $entity) && $entity->approved,
        'entities.partials.state')

    @includeWhen(request()->user()->can('do-everything') && $entity->trashed(),
        'entities.partials.destroy')


@endsection

