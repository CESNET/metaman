@extends('layouts.defaultCreate')
@section('title', __('entities.add'))
@section('form_action',route('entities.store'))
@section('submit_button',__('entities.add'))
@section('form_enctype',"multipart/form-data")
@section('profile',__('entities.profile'))

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


    <x-forms.section.form-body-section
        name="entities.federation"
        label="federation"
    >

        <select @class([
                            'text-sm focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm rounded-md',
                            'border-red-500 border' => $errors->has('federation'),
                            ])
                name="federation" id="federation" required>
            <option value="">{{ __('entities.choose_federation') }}</option>
            @foreach ($federations as $federation)
                <option value="{{ $federation->id }}" {{ old('federation') == $federation->id ? 'selected' : '' }}>
                    {{ $federation->name }}
                </option>
            @endforeach
        </select>


    </x-forms.section.form-body-section>


    <x-forms.section.form-body-section
        name="common.explanation"
        label="explanation"
    >
        <x-forms.element.textarea err="explanation">
            name="explanation" id="explanation" rows="3" maxlength="255"
            placeholder="{{ __('entities.explanation_placeholder') }}"
            required
        </x-forms.element.textarea>

    </x-forms.section.form-body-section>

@endsection


