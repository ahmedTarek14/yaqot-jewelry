{{-- Small square image for index rows; a neutral placeholder when there is no image. --}}
@props(['url' => null])

<span class="yq-thumb">
    @if ($url)
        <img src="{{ $url }}" alt="" width="48" height="48" loading="lazy">
    @else
        <x-icon name="image" size="18" />
    @endif
</span>
