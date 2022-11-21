<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.update', $entity) }}" method="POST">
    @csrf
    @method('patch')

    <input type="hidden" name="action" value="rs">

    <input type="checkbox" name="rsbox" @click.prevent="open = !open" @if ($entity->rs) checked @endif>

    <x-modal>
        <x-slot:title>
            @if ($entity->rs)
                {{ __('common.delete_rs') }}
            @else
                {{ __('common.add_rs') }}
            @endif
        </x-slot:title>
        @if ($entity->rs)
            {{ __('common.delete_rs_body', ['name' => $entity->{"name_$locale"}]) }}
        @else
            {{ __('common.add_rs_body', ['name' => $entity->{"name_$locale"}]) }}
        @endif
    </x-modal>
</form>
