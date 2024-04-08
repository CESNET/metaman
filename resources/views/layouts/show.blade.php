@extends('layout')

@section('content')

    @yield('navigation')

    <h3 class="text-lg font-semibold">@yield('profile')</h3>
    <div class="dark:bg-gray-800 sm:rounded-lg mb-6 overflow-hidden bg-white shadow">
        <div>
            <dl>

                <div class="bg-gray-50 dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
                    <dt class="text-sm text-gray-500">{{ __('common.name') }}</dt>
                    <dd class="sm:col-span-2">
                        <span class="pr-4"> @yield('name') </span>
                        <span class="pr-4"> @yield('model') </span>

                    </dd>
                </div>
                @yield('specific_fields')

                @can('do-everything')
                    @yield('admin_section')
                @endcan

                @yield('specific_condition')

            </dl>
        </div>

        <div class="px-6 py-3 bg-gray-100">

            @yield('control_buttons')

        </div>

    </div>
@endsection
