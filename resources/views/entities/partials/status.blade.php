<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.update', $entity) }}" method="POST">
    @csrf
    @method('patch')
    <input type="hidden" name="action" value="status">

    <x-button @click.prevent="open = !open" color="red">
        @if ($entity->active)
            {{ __('common.deactivate') }}
        @else
            {{ __('common.activate') }}
        @endif
    </x-button>

    <x-modal>
        <x-slot:title>
            @if ($entity->active)
                {{ __('common.deactivate_model', ['name' => $entity->{"name_$locale"}]) }}
            @else
                {{ __('common.activate_model', ['name' => $entity->{"name_$locale"}]) }}
            @endif
        </x-slot:title>
        @if ($entity->active)
            {{ __('common.deactivate_model_body', ['name' => $entity->{"name_$locale"}, 'type' => 'entity']) }}
        @else
            {{ __('common.activate_model_body', ['name' => $entity->{"name_$locale"}, 'type' => 'entity']) }}
        @endif
    </x-modal>
</form>
