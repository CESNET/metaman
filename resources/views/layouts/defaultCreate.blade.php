@extends('layout')

@section('content')
    <form method="POST" action="@yield('form_action')" enctype="@yield('form_enctype')">
        @csrf
        @yield('form_method')
        <div class="sm:rounded-lg mb-6 overflow-hidden bg-white shadow">

            <div class="sm:px-6 px-4 py-5">
                <h3 class="text-lg font-semibold">
                  @yield('profile')
                </h3>
            </div>

            <div class="dark:border-gray-500 border-t border-gray-200">
                <dl>
                    @yield('specific_fields')
                </dl>

            </div>

            <div class="dark:bg-transparent px-4 py-4 bg-gray-100">
                <x-buttons.back/>
                <x-button> @yield('submit_button')</x-button>
            </div>


        </div>

    </form>

@endsection
