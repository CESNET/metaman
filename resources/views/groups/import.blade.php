@extends('layouts.defaultImport')
@section('title', __('groups.import'))
@section('form_action',route('groups.import'))


{{--Add all cells in table --}}
@php
    $cells = [
        'common.name',
        'common.description',
        'common.file'
    ];
@endphp


@section('specific_fields')

    @foreach ($groups as $group)

        <x-form-table.row>

            <x-form-table.body-cell>
                <label>
                    <input @click.stop class="rounded" type="checkbox" name="groups[]"
                           value="{{ $group }}" x-bind:checked="selectAll" >
                </label>
            </x-form-table.body-cell>


            <x-form-table.body-cell>
                <label>
                    <input class="rounded-lg" type="text" name="names[{{ $group }}]">
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                <label>
                    <input class="rounded-lg" type="text" name="descriptions[{{ $group }}]">
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                <code class="text-sm text-pink-500">
                    {{ $group }}
                </code>
            </x-form-table.body-cell>

        </x-form-table.row>



    @endforeach

@endsection

@section('submit_button')
    <x-button>{{ __('groups.import') }}</x-button>
@endsection






