{{--
    Dashboard "Insights" card: top products for 7 or 30 days (a plain ?range= link, no JavaScript)
    and a server-rendered SVG line chart of the last 30 days. Counters are anonymous daily totals.
    Expects $insights from Modules\Analytics\Support\Insights::forDashboard() and $chart (its 'chart' part).
--}}

<section class="card mt-4 yq-insights" aria-labelledby="insights-title">
    <div class="card-header pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h2 class="h6 mb-0 d-flex align-items-center gap-2" id="insights-title">
            <x-icon name="trending-up" size="18" />
            {{ __('analytics::analytics.insights.title') }}
        </h2>
        <nav aria-label="{{ __('analytics::analytics.insights.range_label') }}">
            <ul class="yq-range list-unstyled mb-0">
                @foreach ($insights['ranges'] as $days)
                    <li>
                        <a href="{{ route('admin.dashboard', ['range' => $days]) }}"
                            @class(['yq-range__link', 'is-active' => $insights['range'] === $days])
                            @if ($insights['range'] === $days) aria-current="true" @endif>
                            {{ __('analytics::analytics.insights.last_days', ['days' => $days]) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>

    <div class="card-body">
        <p class="text-sm text-muted mb-4">{{ __('analytics::analytics.insights.intro') }}</p>

        <div class="row g-4">
            @foreach ([
                'mostViewed' => ['title' => __('analytics::analytics.insights.most_viewed'), 'icon' => 'eye', 'unit' => 'views'],
                'mostAsked' => ['title' => __('analytics::analytics.insights.most_asked'), 'icon' => 'message-circle', 'unit' => 'clicks'],
            ] as $key => $list)
                <div class="col-12 col-lg-6">
                    <h3 class="h6 d-flex align-items-center gap-2 mb-2">
                        <x-icon :name="$list['icon']" size="18" />
                        {{ $list['title'] }}
                        <span class="text-sm text-muted fw-normal">{{ __('analytics::analytics.insights.last_days', ['days' => $insights['range']]) }}</span>
                    </h3>

                    @if ($insights[$key] === [])
                        <p class="yq-insights__empty mb-0">{{ __('analytics::analytics.insights.empty') }}</p>
                    @else
                        <ol class="yq-insights__list">
                            @foreach ($insights[$key] as $row)
                                <li>
                                    <a href="{{ route('admin.products.edit', $row['product']) }}" class="yq-insights__name">{{ $row['product']->name }}</a>
                                    <span class="yq-insights__sku" dir="ltr">{{ $row['product']->sku }}</span>
                                    <span class="yq-insights__count">
                                        {{ number_format($row['count']) }}
                                        <span class="visually-hidden">{{ __('analytics::analytics.insights.unit_'.$list['unit']) }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            @endforeach
        </div>

        <hr class="horizontal dark my-4">

        <h3 class="h6 mb-1">{{ __('analytics::analytics.insights.chart_title') }}</h3>
        <ul class="yq-chart__legend list-unstyled" aria-label="{{ __('analytics::analytics.insights.legend') }}">
            <li>
                <svg class="yq-chart__swatch" width="28" height="10" viewBox="0 0 28 10" aria-hidden="true" focusable="false"><line class="yq-chart__line yq-chart__line--views" x1="1" y1="5" x2="27" y2="5" /></svg>
                {{ __('analytics::analytics.insights.series_views') }}
            </li>
            <li>
                <svg class="yq-chart__swatch" width="28" height="10" viewBox="0 0 28 10" aria-hidden="true" focusable="false"><line class="yq-chart__line yq-chart__line--clicks" x1="1" y1="5" x2="27" y2="5" /></svg>
                {{ __('analytics::analytics.insights.series_clicks') }}
            </li>
        </ul>

        <div class="yq-chart" dir="ltr">
            <div class="yq-chart__scroll" tabindex="0" role="region" aria-label="{{ __('analytics::analytics.insights.chart_title') }}">
                <svg class="yq-chart__svg" viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" role="img"
                    aria-labelledby="insights-chart-title insights-chart-desc" data-points="{{ $chart['series']['views']['count'] }}">
                    <title id="insights-chart-title">{{ __('analytics::analytics.insights.chart_title') }}</title>
                    <desc id="insights-chart-desc">{{ __('analytics::analytics.insights.chart_desc', ['max' => number_format($chart['max'])]) }}</desc>

                    @foreach ($chart['yTicks'] as $tick)
                        <line class="yq-chart__grid" x1="{{ $tick['x1'] }}" x2="{{ $tick['x2'] }}" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" />
                        <text class="yq-chart__tick" x="{{ $tick['x1'] - 6 }}" y="{{ $tick['y'] + 4 }}" text-anchor="end">{{ number_format($tick['value']) }}</text>
                    @endforeach

                    @foreach ($chart['xTicks'] as $tick)
                        <text class="yq-chart__tick" x="{{ $tick['x'] }}" y="{{ $chart['plotBottom'] + 18 }}" text-anchor="middle">{{ \Modules\Analytics\Support\LineChart::label($tick['date']) }}</text>
                    @endforeach

                    <polyline class="yq-chart__line yq-chart__line--clicks" points="{{ $chart['series']['whatsapp_clicks']['points'] }}" />
                    <polyline class="yq-chart__line yq-chart__line--views" points="{{ $chart['series']['views']['points'] }}" />
                </svg>
            </div>
        </div>

        @if (! $insights['hasData'])
            <p class="yq-chart__none text-sm text-muted mb-0">{{ __('analytics::analytics.insights.chart_empty') }}</p>
        @else
        <details class="yq-chart__data">
            <summary>{{ __('analytics::analytics.insights.show_table') }}</summary>
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('analytics::analytics.insights.chart_title') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('analytics::analytics.insights.date') }}</th>
                            <th scope="col">{{ __('analytics::analytics.insights.series_views') }}</th>
                            <th scope="col">{{ __('analytics::analytics.insights.series_clicks') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($insights['days'] as $day)
                            <tr>
                                <th scope="row" class="text-sm fw-normal"><time datetime="{{ $day['date'] }}">{{ \Modules\Analytics\Support\LineChart::label($day['date']) }}</time></th>
                                <td>{{ number_format($day['views']) }}</td>
                                <td>{{ number_format($day['whatsapp_clicks']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
        @endif

        <div class="yq-insights__footer">
            <p class="text-sm text-muted mb-0">{{ __('analytics::analytics.insights.retention', ['months' => $insights['retentionMonths']]) }}</p>
            <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#prune-modal">
                {{ __('analytics::analytics.insights.prune.button') }}
            </button>
        </div>
    </div>
</section>

@push('modals')
    <div class="modal fade" id="prune-modal" tabindex="-1" aria-labelledby="prune-modal-title"
        aria-describedby="prune-modal-body" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="{{ route('admin.analytics.prune') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title h5" id="prune-modal-title">{{ __('analytics::analytics.insights.prune.title') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('admin.messages.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="prune-modal-body">{{ __('analytics::analytics.insights.prune.body', ['months' => $insights['retentionMonths']]) }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">{{ __('admin.modals.cancel') }}</button>
                    <button type="submit" class="btn btn-danger mb-0">{{ __('analytics::analytics.insights.prune.confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
@endpush
