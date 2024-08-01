<form class="inline-block" action="{{ route('federations.approve', $federation) }}" method="POST">
    @csrf
    @method('post')
    <x-button>{{ __('common.approve') }}</x-button>
</form>
