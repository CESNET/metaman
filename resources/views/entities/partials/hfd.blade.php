<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.hfd', $entity) }}" method="POST">
    @csrf
    @method('patch')
    <input type="checkbox" name="hfdbox" @click.prevent="open = !open" @if ($entity->hfd) checked @endif>

    <x-modal>
        <x-slot:title>
            @if ($entity->hfd)
                {{ __('entities.confirm_drop_hfd') }}
            @else
                {{ __('entities.confirm_add_hfd') }}
            @endif
        </x-slot:title>
        @if ($entity->hfd)
            {{ __('entities.confirm_drop_hfd_body') }}
        @else
            {{ __('entities.confirm_add_hfd_body') }}
        @endif
    </x-modal>
</form>
