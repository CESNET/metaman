<header>

    @include('layouts.scriptAttention')

    <div class="md:h-16 dark:bg-gray-800 bg-gray-200">
        <div class="md:pl-4 h-full max-w-screen-xl mx-auto">
            <nav x-data="{ open: false }" class="md:flex-row md:items-center flex flex-col justify-between h-full">

                <div class="md:flex-row md:items-center flex flex-col">
                    <p class="flex items-center h-16">
                        <a class="inline-block px-4 py-2 text-lg font-bold" href="/">{{ config('app.name') }}</a>
                    </p>
                    <ul class="md:flex md:flex-row flex-col hidden" id="navigation">
                        <li><a class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 block px-4 py-2"
                                href="{{ route('federations.index') }}">{{ __('common.federations') }}</a></li>
                        <li><a class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 block px-4 py-2"
                                href="{{ route('entities.index') }}">{{ __('common.entities') }}</a></li>
                        @can('do-everything')
                            <li><a class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 block px-4 py-2"
                                    href="{{ route('categories.index') }}">{{ __('common.categories') }}</a></li>
                            <li><a class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 block px-4 py-2"
                                    href="{{ route('groups.index') }}">{{ __('common.groups') }}</a></li>
                        @endcan
                        <li><a class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 block px-4 py-2"
                                href="{{ route('users.show', Auth::id()) }}">{{ __('common.my_profile') }}</a>
                        </li>
                        @can('viewAny', App\Models\User::class)
                            <li><a class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 block px-4 py-2"
                                    href="{{ route('users.index') }}">{{ __('common.users') }}</a></li>
                        @endcan
                    </ul>
                </div>

                <div class="md:flex-row flex flex-col">
                    <ul class="md:pr-4 md:flex md:flex-row md:text-sm md:items-center flex-col hidden" id="profile">
                        {{-- <li>
                            <a class="dark:hover:text-white hover:text-gray-700 whitespace-nowrap" href="{{ route('notifications') }}">
                                <div class="md:hidden hover:bg-gray-300 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:text-gray-400 md:hover:bg-transparent px-4 py-2 rounded-md">Notifications ({{ $notifications}})</div>
                                <div class="md:flex items-center justify-center hidden">
                                    <span class="flex items-center justify-center w-5 h-5 rounded-full">{{ $notifications }}</span>
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </div>
                            </a>
                        </li> --}}
                        <li>
                            <x-language-switcher />

                            <a class="md:inline-block md:rounded hover:bg-gray-400 hover:text-gray-900 whitespace-nowrap block px-4 py-2"
                                @env(['local', 'testing']) href="/fakelogout"
                            @else
                                href="{{ route('logout') }}" @endenv>{{ __('common.logout') }}</a>
                        </li>
                    </ul>
                </div>

                <div class="md:hidden top-3 right-4 absolute block">
                    <button
                        @click="open = !open; document.querySelector('#navigation').classList.toggle('hidden'); document.querySelector('#profile').classList.toggle('hidden');"
                        class="hover:bg-gray-300 dark:hover:bg-gray-700 p-2 rounded-lg" id="menu">
                        <svg :class="open && 'hidden'" class="block w-6 h-6" id="open-menu"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-cloak :class="open || 'hidden'" class="w-6 h-6" id="close-menu"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

            </nav>
        </div>
    </div>

    <div class="dark:bg-gray-700 h-10 bg-gray-100">
        <div class="md:px-8 h-full max-w-screen-xl px-4 mx-auto">
            <div class="flex items-center justify-between h-full text-lg font-semibold">
                <div>
                    @yield('title')
                </div>
                <div class="flex items-center">
                    @yield('subheader')
                </div>
            </div>
        </div>
    </div>

    <hr class="hidden">
</header>
