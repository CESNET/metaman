<div>

    <div class="mb-4">
        <form>
            <label class="sr-only" for="search">{{ __('common.search') }}</label>
            <input wire:model.debounce.500ms="search" class="dark:bg-transparent w-full px-4 py-2 border rounded-lg"
                type="text" name="search" id="search" value="{{ request('search') }}"
                placeholder="{{ __('federations.searchbox') }}" autofocus>
        </form>
        <div wire:loading class="font-bold">
            {{ __('federations.loading_federations_please_wait') }}
        </div>
    </div>

    <div>
        <div class="dark:bg-transparent overflow-x-auto bg-white border rounded-lg">

            <table class="min-w-full border-b border-gray-300">

                <thead>
                    <tr>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.name') }}</th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.description') }}</th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.status') }}</th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            &nbsp;</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">

                    @forelse ($federations as $federation)
                        <tr x-data class="hover:bg-blue-50 dark:hover-bg-gray-700" role="button"
                            @click="window.location = $el.querySelectorAll('a')[0].href">
                            <td class="whitespace-nowrap px-6 py-3 text-sm">
                                {{ $federation->name }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $federation->description }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <x-pils.approved :model="$federation" />
                                <x-pils.status :model="$federation" />
                                <x-pils.state :model="$federation" />
                            </td>
                            <td class="px-6 py-3 text-sm text-right">
                                <a class="hover:underline text-blue-500"
                                    href="{{ route('federations.show', $federation->id) }}">{{ __('common.show') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr class="hover:bg-blue-50 dark:hover-bg-gray-700">
                            <td class="px-6 py-3 font-bold text-center" colspan="4">{{ __('federations.empty') }}
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>

            {{ $federations->withQueryString()->links() }}

        </div>
    </div>

</div>
