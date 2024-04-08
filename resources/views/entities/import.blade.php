@extends('layouts.defaultImport')
@section('title', __('entities.import'))
@section('form_action',route('entities.import'))



@php

    $cells = [
        'common.name',
        'common.entityid',
        'common.federations',
    ];
@endphp

@section('specific_fields')

    @forelse ($entities as $entity)

        <x-form-table.row>


            <x-form-table.body-cell>
                <label>
                    <input class="rounded" type="checkbox" name="entities[]" value="{{ $entity['file'] }}"
                           x-bind:checked="selectAll">
                </label>
            </x-form-table.body-cell>


            <x-form-table.body-cell>
                {{ $entity["name_$locale"] }}
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                {{ $entity['entityid'] }}
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                @if (isset($entity['federations']))
                    @foreach ($entity['federations'] as $federation)
                        {{ $federation }}@if (!$loop->last)
                            ,
                        @endif
                    @endforeach
                @else
                @endif
            </x-form-table.body-cell>



        </x-form-table.row>


    @empty

    @endforelse

@endsection

@section('submit_button')
    <x-button>{{ __('entities.import') }}</x-button>
@endsection




