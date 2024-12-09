<div class="mb-4">
    <a @class([
        'px-2',
        'py-1',
        'mr-2',
        'rounded-lg',
        'shadow',
        'bg-gray-300' => request()->routeIs('entities.show'),
        'bg-blue-100 hover:bg-blue-300' => !request()->routeIs('entities.show'),
        'cursor-default' => request()->routeIs('entities.show'),
    ]) href="{{ route('entities.show', $entity) }}">{{ __('common.details') }}</a>
    <a @class([
        'px-2',
        'py-1',
        'mr-2',
        'rounded-lg',
        'shadow',
        'bg-gray-300' => request()->routeIs('entities.operators'),
        'bg-blue-100 hover:bg-blue-300' => !request()->routeIs(
            'entities.operators'
        ),
        'cursor-default' => request()->routeIs('entities.operators'),
    ])
        href="{{ route('entities.operators.index', $entity) }}">{{ __('common.operators') }}</a>
    <a @class([
        'px-2',
        'py-1',
        'mr-2',
        'rounded-lg',
        'shadow',
        'bg-gray-300' => request()->routeIs('entities.federations'),
        'bg-blue-100 hover:bg-blue-300' => !request()->routeIs(
            'entities.federations'
        ),
        'cursor-default' => request()->routeIs('entities.federations'),
    ])
        href="{{ route('entities.federations', $entity) }}">{{ __('common.federations') }}</a>

    @can('do-everything')
        <a @class([
        'px-2',
        'py-1',
        'mr-2',
        'rounded-lg',
        'shadow',
        'bg-gray-300' => request()->routeIs('entities.category '),
        'bg-blue-100 hover:bg-blue-300' => !request()->routeIs(
            'entities.category '
        ),
        'cursor-default' => request()->routeIs('entities.category '),
    ])
           href="{{ route('entities.groups', $entity) }}">{{ __('common.groups') }}</a>

    @endcan



</div>
