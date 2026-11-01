{{--
    <x-front.breadcrumbs :items="[['label' => __('...'), 'url' => route('...')], ['label' => $name]]" />
    "Home" is added first. The last item is the current page (no url, aria-current).
--}}
@props(['items'])

<nav {{ $attributes->class('yq-breadcrumbs') }} aria-label="{{ __('front.breadcrumbs.label') }}">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('front.home') }}" class="yq-underline">{{ __('front.breadcrumbs.home') }}</a></li>
        @foreach ($items as $item)
            @if ($loop->last || empty($item['url']))
                <li class="breadcrumb-item active" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $item['url'] }}" class="yq-underline">{{ $item['label'] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
