<form class="inline-block" action="{{ route('memberships.update', $membership) }}" method="post">
    @csrf
    @method('patch')
    <x-button color="green">{{ __('common.accept') }}</x-button>
</form>
