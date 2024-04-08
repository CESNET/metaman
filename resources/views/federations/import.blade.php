@extends('layouts.defaultImport')
@section('title', __('federations.import'))
@section('form_action',route('federations.import'))



{{--Add all cells in table --}}
@php
    $cells = [
        'common.name',
        'common.description',
        'federations.xml_id',
        'federations.xml_name',
        'federations.filters',
    ];
@endphp

@section('specific_fields')
    @foreach ($federations as $federation)
        <x-form-table.row>

            <x-form-table.body-cell>
                <label>
                    <input @click.stop class="rounded" type="checkbox" name="federations[]"
                           value="{{ $federation['cfgfile'] }}" x-bind:checked="selectAll">
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                <label>
                    <input class="rounded" type="text" name="names[{{ $federation['cfgfile'] }}]">
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                <label>
                    <input class="rounded" type="text" name="descriptions[{{ $federation['cfgfile'] }}]">
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                {{ $federation['xml_id'] }}
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                {{ $federation['name'] }}
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                {{ $federation['filters'] }}
            </x-form-table.body-cell>

        </x-form-table.row>





    @endforeach
@endsection

@section('submit_button')
    <x-button>{{ __('federations.import') }}</x-button>
@endsection




