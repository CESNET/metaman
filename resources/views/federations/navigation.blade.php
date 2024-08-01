<div class="mb-4">
    <a @class([
        'px-2',
        'py-1',
        'mr-2',
        'rounded-lg',
        'shadow',
        'bg-gray-300' => request()->routeIs('federations.show'),
        'bg-blue-100 hover:bg-blue-300' => !request()->routeIs('federations.show'),
        'cursor-default' => request()->routeIs('federations.show'),
    ])
        href="{{ route('federations.show', $federation) }}">{{ __('common.details') }}</a>
    <a @class([
        'px-2',
        'py-1',
        'mr-2',
        'rounded-lg',
        'shadow',
        'bg-gray-300' => request()->routeIs('federations.operators'),
        'bg-blue-100 hover:bg-blue-300' => !request()->routeIs(
            'federations.operators'
        ),
        'cursor-default' => request()->routeIs('federations.operators'),
    ])
       href="{{ route('federations.operators.index', $federation) }}">{{ __('common.operators') }}
    </a>

    <a @class([
        'px-2',
        'py-1',
        'mr-2',
        'rounded-lg',
        'shadow',
        'bg-gray-300' => request()->routeIs('federations.entities'),
        'bg-blue-100 hover:bg-blue-300' => !request()->routeIs(
            'federations.entities'
        ),
        'cursor-default' => request()->routeIs('federations.entities'),
    ])
        href="{{ route('federations.entities.index', $federation) }}">{{ __('common.entities') }}</a>
    @can('update', $federation)
        <a @class([
            'px-2',
            'py-1',
            'mr-2',
            'rounded-lg',
            'shadow',
            'bg-gray-300' => request()->routeIs('federations.requests'),
            'bg-blue-100 hover:bg-blue-300' => !request()->routeIs(
                'federations.requests'
            ),
            'cursor-default' => request()->routeIs('federations.requests'),
        ])
            href="{{ route('federations.requests', $federation) }}">{{ __('common.requests') }}</a>
    @endcan
</div>
