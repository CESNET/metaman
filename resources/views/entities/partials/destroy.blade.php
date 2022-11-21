<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.destroy', $entity) }}" method="POST">
    @csrf
    @method('delete')

    <x-button @click.prevent="open = !open" color="red">{{ __('common.destroy') }}</x-button>

    <x-modal>
        <x-slot:title>
            {{ __('common.destroy_model', ['name' => $entity->{"name_$locale"}]) }}
        </x-slot:title>
        {{ __('common.destroy_model_body', ['name' => $entity->{"name_$locale"}, 'type' => 'entity']) }}
    </x-modal>
</form>
