{{-- FederationController @update 'reject' Notifications --}}
<form class="inline-block" action="{{ route('federations.reject', $federation) }}" method="POST">
    @csrf
    @method('delete')
    <input type="hidden" name="action" value="reject">
    <x-button color="red">{{ __('common.reject') }}</x-button>
</form>
