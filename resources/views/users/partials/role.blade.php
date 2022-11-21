<form x-data="{ open: false }" class="inline-block" action="{{ route('users.update', $user) }}" method="POST">
    @csrf
    @method('patch')

    <input type="hidden" name="action" value="role">

    <x-button @click.prevent="open = !open">
        @if ($user->admin)
            {{ __('common.deadmin') }}
        @else
            {{ __('common.make_admin') }}
        @endif
    </x-button>

    <x-modal>
        <x-slot:title>
            @if ($user->admin)
                {{ __('common.revoke_admin_rights') }}
            @else
                {{ __('common.grant_admin_rights') }}
            @endif
        </x-slot:title>
        @if ($user->admin)
            {{ __('common.revoke_admin_rights_body', ['name' => $user->name]) }}
        @else
            {{ __('common.grant_admin_rights_body', ['name' => $user->name]) }}
        @endif
    </x-modal>
</form>
