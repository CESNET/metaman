@extends('layout')
@section('title', __('common.notifications'))

@section('content')

    <div>
        <ul class="sm:flex sm:space-x-4 sm:space-y-0 justify-center pb-4 space-y-3 font-semibold">
            <li><a class="px-3 py-1 @if (Request('show') === 'unread') bg-gray-500 text-gray-50 @else bg-gray-300 hover:bg-gray-200 @endif rounded-full shadow"
                    href="?show=unread">{{ __('notifications.unread_notifications') }}</a></li>
            <li><a class="px-3 py-1 @if (Request('show') === 'read') bg-gray-500 text-gray-50 @else bg-gray-300 hover:bg-gray-200 @endif rounded-full shadow"
                    href="?show=read">{{ __('notifications.read_notifications') }}</a></li>
            <li><a class="px-3 py-1 @if (Request('show') !== 'unread' and Request('show') !== 'read') bg-gray-500 text-gray-50 @else bg-gray-300 hover:bg-gray-200 @endif rounded-full shadow"
                    href="?show=all">{{ __('notifications.all_notifications') }}</a></li>
        </ul>
        <div class="dark:bg-transparent overflow-x-auto bg-white border rounded-lg">
            <table class="min-w-full border-b border-gray-300">
                <thead>
                    <tr>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.notification') }}</th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            {{ __('common.created') }}</th>
                        <th
                            class="dark:bg-gray-700 px-6 py-3 text-xs tracking-widest text-left uppercase bg-gray-100 border-b">
                            &nbsp;</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($notifications as $notification)
                        <tr
                            class="hover:bg-blue-100 dark:hover:bg-gray-700
                        @if ($notification->unread()) bg-blue-50 font-bold
                        @else
                            bg-white @endif">
                            <td class="px-6 py-3 text-sm">
                                @if (is_int($notification->data['user_id'] ?? null))
                                    <a class="hover:underline text-blue-500"
                                        href="{{ route('users.show', $notification->data['user_id']) }}">{{ $notification->data['body'] }}</a>
                                @elseif (is_int($notification->data['federation_id'] ?? null))
                                    <a class="hover:underline text-blue-500"
                                        href="{{ route('federations.show', $notification->data['federation_id']) }}">{{ $notification->data['body'] }}</a>
                                @elseif (is_int($notification->data['entity_id'] ?? null))
                                    <a class="hover:underline text-blue-500"
                                        href="{{ route('entities.show', $notification->data['entity_id']) }}">{{ $notification->data['body'] }}</a>
                                @elseif (is_int($notification->data['category_id'] ?? null))
                                    <a class="hover:underline text-blue-500"
                                        href="{{ route('categories.show', $notification->data['category_id']) }}">{{ $notification->data['body'] }}</a>
                                @elseif (is_int($notification->data['group_id'] ?? null))
                                    <a class="hover:underline text-blue-500"
                                        href="{{ route('groups.show', $notification->data['group_id']) }}">{{ $notification->data['body'] }}</a>
                                @else
                                    {{ $notification->data['body'] }}
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm">
                                {{ $notification->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-3 text-sm text-right">
                                <form class="inline-block" action="{{ route('notifications.update', $notification->id) }}"
                                    method="post">
                                    @csrf
                                    @method('patch')
                                    <input type="hidden" name="page" value="{{ request('page') }}">
                                    <button
                                        class="text-blue-500 hover:underline @if ($notification->unread()) font-bold @endif"
                                        type="submit">
                                        @if ($notification->unread())
                                            {{ __('notifications.mark_as_read') }}
                                        @else
                                            {{ __('notifications.mark_as_unread') }}
                                        @endif
                                    </button>
                                </form>
                                <span class="mx-1">|</span>
                                <form class="inline-block" action="{{ route('notifications.destroy', $notification->id) }}"
                                    method="post">
                                    @csrf
                                    @method('delete')
                                    <input type="hidden" name="page" value="{{ request('page') }}">
                                    <button
                                        class="text-red-500 hover:underline @if ($notification->unread()) font-bold @endif"
                                        type="submit">
                                        {{ __('notifications.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="hover:bg-blue-50 dark:hover-bg-gray-700">
                            <td class="px-6 py-3 font-bold text-center" colspan="4">{{ __('notifications.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $notifications->withQueryString()->links() }}
        </div>
    </div>

@endsection
