<form x-data="{ open: false }" id="delete_operators" action="{{ route('federations.operators.destroy', $federation) }}"
      method="post">
    @csrf
    @method('DELETE')
    <div class="dark:bg-transparent overflow-x-auto bg-gray-100 border rounded-lg">
        <table class="min-w-full border-b border-gray-300">
            <thead>
            <tr>
                <th
                    class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                    &nbsp;
                </th>
                <th
                    class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                    {{ __('common.name') }}
                </th>
                <th
                    class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                    {{ __('common.email') }}
                </th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
            @forelse ($operators as $operator)
                <tr x-data class="bg-white" role="button"
                    @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                    <td class="px-6 py-3 text-sm">
                        <input @click.stop class="rounded" type="checkbox" name="operators[]"
                               value="{{ $operator->id }}">
                    </td>
                    <td class="whitespace-nowrap px-6 py-3 text-sm">
                        {{ $operator->name }}
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <a class="hover:underline text-blue-500"
                           href="mailto:{{ $operator->email }}">{{ $operator->email }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-6 py-3 font-bold text-center bg-white" colspan="3">
                        {{ __('common.no_operators') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        {{ $operators->links() }}
        @if (count($operators))
            <div class="px-4 py-3 bg-gray-100">
                <x-button color="red" @click.prevent="open = !open">{{ __('common.delete_operators') }}</x-button>

                <x-modal>
                    <x-slot:title>{{ __('common.confirm_delete_operators') }}</x-slot:title>
                    {{ __('common.confirm_delete_operators_body') }}
                    <div class="mt-4">
                        <x-button color="red" type="submit">{{ __('common.confirm') }}</x-button>
                        <x-button @click.prevent="open = false">{{ __('common.cancel') }}</x-button>
                    </div>
                </x-modal>
            </div>
        @endif
    </div>
</form>
