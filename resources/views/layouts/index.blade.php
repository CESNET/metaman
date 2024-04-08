@extends('layout')

@section('subheader')

    @can('do-everything')
        @yield('adminOnly_action')
    @endcan

    @yield('create')

@endsection

