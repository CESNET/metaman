@extends('layouts.defaultCreate')
@section('title', __('entities.edit', ['name' => $entity->{"name_$locale"}]))
@section('form_action', route('entities.update', $entity))
@section('submit_button', __('entities.update'))
@section('profile',__('entities.profile'))
@section('form_enctype',"multipart/form-data")

@section('form_method')
    @method('patch')
    <input type="hidden" name="action" value="update">
@endsection

@section('specific_fields')
    <x-forms.section.form-body-section
        name="entities.metadata_file"
        label="file"
    >
        <x-forms.element.input err="file">
            type="file" name="file" id="file"
        </x-forms.element.input>

    </x-forms.section.form-body-section>

    <x-forms.section.form-body-section
        name="common.metadata"
        label="metadata"
    >
        <x-forms.element.textarea err="metadata">
            name="metadata" id="metadata" rows="10"
            placeholder="{{ __('entities.metadata_placeholder') }}"
        </x-forms.element.textarea>


    </x-forms.section.form-body-section>

@endsection

