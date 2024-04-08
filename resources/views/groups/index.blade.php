@extends('layouts.index')
@section('title', __('common.groups'))

@section('adminOnly_action')
    <x-buttons.subhead href="{{ route('groups.import') }}">{{ __('common.import') }}</x-buttons.subhead>
    <x-buttons.subhead href="{{ route('groups.refresh') }}">{{ __('common.refresh') }}</x-buttons.subhead>
@endsection

@section('create')
    <x-buttons.subhead href="{{ route('groups.create') }}">{{ __('common.add') }}</x-buttons.subhead>
@endsection

@section('content')

    @livewire('search-groups')

@endsection
