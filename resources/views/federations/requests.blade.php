@extends('layout')
@section('title', __('common.requests'))

@section('content')

    @include('federations.navigation')

    @forelse ($joins as $join)
        @if($join->entity)
            <div class="sm:rounded-lg mb-4 overflow-hidden bg-white shadow">
                <div>
                    <dl>
                        <div class="bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                            <dt class="text-sm text-gray-500">{{ __('common.entity') }}</dt>
                            <dd><a class="hover:underline text-blue-500"
                                   href="{{ route('entities.show', $join->entity) }}">{{ $join->entity->{"name_$locale"} ?? $join->entity->entityid }}</a>
                            </dd>
                        </div>
                        <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                            <dt class="text-sm text-gray-500">{{ __('common.requester') }}</dt>
                            <dd><a class="hover:underline text-blue-500"
                                   href="{{ route('users.show', $join->requested_by) }}">{{ $join->requester->name }}</a>
                            </dd>
                        </div>
                        <div class="bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                            <dt class="text-sm text-gray-500">{{ __('common.explanation') }}</dt>
                            <dd>{{ $join->explanation }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="px-6 py-3 bg-gray-100">
                    <x-forms.membership-reject :membership="$join" />
                    <x-forms.membership-accept :membership="$join" />
                </div>
            </div>
        @endif

    @empty
        {{ __('common.no_requests') }}
    @endforelse

@endsection
