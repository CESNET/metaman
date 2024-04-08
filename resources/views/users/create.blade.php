@extends('layouts.defaultCreate')
@section('title', __('users.add'))
@section('form_action',route('users.store'))
@section('submit_button',__('users.add'))
@section('profile', __('users.profile'))


@section('specific_fields')

    <x-forms.section.form-body-section
        name="common.full_name"
        label="name"
    >
        <x-forms.element.input err="name">
            type="text" name="name" id="name" maxlength="32"
            placeholder="{{ __('users.name_placeholder') }}"
            value="{{ old('name') }}"
            required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="common.uniqueid_attribute"
        label="uniqueid"
    >

        <x-forms.element.input err="uniqueid">
            type="email" name="uniqueid" id="uniqueid" maxlength="255"
            placeholder="{{ __('users.uniqueid_placeholder') }}" value="{{ old('uniqueid') }}"
            required
        </x-forms.element.input>

    </x-forms.section.form-body-section>


    <x-forms.section.form-body-section
        name="common.email_address"
        label="email"
    >
        <x-forms.element.input err="email">
            type="email" name="email" id="email" maxlength="255"
            placeholder="{{ __('users.email_placeholder') }}" value="{{ old('email') }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

@endsection


