<form x-data="{ open: false }" id="add_members" action="{{ route('federations.entities.store', $federation) }}"
      method="post">
    @csrf
    <div class="overflow-x-auto bg-white border rounded-lg">
        <table class="min-w-full border-b border-gray-300">
            <thead>
            <tr>
                <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">&nbsp;
                </th>
                <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                    {{ __('common.name') }}</th>
                <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                    {{ __('common.description') }}</th>
                <th class="px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                    {{ __('common.status') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
            @forelse ($entities as $entity)
                <tr x-data class="hover:bg-blue-50" role="button"
                    @click="checkbox = $el.querySelector('input[type=checkbox]'); checkbox.checked = !checkbox.checked">
                    <td class="px-6 py-3 text-sm">
                        <input @click.stop class="rounded" type="checkbox" name="entities[]"
                               value="{{ $entity->id }}">
                    </td>
                    <td class="whitespace-nowrap px-6 py-3 text-sm">
                        {{ $entity->{"name_$locale"} }}
                        <div class="text-gray-500">
                            {{ $entity->entityid }}
                        </div>
                    </td>
                    <td class="px-6 py-3 text-sm">
                        {{ $entity->{"description_$locale"} ?: __('entities.no_description') }}
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <x-status :model="$federation" />
                    </td>
                </tr>
            @empty
                <tr class="hover:bg-blue-50">
                    <td class="px-6 py-3 font-bold text-center">
                        {{ __('federations.empty') }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        {{ $entities->links() }}
        @if (count($entities))
            <div class="px-4 py-2 bg-gray-100">
                <x-button @click.prevent="open = !open">{{ __('common.add_members') }}</x-button>

                <x-modal>
                    <x-slot:title>{{ __('common.confirm_add_members') }}</x-slot:title>
                    {{ __('common.confirm_add_members_body') }}
                </x-modal>
            </div>
        @endif
    </div>
</form>
