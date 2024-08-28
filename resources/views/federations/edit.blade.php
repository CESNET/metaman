@extends('layouts.defaultCreate')
@section('title', __('federations.edit', ['name' => $federation->name]))
@section('form_action', route('federations.update', $federation))
@section('submit_button', __('federations.update'))
@section('profile',__('federations.profile'))


@section('form_method')
    @method('patch')
    <input type="hidden" name="action" value="update">
@endsection

@section('specific_fields')
    <x-forms.section.form-body-section
        name="common.name"
        label="name"
    >
        <x-forms.element.input err="name">
            type="text" name="name" id="name" maxlength="32"
            placeholder="{{ __('federations.name_placeholder') }}" value="{{ $federation->name }}"
            required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="common.description"
        label="description"
    >
        <x-forms.element.input err="description">
            type="text" name="description" id="description" maxlength="255"
            placeholder="{{ __('federations.description_placeholder') }}"
            value="{{ $federation->description }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>


    @can('do-everything')
        <x-forms.section.form-body-section
            name="common.add_sp_idp"
            label="add_sp_and_idp_section"
        >
            <label for="use_sp">

                <input type="checkbox" name="sp_and_ip_feed" id="sp_and_ip_feed" value="1"  {{ $federation->additional_filters == 1 ? 'checked' : '' }}>
                <span class="{{ $federation->additional_filters == 1 ? 'checked-label' : 'unchecked-label' }}">
                {{ __('federations.add_sp_and_idp') }}
            </span>

            </label>
        </x-forms.section.form-body-section>

    @endcan



@endsection
