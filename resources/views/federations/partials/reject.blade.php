{{-- FederationController @update 'reject' Notifications --}}
<form class="inline-block" action="{{ route('federations.update', $federation) }}" method="POST">
    @csrf
    @method('patch')
    <input type="hidden" name="action" value="reject">
    <x-button color="red">{{ __('common.reject') }}</x-button>
</form>
