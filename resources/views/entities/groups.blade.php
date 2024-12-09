@extends('layout')
@section('title', __('entities.show', ['name' => $entity->{"name_$locale"}]))

@section('content')

    @include('entities.navigation')

    @can('do-everything')

        <div class="mb-4">
            <h3 class="text-lg font-semibold">
                {{ __('common.delete_members') }}
            </h3>
            @include('entities.groupForm.delete')
        </div>

        <div>
            <h3 class="text-lg font-semibold">
                {{ __('common.add_members') }}
            </h3>
            @include('entities.groupForm.add')
        </div>

    @endcan

@endsection
