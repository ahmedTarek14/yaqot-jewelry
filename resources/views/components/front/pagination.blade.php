{{--
    Page links for the dark theme. Keep the query string with ->withQueryString() on the paginator.
    <x-front.pagination :paginator="$products" />
--}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav {{ $attributes->class('yq-pagination') }} aria-label="{{ __('front.pagination.label') }}">
        <ul class="pagination">
            <li @class(['page-item', 'disabled' => $paginator->onFirstPage()])>
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-hidden="true"><x-icon name="chevron-left" size="18" flip-rtl /></span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        aria-label="{{ __('front.pagination.previous') }}"><x-icon name="chevron-left" size="18" flip-rtl /></a>
                @endif
            </li>

            {{-- linkCollection(): previous, the pages ("..." has no url), next. --}}
            @foreach ($paginator->onEachSide(1)->linkCollection()->slice(1, -1) as $link)
                @if ($link['url'] === null)
                    <li class="page-item disabled" aria-hidden="true"><span class="page-link">…</span></li>
                @elseif ($link['active'])
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $link['label'] }}</span></li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $link['url'] }}"
                            aria-label="{{ __('front.pagination.page', ['page' => $link['label']]) }}">{{ $link['label'] }}</a>
                    </li>
                @endif
            @endforeach

            <li @class(['page-item', 'disabled' => ! $paginator->hasMorePages()])>
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
                        aria-label="{{ __('front.pagination.next') }}"><x-icon name="chevron-right" size="18" flip-rtl /></a>
                @else
                    <span class="page-link" aria-hidden="true"><x-icon name="chevron-right" size="18" flip-rtl /></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
