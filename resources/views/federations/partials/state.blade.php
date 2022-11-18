<form x-data="{ open: false }" class="inline-block" action="{{ route('federations.update', $federation) }}" method="POST">
    @csrf
    @method('patch')
    <input type="hidden" name="action" value="state">
    <x-button @click.prevent="open = !open" color="red">
        @if ($federation->trashed())
            {{ __('common.restore') }}
        @else
            {{ __('common.delete') }}
        @endif
    </x-button>

    <x-modal>
        <x-slot:title>
            @if ($federation->trashed())
                {{ __('common.restore_model', ['name' => $federation->name]) }}
            @else
                {{ __('common.delete_model', ['name' => $federation->name]) }}
            @endif
        </x-slot:title>
        @if ($federation->trashed())
            {{ __('common.restore_model_body', ['name' => $federation->name, 'type' => 'federation']) }}
        @else
            {{ __('common.delete_model_body', ['name' => $federation->name, 'type' => 'federation']) }}
        @endif
    </x-modal>
</form>
