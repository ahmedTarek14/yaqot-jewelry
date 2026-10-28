@extends('layouts.admin.master')

@section('title', __('collection::collection.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('collection::collection.title') }}</li>
@endpush

@section('content')
    <div class="card">
        <x-admin.card-header :title="__('collection::collection.title')" :count="$collections->total()">
            <a href="{{ route('admin.collections.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ __('collection::collection.create') }}
            </a>
        </x-admin.card-header>

        <x-admin.listing-toolbar :listing="$listing" :placeholder="__('collection::collection.search_placeholder')">
            <x-admin.filter-select name="status" :label="__('admin.status.label')" :value="$listing->filter('status')"
                :options="['active' => __('admin.status.active'), 'inactive' => __('admin.status.inactive')]" />
            <x-admin.filter-select name="homepage" :label="__('collection::collection.homepage.label')" :value="$listing->filter('homepage')"
                :options="['yes' => __('collection::collection.homepage.on'), 'no' => __('collection::collection.homepage.off')]" />
        </x-admin.listing-toolbar>

        @if ($collections->isEmpty())
            <x-admin.empty-state :listing="$listing" :message="__('collection::collection.empty')"
                :create-url="route('admin.collections.create')" :create-label="__('collection::collection.create')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('collection::collection.table_caption') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="yq-table__thumb"><span class="visually-hidden">{{ __('admin.columns.image') }}</span></th>
                            <x-admin.sort-header :listing="$listing" column="name" :label="__('admin.columns.name')" />
                            <x-admin.sort-header :listing="$listing" column="products_count" :label="__('admin.columns.products')"
                                class="d-none d-md-table-cell" />
                            <th scope="col">{{ __('admin.status.label') }}</th>
                            <th scope="col" class="d-none d-md-table-cell">{{ __('collection::collection.homepage.label') }}</th>
                            {{-- One toggle column more than Categories: the order shows only on the widest screens. --}}
                            <x-admin.sort-header :listing="$listing" column="sort_order" :label="__('admin.columns.sort_order')"
                                class="d-none d-xxl-table-cell" />
                            <x-admin.sort-header :listing="$listing" column="updated_at" :label="__('admin.columns.updated_at')"
                                class="d-none d-lg-table-cell" />
                            <th scope="col" class="text-end">{{ __('admin.actions.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($collections as $collection)
                            <tr>
                                <td class="yq-table__thumb"><x-admin.thumbnail :url="$collection->publicImageUrl()" /></td>
                                <td>
                                    <x-admin.name-cell :model="$collection" :url="route('admin.collections.edit', $collection)" />
                                </td>
                                <td class="d-none d-md-table-cell">{{ $collection->products_count }}</td>
                                <td>
                                    <x-admin.status-toggle :id="'active-'.$collection->id" :on="$collection->is_active"
                                        :name="$collection->name" :url="route('admin.collections.toggle-active', $collection)" />
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <x-admin.status-toggle :id="'homepage-'.$collection->id" :on="$collection->show_on_homepage"
                                        :name="$collection->name" :url="route('admin.collections.toggle-homepage', $collection)"
                                        :field="__('collection::collection.homepage.label')"
                                        :on-label="__('collection::collection.homepage.on')"
                                        :off-label="__('collection::collection.homepage.off')" />
                                </td>
                                <td class="d-none d-xxl-table-cell">{{ $collection->sort_order }}</td>
                                <td class="d-none d-lg-table-cell text-sm">
                                    <time datetime="{{ $collection->updated_at->toIso8601String() }}">{{ $collection->updated_at->translatedFormat('j M Y') }}</time>
                                </td>
                                <td>
                                    <x-admin.row-actions :name="$collection->name"
                                        :edit-url="route('admin.collections.edit', $collection)"
                                        :delete-url="route('admin.collections.destroy', $collection)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($collections->hasPages())
                <div class="yq-pagination">{{ $collections->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </div>
@endsection
