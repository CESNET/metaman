@extends('layouts.defaultCreate')
@section('title', __('categories.edit', ['name' => $category->name]))
@section('form_action', route('categories.update', $category) )
@section('submit_button',__('categories.update'))
@section('profile',__('categories.profile'))

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
            placeholder="{{ __('categories.name_placeholder') }}" value="{{ $category->name }}"
            required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="common.description"
        label="name"
    >
        <x-forms.element.input err="description">
            type="text" name="description" id="description" maxlength="255"
            placeholder="{{ __('categories.description_placeholder') }}"
            value="{{ $category->description }}" required
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="common.file"
        label="name"
        err="tagfile"
    >
        <x-forms.element.input err="tagfile">
            type="text" name="tagfile" id="tagfile" maxlength="36"
            placeholder="{{ __('categories.file_placeholder') }}" value="{{ $category->tagfile }}"
            required
        </x-forms.element.input>

    </x-forms.section.form-body-section>


@endsection

