<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.state', $entity) }}" method="POST">
    @csrf
    @method('patch')
    @if ($entity->trashed())
        <x-button @click.prevent="open =! open" color="green">{{ __('common.restore') }}</x-button>
    @else
        <x-button @click.prevent="open =! open" color="red">{{ __('common.delete') }}</x-button>
    @endif

    <x-modal>
        <x-slot:title>
            @if ($entity->trashed())
                {{ __('common.restore_model', ['name' => $entity->{"name_$locale"}]) }}
            @else
                {{ __('common.delete_model', ['name' => $entity->{"name_$locale"}]) }}
            @endif
        </x-slot:title>
        @if ($entity->trashed())
            {{ __('common.restore_model_body', ['name' => $entity->{"name_$locale"}, 'type' => 'entity']) }}
        @else
            {{ __('common.delete_model_body', ['name' => $entity->{"name_$locale"}, 'type' => 'entity']) }}
        @endif
    </x-modal>
</form>
