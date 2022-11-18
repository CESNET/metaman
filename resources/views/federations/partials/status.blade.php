<form x-data="{ open: false }" class="inline-block" action="{{ route('federations.update', $federation) }}" method="POST">
    @csrf
    @method('patch')
    <input type="hidden" name="action" value="status">

    <x-button @click.prevent="open = !open" color="red">
        @if ($federation->active)
            {{ __('common.deactivate') }}
        @else
            {{ __('common.activate') }}
        @endif
    </x-button>

    <x-modal>
        <x-slot:title>
            @if ($federation->active)
                {{ __('common.deactivate_model', ['name' => $federation->name]) }}
            @else
                {{ __('common.activate_model', ['name' => $federation->name]) }}
            @endif
        </x-slot:title>
        @if ($federation->active)
            {{ __('common.deactivate_model_body', ['name' => $federation->name, 'type' => 'federation']) }}
        @else
            {{ __('common.activate_model_body', ['name' => $federation->name, 'type' => 'federation']) }}
        @endif
    </x-modal>
</form>
