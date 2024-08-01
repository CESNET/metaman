<form x-data="{ open: false }" id="add_operators" action="{{ route('federations.operators.store', $federation) }}"
      method="post">
    @csrf
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
            @forelse ($users as $user)
                <tr x-data class="bg-white" role="button"
                    @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                    <td class="px-6 py-3 text-sm">
                        <input @click.stop class="rounded" type="checkbox" name="operators[]"
                               value="{{ $user->id }}">
                    </td>
                    <td class="whitespace-nowrap px-6 py-3 text-sm">
                        {{ $user->name }}
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <a class="hover:underline text-blue-500"
                           href="mailto:{{ $user->email }}">{{ $user->email }}</a>
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
        {{ $users->links() }}
        @if (count($users))
            <div class="px-4 py-3 bg-gray-100">
                <x-button @click.prevent="open = !open">{{ __('common.add_operators') }}</x-button>

                <x-modal>
                    <x-slot:title>{{ __('common.confirm_add_operators') }}</x-slot:title>
                    {{ __('common.confirm_add_operators_body') }}
                </x-modal>
            </div>
        @endif
    </div>
</form>
