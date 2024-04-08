<div class="odd:bg-gray-50 even:bg-white dark:bg-gray-900 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 px-4 py-5">
    <dt class="text-sm text-gray-500">
        {{ $dt }}
    </dt>
    <dd class="sm:col-span-2">
        {{ $dd ?? $slot }}
    </dd>
</div>
