@extends('layouts.index')
@section('title', __('common.federations'))

@section('adminOnly_action')
    <x-buttons.subhead href="{{ route('federations.import') }}">{{ __('common.import') }}</x-buttons.subhead>
    <x-buttons.subhead href="{{ route('federations.refresh') }}">{{ __('common.refresh') }}</x-buttons.subhead>
@endsection

@section('create')
    <x-buttons.subhead href="{{ route('federations.create') }}">{{ __('common.add') }}</x-buttons.subhead>
@endsection


@section('content')

    @livewire('search-federations')

@endsection
