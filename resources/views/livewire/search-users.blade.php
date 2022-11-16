<div>

    <div class="mb-4">
        <form>
            <label class="sr-only" for="search">{{ __('common.search') }}</label>
            <input wire:model.debounce.500ms="search" class="dark:bg-transparent w-full px-4 py-2 border rounded-lg"
                type="text" name="search" id="search" placeholder="{{ __('users.searchbox') }}" autofocus>
        </form>
        <div wire:loading class="font-bold">
            {{ __('users.loading_users_please_wait') }}
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
                            {{ __('common.email') }}</th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.status') }}</th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            &nbsp;</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">

                    @forelse ($users as $user)
                        <tr x-data class="hover:bg-blue-50 dark:hover:bg-gray-700" role="button"
                            @click="window.location = $el.querySelectorAll('a')[1].href">
                            <td class="whitespace-nowrap px-6 py-3 text-sm">
                                <div class="font-bold">
                                    {{ $user->name }}
                                </div>
                                <div class="text-gray-400">
                                    {{ $user->uniqueid }}
                                </div>
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <a class="hover:underline text-blue-600"
                                    href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <x-pils.status :model="$user" />
                            </td>
                            <td class="px-6 py-3 text-sm text-right">
                                <a class="hover:underline text-blue-600"
                                    href="{{ route('users.show', $user->id) }}">{{ __('common.show') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr class="hover:bg-blue-50 cursor-pointer">
                            <td class="whitespace-nowrap px-6 py-3 font-semibold text-center" colspan="4">
                                {{ __('users.none_found') }}
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>

            {{ $users->links() }}

        </div>
    </div>

</div>
