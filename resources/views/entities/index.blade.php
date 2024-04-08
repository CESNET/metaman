@extends('layouts.index')
@section('title', __('common.entities'))


@section('adminOnly_action')
    <x-buttons.subhead href="{{ route('entities.import') }}">{{ __('common.import') }}</x-buttons.subhead>
    <x-buttons.subhead href="{{ route('entities.refresh') }}">{{ __('common.refresh') }}</x-buttons.subhead>
@endsection

@section('create')
    <x-buttons.subhead href="{{ route('entities.create') }}">{{ __('common.add') }}</x-buttons.subhead>
@endsection

@section('content')

    @livewire('search-entities')

@endsection
