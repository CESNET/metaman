<form class="inline-block" action="{{ route('federations.update', $federation) }}" method="POST">
    @csrf
    @method('patch')
    <input type="hidden" name="action" value="approve">
    <x-button>{{ __('common.approve') }}</x-button>
</form>
