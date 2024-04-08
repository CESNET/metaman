@extends('layouts.show')
@section('title', __('users.show', ['name' => $user->name]))
@section('profile', __('users.profile'))

@section('name',$user->name)

@section('model')
    <x-user-status :model="$user" />
@endsection

@section('specific_fields')

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.uniqueid_attribute') }}
        </x-slot>

        <x-slot name="dd">
            {{ $user->uniqueid }}
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.email_address') }}
        </x-slot>

        <x-slot name="dd">
            <div>
                <a class="hover:underline text-blue-500"
                   href="mailto:{{ $user->email }}">{{ $user->email }}</a>
            </div>
            @if (count($emails) > 1)
                @can('update', $user)
                    <div class="p-4 mt-4 bg-white border rounded-lg shadow">
                        <p class="mb-4 text-gray-500">
                            {{ __('users.select_different_email') }}
                        </p>
                        <form action="{{ route('users.update', $user) }}" method="post">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="action" value="email">
                            <select class="mr-4 rounded" name="email">
                                @foreach ($emails as $email)
                                    <option value="{{ $email }}"
                                            @if ($email === $user->email) selected @endif>{{ $email }}
                                    </option>
                                @endforeach
                            </select>
                            <x-button>{{ __('users.update_email') }}</x-button>
                        </form>
                    </div>
                @endcan
            @endif
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.federations') }}
        </x-slot>

        <x-slot name="dd">
            @if (count($user->federations))
                <ul class="list-decimal list-inside">
                    @foreach ($user->federations as $federation)
                        <li>
                            <a class="hover:underline text-blue-500"
                               href="{{ route('federations.show', $federation) }}">{{ $federation->name }}</a>
                        </li>
                    @endforeach
                </ul>
            @else
                {{ __('users.no_federations') }}
            @endif
        </x-slot>
    </x-element.body-section>

    <x-element.body-section>
        <x-slot name="dt">
            {{ __('common.entities') }}
        </x-slot>

        <x-slot name="dd">
            @if (count($user->entities))
                <ul class="list-decimal list-inside">
                    @foreach ($user->entities->sortBy("name_{$locale}") as $entity)
                        <li>
                            <a class="hover:underline text-blue-500"
                               href="{{ route('entities.show', $entity) }}">{{ $entity->{"name_$locale"} ?: $entity->entityid }}</a>
                        </li>
                    @endforeach
                </ul>
            @else
                {{ __('users.no_entities') }}
            @endif
        </x-slot>
    </x-element.body-section>

@endsection

@section('control_buttons')
    <x-buttons.back/>

    @includeWhen(request()->user()->can('update', $user) &&
        !request()->user()->is($user),
    'users.partials.status')

    @includeWhen(request()->user()->can('do-everything') &&
            !request()->user()->is($user),
        'users.partials.role')

@endsection



