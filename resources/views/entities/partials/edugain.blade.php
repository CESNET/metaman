<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.update', $entity) }}" method="POST">
    @csrf
    @method('patch')

    <input type="hidden" name="action" value="edugain">

    <input type="checkbox" name="edugainbox" @click.prevent="open = !open"
        @if ($entity->edugain) checked @endif>

    <x-modal>
        <x-slot:title>
            @if ($entity->edugain)
                {{ __('common.drop_from_edugain') }}
            @else
                {{ __('common.export_to_edugain') }}
            @endif
        </x-slot:title>
        @if ($entity->edugain)
            {{ __('common.drop_from_edugain_body', ['name' => $entity->{"name_$locale"}]) }}
        @else
            {{ __('common.export_to_edugain_body', ['name' => $entity->{"name_$locale"}]) }}
        @endif
    </x-modal>
</form>
