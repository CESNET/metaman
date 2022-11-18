<form x-data="{ open: false }" class="inline-block" action="{{ route('federations.destroy', $federation) }}" method="POST">
    @csrf
    @method('delete')
    <x-button @click.prevent="open = !open" color="red">{{ __('common.destroy') }}</x-button>

    <x-modal>
        <x-slot:title>
            {{ __('common.destroy_model', ['name' => $federation->name]) }}
        </x-slot:title>
        {{ __('common.destroy_model_body', ['name' => $federation->name, 'type' => 'federation']) }}
    </x-modal>
</form>
