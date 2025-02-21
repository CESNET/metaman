@extends('layout')




@section('content')
<form method="POST" action="@yield('form_action')">
    @csrf
    <div class="dark:bg-transparent overflow-x-auto bg-white border rounded-lg">
        <table class="min-w-full border-b border-gray-300">
            <thead>
            <tr>
                <x-form-table.head-cell>
                    <label>
                        <input class="rounded" type="checkbox" onclick="toggle(this);">
                    </label>
                </x-form-table.head-cell>
                @isset($cells)
                    @foreach($cells as $cell)
                        <x-form-table.head-cell>
                            {{  __($cell)}}
                        </x-form-table.head-cell>
                    @endforeach
                @endisset

            </tr>
            </thead>


            <tbody class="divide-y divide-gray-200">
                @yield('specific_fields')
            </tbody>
        </table>
    </div>
    <div class="dark:bg-transparent px-4 py-4 bg-gray-100">

        @yield('submit_button')
    </div>
</form>
@endsection

@push('scripts')
    <script defer>
        function toggle(source) {
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i] != source)
                    checkboxes[i].checked = source.checked;
            }
        }
    </script>
@endpush
