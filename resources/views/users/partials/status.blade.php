<form x-data="{ open: false }" class="inline-block" action="{{ route('users.update', $user) }}" method="POST">
    @csrf
    @method('patch')

    <input type="hidden" name="action" value="status">

    <x-button @click.prevent="open = !open">
        @if ($user->active)
            {{ __('common.deactivate') }}
        @else
            {{ __('common.activate') }}
        @endif
    </x-button>

    <x-modal>
        <x-slot:title>
            @if ($user->active)
                {{ __('common.deactivate_model', ['name' => $user->name]) }}
            @else
                {{ __('common.activate_model', ['name' => $user->name]) }}
            @endif
        </x-slot:title>
        @if ($user->active)
            {{ __('common.deactivate_model_body', ['name' => $user->name, 'type' => 'user']) }}
        @else
            {{ __('common.activate_model_body', ['name' => $user->name, 'type' => 'user']) }}
        @endif
    </x-modal>
</form>
