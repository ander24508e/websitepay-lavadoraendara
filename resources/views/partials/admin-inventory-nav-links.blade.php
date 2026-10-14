@foreach ($items as $subItem)
    @can($subItem['can'])
        <a href="{{ route($subItem['route']) }}"
            class="{{ request()->routeIs(...$subItem['active']) ? 'active' : '' }}" title="{{ $subItem['title'] }}">
            <span>
                @switch($subItem['icon'])
                    @case('cube')
                        <x-heroicon-o-cube class="w-4 h-4" />
                    @break

                    @case('arrows-up-down')
                        <x-heroicon-o-arrows-up-down class="w-4 h-4" />
                    @break

                    @case('shopping-cart')
                        <x-heroicon-o-shopping-cart class="w-4 h-4" />
                    @break

                    @case('arrows-right-left')
                        <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                    @break

                    @case('arrow-uturn-left')
                        <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                    @break

                    @case('clipboard-document-check')
                        <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
                    @break

                    @case('chart-bar')
                        <x-heroicon-o-chart-bar class="w-4 h-4" />
                    @break
                @endswitch
            </span>
            <span>{{ $subItem['title'] }}</span>
        </a>
    @endcan
@endforeach
