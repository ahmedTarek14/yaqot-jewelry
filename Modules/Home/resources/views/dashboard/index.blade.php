@extends('layouts.admin.master')

@section('title', __('admin.nav.dashboard'))

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h4 mb-2">{{ __('home::home.dashboard.welcome', ['name' => auth('admin')->user()->name]) }}</h2>
                    <p class="mb-0">{{ __('home::home.dashboard.intro') }}</p>
                </div>
            </div>
        </div>
    </div>

    <h2 class="visually-hidden">{{ __('home::home.dashboard.stats.heading') }}</h2>
    <div class="row g-3 g-lg-4 mt-1">
        <div class="col-12 col-sm-6 col-xl-3">
            <x-admin.stat-card :label="__('home::home.dashboard.stats.products')" :value="$stats['products']" icon="gem"
                :url="route('admin.products.index')" :link-label="__('home::home.dashboard.stats.products_link')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-admin.stat-card :label="__('home::home.dashboard.stats.published')" :value="$stats['published']" icon="globe"
                :url="route('admin.products.index', ['status' => 'published'])"
                :link-label="__('home::home.dashboard.stats.published_link')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-admin.stat-card :label="__('home::home.dashboard.stats.drafts')" :value="$stats['drafts']" icon="edit"
                :url="route('admin.products.index', ['status' => 'draft'])"
                :link-label="__('home::home.dashboard.stats.drafts_link')" />
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <x-admin.stat-card :label="__('home::home.dashboard.stats.active_categories')" :value="$stats['active_categories']" icon="tag"
                :url="route('admin.categories.index', ['status' => 'active'])"
                :link-label="__('home::home.dashboard.stats.active_categories_link')" />
        </div>
    </div>

    @include('analytics::dashboard.insights', ['insights' => $insights, 'chart' => $insights['chart']])

    <div class="card mt-4">
        <x-admin.card-header :title="__('home::home.dashboard.recent.title')">
            @if ($recentProducts->isNotEmpty())
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-dark btn-sm mb-0">
                    {{ __('home::home.dashboard.recent.all') }}
                </a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                    <x-icon name="plus" size="16" /> {{ __('home::home.dashboard.add_product') }}
                </a>
            @endif
        </x-admin.card-header>

        @if ($recentProducts->isEmpty())
            <x-admin.empty-state :message="__('home::home.dashboard.recent.empty')"
                :create-url="route('admin.products.create')" :create-label="__('home::home.dashboard.add_product')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('home::home.dashboard.recent.caption') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="yq-table__thumb"><span class="visually-hidden">{{ __('admin.columns.image') }}</span></th>
                            <th scope="col">{{ __('admin.columns.name') }}</th>
                            <th scope="col">{{ __('admin.status.label') }}</th>
                            <th scope="col" class="d-none d-md-table-cell">{{ __('admin.columns.updated_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentProducts as $product)
                            <tr>
                                <td class="yq-table__thumb"><x-admin.thumbnail :url="$product->primaryImage?->publicImageUrl()" /></td>
                                <td>
                                    <x-admin.name-cell :model="$product" :url="route('admin.products.edit', $product)" />
                                </td>
                                <td>
                                    <x-admin.badge :on="$product->is_published"
                                        :label="$product->is_published ? __('admin.status.published') : __('admin.status.draft')" />
                                </td>
                                <td class="d-none d-md-table-cell text-sm">
                                    <time datetime="{{ $product->updated_at->toIso8601String() }}">{{ $product->updated_at->translatedFormat('j M Y, H:i') }}</time>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Only after an upload that brought migrations (the host has no shell for `artisan migrate`). --}}
    @if ($pendingMigrations > 0)
        <div class="card mt-4 border border-warning">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1">
                    <h2 class="h6 mb-1">{{ __('home::home.system.title') }}</h2>
                    <p class="text-sm mb-0">{{ trans_choice('home::home.system.pending', $pendingMigrations, ['count' => $pendingMigrations]) }}</p>
                </div>
                <form method="POST" action="{{ route('admin.system.migrate') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm mb-0">{{ __('home::home.system.apply') }}</button>
                </form>
            </div>
        </div>
    @endif

    <div class="card mt-4">
        <div class="card-body">
            <h2 class="h6 mb-1">{{ __('admin.limits.dashboard_title') }}</h2>
            <p class="text-sm text-muted mb-0">
                {{ __('admin.limits.dashboard_text', ['post' => $uploadLimits['postLabel'], 'file' => $uploadLimits['fileLabel']]) }}
            </p>
        </div>
    </div>
@endsection
