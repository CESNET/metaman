@extends('layouts.index')
@section('title', __('common.users'))

@section('create')
    <x-buttons.subhead href="{{ route('users.create') }}">{{ __('common.add') }}</x-buttons.subhead>
@endsection


@section('content')

    @livewire('search-users')

@endsection
