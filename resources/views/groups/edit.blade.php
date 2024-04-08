@extends('layouts.defaultCreate')
@section('title', __('groups.edit', ['name' => $group->name]))
@section('form_action', route('groups.update', $group) )
@section('submit_button',__('groups.update'))
@section('profile',__('groups.profile'))

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
            placeholder="{{ __('groups.name_placeholder') }}" value="{{ $group->name }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="common.description"
        label="name"
    >
        <x-forms.element.input err="description">
            type="text" name="description" id="description" maxlength="255"
            placeholder="{{ __('groups.description_placeholder') }}"
            value="{{ $group->description }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>


    <x-forms.section.form-body-section
        name="common.file"
        label="name"
        err="tagfile"
    >
        <x-forms.element.input err="tagfile">
            type="text" name="tagfile" id="tagfile" maxlength="36"
            placeholder="{{ __('groups.file_placeholder') }}" value="{{ $group->tagfile }}"
            required
        </x-forms.element.input>

    </x-forms.section.form-body-section>


@endsection

