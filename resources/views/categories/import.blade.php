@extends('layouts.defaultImport')
@section('title', __('categories.import'))
@section('form_action',route('categories.import'))


{{--Add all cells in table --}}
@php
    $cells = [
        'common.name',
        'common.description',
        'common.file'
    ];
@endphp

@section('specific_fields')
    @foreach ($categories as $category)

        <x-form-table.row>

            <x-form-table.body-cell>
                <label>
                    <input @click.stop class="rounded" type="checkbox" name="categories[]"
                           value="{{ $category }}"  x-bind:checked="selectAll" >
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                <label>
                    <input class="rounded" type="text" name="names[{{ $category }}]">
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                <label>
                    <input class="rounded" type="text" name="descriptions[{{ $category }}]">
                </label>
            </x-form-table.body-cell>

            <x-form-table.body-cell>
                <code class="text-sm text-pink-500">
                    {{ $category }}
                </code>
            </x-form-table.body-cell>

        </x-form-table.row>

    @endforeach
@endsection

@section('submit_button')
    <x-button>{{ __('categories.import') }}</x-button>
@endsection




