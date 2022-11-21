<form class="inline-block" action="{{ route('memberships.destroy', $membership) }}" method="post">
    @csrf
    @method('delete')
    <x-button color="red">{{ __('common.reject') }}</x-button>
</form>
