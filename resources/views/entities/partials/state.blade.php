<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.update', $entity) }}" method="POST">
    @csrf
    @method('patch')
    <input type="hidden" name="action" value="state">

    <x-button @click.prevent="open =! open" color="red">
        @if ($entity->trashed())
            {{ __('common.restore') }}
        @else
            {{ __('common.delete') }}
        @endif
    </x-button>

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
