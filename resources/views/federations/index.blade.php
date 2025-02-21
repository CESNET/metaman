@extends('layouts.index')
@section('title', __('common.federations'))

@section('create')
    <x-buttons.subhead href="{{ route('federations.create') }}">{{ __('common.add') }}</x-buttons.subhead>
@endsection


@section('content')

    <livewire:search-federations />

@endsection
