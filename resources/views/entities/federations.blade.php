@extends('layout')
@section('title', __('entities.show', ['name' => $entity->{"name_$locale"}]))

@section('content')

    @include('entities.navigation')

    @can('update', $entity)

        @if (count($federations))
            <div class="mb-4">
                <h3 class="text-lg font-semibold">{{ __('entities.leave_federations') }}</h3>
                <form x-data="{ open: false }" id="leave_federations" action="{{ route('entities.leave', $entity) }}" method="post">
                    @csrf
                    <input type="hidden" name="action" value="leave_federations">
                    <div class="overflow-x-auto bg-gray-100 border rounded-lg">
                        <table class="min-w-full border-b border-gray-300">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">&nbsp;</th>
                                    <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">
                                        {{ __('common.name') }}</th>
                                    <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">
                                        {{ __('common.description') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-300">
                                @foreach ($federations as $federation)
                                    <tr x-data class="bg-white" role="button"
                                        @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                                        <td class="px-6 py-3 text-sm">
                                            <input @click.stop class="rounded" type="checkbox" name="federations[]"
                                                value="{{ $federation->id }}">
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            {{ $federation->name }}
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            {{ $federation->description }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if (count($federations))
                            <div class="px-4 py-3 bg-gray-100">
                                <x-button color="red" @click.prevent="open = !open">{{ __('entities.leave_federations') }}
                                </x-button>

                                <x-modal>
                                    <x-slot:title>{{ __('common.confirm_leave_federations') }}</x-slot:title>
                                    {{ __('common.confirm_leave_federations_body') }}
                                </x-modal>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        @else
            <div class="text-red-50 px-4 py-2 mb-4 font-bold bg-red-700 rounded shadow">
                {{ __('entities.not_part_of_a_federation') }}
            </div>
        @endif

        @if (count($joinable))
            <form action="{{ route('entities.join', $entity) }}" method="post">
                @csrf
                <input type="hidden" name="action" value="join_federation">
                <div class="sm:rounded-lg overflow-hidden bg-white shadow">
                    <div class="sm:px-6 px-4 py-5">
                        <h3 class="text-lg font-semibold">{{ __('entities.join_federation') }}</h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <dl>
                            <div class="bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                                <dt class="text-sm text-gray-500">
                                    {{ __('common.federation') }}
                                </dt>
                                <dd class="sm:col-span-2">
                                    {!! $errors->first('federation', '<div class="float-right text-sm font-semibold text-red-600">:message</div>') !!}
                                    <select
                                        class="w-full text-sm rounded @error('federation') border-red-500 border @else @if (old('federation') !== null) border-green-500 @endif @enderror"
                                        name="federation" id="federation" required>
                                        <option value="">{{ __('entities.choose_federation_placeholder') }}</option>
                                        @foreach ($joinable as $federation)
                                            <option value="{{ $federation->id }}"
                                                {{ old('federation') == $federation->id ? 'selected' : '' }}>
                                                {{ $federation->name }}</option>
                                        @endforeach
                                    </select>
                                </dd>
                            </div>
                            <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5 bg-white">
                                <dt class="text-sm text-gray-500">
                                    {{ __('common.explanation') }}
                                </dt>
                                <dd class="sm:col-span-2">
                                    {!! $errors->first('explanation', '<div class="float-right text-sm font-semibold text-red-600">:message</div>') !!}
                                    <textarea
                                        class="text-sm @error('explanation') border-red-500 border @else @if (old('explanation') !== null) border-green-500 @endif @enderror focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm border-gray-300 dark:border-gray-700 rounded-md"
                                        name="explanation" id="explanation" rows="3" maxlength="255"
                                        placeholder="{{ __('entities.join_explanation_placeholder') }}" required>{{ old('explanation') }}</textarea>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div class="px-4 py-4 bg-gray-100">
                        <x-button>{{ __('entities.join_federation') }}</x-button>
                    </div>
                </div>
            </form>
        @else
            <p>
                No federations to join.
            </p>
        @endif
    @else
        @if (count($federations))
            <div class="mb-4">
                <h3 class="text-lg font-semibold">{{ __('entities.leave_federations') }}</h3>
                <form action="{{ route('entities.leave', $entity) }}" method="post">
                    @csrf
                    <input type="hidden" name="action" value="leave_federations">
                    <div class="overflow-x-auto bg-gray-100 border rounded-lg">
                        <table class="min-w-full border-b border-gray-300">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">
                                        {{ __('common.name') }}</th>
                                    <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100">
                                        {{ __('common.description') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-300">
                                @forelse ($federations as $federation)
                                    <tr class="bg-white">
                                        <td class="px-6 py-3 text-sm">
                                            {{ $federation->name }}
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            {{ $federation->description }}
                                        </td>
                                    </tr>
                                @empty
                                    nuttin
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        @else
            nuttin
        @endif

    @endcan

@endsection
