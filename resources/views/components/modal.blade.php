@props(['action' => __('common.confirm')])

<div x-cloak x-show="open" class="fixed inset-0 z-10 overflow-y-auto">
    <div class="overlay sm:block sm:p-0 flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="sm:inline-block sm:align-middle sm:h-screen hidden" aria-hidden="true">&#8203;</span>
        <div class="modal-panel sm:my-8 sm:align-middle sm:max-w-lg sm:w-full inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl"
            role="dialog" aria-modal="true" aria-labelledby="modal-headline">
            <div class="sm:p-6 sm:pb-4 px-4 pt-5 pb-4 bg-white">
                <div class="sm:flex sm:items-start">
                    <div
                        class="sm:mx-0 sm:h-10 sm:w-10 flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full">
                        <!-- Heroicon name: exclamation -->
                        <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="sm:mt-0 sm:ml-4 sm:text-left mt-3 text-center">
                        <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-headline">
                            {{ $title }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                {{ $slot }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse px-4 py-3">
                <button type="submit"
                    class="hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm">
                    {{ $action }}
                </button>
                <button @click="open = false" type="button"
                    class="hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
