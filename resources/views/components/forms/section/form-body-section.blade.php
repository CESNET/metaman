<x-element.body-section>
    <x-slot name="dt">
        <label class="text-sm" for="{{ $label ?? 'name'  }}">

            {{ __($name) ?? "Here must be a name" }}
        </label>
    </x-slot>

    <x-slot name="dd">
        {!! $errors->first("$err", '<div class="float-right text-sm font-semibold text-red-600">:message</div>') !!}
        {{ $slot }}
    </x-slot>

</x-element.body-section>


