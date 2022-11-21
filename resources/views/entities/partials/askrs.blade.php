<form x-data="{ open: false }" class="inline-block" action="{{ route('entities.rs', $entity) }}" method="POST">
    @csrf

    <x-button @click.prevent="open = !open">{{ __('entities.ask_rs') }}</x-button>

    <x-modal>
        <x-slot:title>
            {{ __('entities.ask_rs') }}?
        </x-slot:title>
        {{ __('entities.ask_rs') }}?
    </x-modal>
</form>
