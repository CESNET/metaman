@extends('layouts.index')
@section('title', __('common.entities'))

@section('create')
    <x-buttons.subhead href="{{ route('entities.create') }}">{{ __('common.add') }}</x-buttons.subhead>
@endsection

@section('content')

    <livewire:search-entities />

@endsection
