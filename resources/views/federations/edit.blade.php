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

    <x-forms.section.form-body-section
        name="federations.xml_id"
        label="xml_id"
    >
        <x-forms.element.input err="xml_id">
            type="text" name="xml_id" id="xml_id" maxlength="32"
            placeholder="{{ __('federations.name_placeholder') }}"
            value="{{ $federation->xml_id }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="federations.xml_name"
        label="xml_name"
    >
        <x-forms.element.input err="xml_name">
            type="text" name="xml_name" id="xml_name" maxlength="255"
            placeholder="{{ __('federations.description_placeholder') }}"
            value="{{ $federation->xml_name }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="federations.filters"
        label="filters"
    >
        <x-forms.element.input err="filters">
            type="text" name="filters" id="filters" maxlength="32"
            placeholder="{{ __('federations.name_placeholder') }}"
            value="{{ $federation->filters }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

@endsection
