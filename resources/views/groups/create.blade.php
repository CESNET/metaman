@extends('layouts.defaultCreate')
@section('title', __('groups.add'))
@section('form_action',route('groups.store'))
@section('submit_button',__('groups.add'))
@section('profile', __('groups.profile'))


@section('specific_fields')

    <x-forms.section.form-body-section
        name="common.name"
        label="name"
    >

        <x-forms.element.input err="name">
            type="text" name="name" id="name" maxlength="32"
            placeholder="{{ __('groups.name_placeholder') }}" value="{{ old('name') }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="common.description"
        label="description"
    >
        <x-forms.element.input err="description">
            type="text" name="description" id="description" maxlength="255"
            placeholder="{{ __('categories.description_placeholder') }}"
            value="{{ old('description') }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

@endsection


