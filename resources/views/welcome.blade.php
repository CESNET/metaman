<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>

<body class="sm:p-16 bg-gray-50 dark:bg-gray-900 dark:text-gray-400 p-4 antialiased text-gray-700">

    <div class="max-w-screen-md mx-auto">

        @include('layouts.scriptAttention')


        <div
            class="sm:p-8 dark:bg-gray-800 dark:border-gray-500 bg-gradient-to-br from-gray-100 dark:from-gray-800 to-gray-100 dark:to-gray-800 via-gray-200 dark:via-gray-900 p-4 bg-gray-100 border rounded shadow-md">

            <header>
                <h1 class="sm:text-5xl pb-4 text-4xl font-bold tracking-wider">{{ config('app.name') }}</h1>
                <p class="pb-4 font-bold text-right text-blue-500">[
                    <x-language-switcher>
                        {{ __('welcome.switch_language') }}
                    </x-language-switcher>

                    ]</p>
                <p class="sm:text-lg leading-relaxed">{!! __('welcome.introduction') !!}</p>
                <div
                    class="bg-gradient-to-r from-gray-100 dark:from-gray-800 to-gray-100 dark:to-gray-800 via-gray-800 dark:via-gray-100 w-full h-px my-8">
                </div>
                <hr class="hidden">
            </header>

            <main class="sm:pb-8 pb-4">
                <p class="pb-4">{{ __('welcome.requested_attributes') }}</p>

                <ul class="list-disc list-inside">
                    <li>
                        <a class="hover:underline text-blue-500"
                            href="https://www.eduid.cz/cs/tech/attributes/cn">cn</a> ({{ __('welcome.cn') }})
                    </li>
                    <li>
                        <a class="hover:underline text-blue-500"
                            href="https://www.eduid.cz/cs/tech/attributes/edupersonuniqueid">uniqueId</a>
                        ({{ __('welcome.uniqueid') }})
                    </li>
                    <li>
                        <a class="hover:underline text-blue-500"
                            href="https://www.eduid.cz/cs/tech/attributes/mail">mail</a> ({{ __('welcome.mail') }})
                    </li>
                </ul>

                <p class="sm:pt-10 pt-6 text-center">
                    <a class="md:inline-block hover:bg-blue-600 text-blue-50 hover:shadow-lg block px-6 py-3 font-bold bg-blue-500 rounded shadow"
                        href="{{ route('login') }}">{{ __('common.login') }}</a>
                </p>
            </main>

            <footer>
                <div
                    class="bg-gradient-to-r from-gray-100 dark:from-gray-800 to-gray-100 dark:to-gray-800 via-gray-300 dark:via-gray-600 w-full h-px mt-4 mb-3">
                </div>
                <hr class="hidden">
                <p class="text-center opacity-75">
                    <small class="text-sm">
                        <a class="hover:underline text-blue-500"
                            href="{{ __('welcome.pii-link') }}">{{ __('welcome.pii-text') }}</a><br>
                        &copy; 2019&dash;{{ date('Y') }} <a class="hover:underline text-blue-500"
                            href="https://www.cesnet.cz">CESNET</a>,
                        <a class="hover:underline text-blue-500" href="mailto:info@eduid.cz">info@eduid.cz</a>.
                    </small>
                </p>
            </footer>

        </div>

        @if (App::environment(['local', 'testing']))
            <hr class="hidden">
            <div class="mt-4 text-blue-900 bg-blue-100 rounded shadow">
                <h2 class="sm:text-xl p-4 font-semibold bg-blue-400 rounded-t">Login without authentication</h2>
                <form action="/fakelogin" method="POST">
                    @csrf
                    <div class="sm:flex-row sm:justify-between flex flex-col p-4 space-y-5">
                        <div class="flex items-center space-x-5">
                            <label class="font-semibold" for="user_id">User ID:</label>
                            <input class="w-20" type="number" name="id" id="user_id" value="1"
                                min="1" required>
                        </div>
                        <button class="hover:bg-blue-400 hover:shadow-lg px-6 py-3 bg-blue-300 rounded shadow"
                            type="submit">Fake Login</button>
                    </div>
                </form>
            </div>
        @endif

    </div>

</body>

</html>
