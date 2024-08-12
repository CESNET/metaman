@extends('layouts.index')
@section('title', __('common.categories'))


@section('adminOnly_action')
    <x-buttons.subhead href="{{ route('categories.import') }}">{{ __('common.import') }}</x-buttons.subhead>
    <x-buttons.subhead href="{{ route('categories.refresh') }}">{{ __('common.refresh') }}</x-buttons.subhead>
@endsection


@section('content')

    @livewire('search-categories')

@endsection
