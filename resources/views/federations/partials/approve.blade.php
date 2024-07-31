<form class="inline-block" action="{{ route('federations.approve', $federation) }}" method="POST">
    @csrf
    @method('post')
    <input type="hidden" name="action" value="approve">
    <x-button>{{ __('common.approve') }}</x-button>
</form>
