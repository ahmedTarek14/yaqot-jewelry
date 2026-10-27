@extends('layouts.admin.master')

@section('title', __('category::category.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('category::category.title') }}</li>
@endpush

@section('content')
    <div class="card">
        <x-admin.card-header :title="__('category::category.title')" :count="$categories->total()">
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ __('category::category.create') }}
            </a>
        </x-admin.card-header>

        <x-admin.listing-toolbar :listing="$listing" :placeholder="__('category::category.search_placeholder')">
            <x-admin.filter-select name="status" :label="__('admin.status.label')" :value="$listing->filter('status')"
                :options="['active' => __('admin.status.active'), 'inactive' => __('admin.status.inactive')]" />
        </x-admin.listing-toolbar>

        @if ($categories->isEmpty())
            <x-admin.empty-state :listing="$listing" :message="__('category::category.empty')"
                :create-url="route('admin.categories.create')" :create-label="__('category::category.create')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('category::category.table_caption') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="yq-table__thumb"><span class="visually-hidden">{{ __('admin.columns.image') }}</span></th>
                            <x-admin.sort-header :listing="$listing" column="name" :label="__('admin.columns.name')" />
                            <x-admin.sort-header :listing="$listing" column="products_count" :label="__('admin.columns.products')"
                                class="d-none d-md-table-cell" />
                            <th scope="col">{{ __('admin.status.label') }}</th>
                            <x-admin.sort-header :listing="$listing" column="sort_order" :label="__('admin.columns.sort_order')"
                                class="d-none d-lg-table-cell" />
                            <x-admin.sort-header :listing="$listing" column="updated_at" :label="__('admin.columns.updated_at')"
                                class="d-none d-lg-table-cell" />
                            <th scope="col" class="text-end">{{ __('admin.actions.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="yq-table__thumb"><x-admin.thumbnail :url="$category->publicImageUrl()" /></td>
                                <td>
                                    <x-admin.name-cell :model="$category" :url="route('admin.categories.edit', $category)" />
                                </td>
                                <td class="d-none d-md-table-cell">{{ $category->products_count }}</td>
                                <td>
                                    <x-admin.status-toggle :id="'active-'.$category->id" :on="$category->is_active"
                                        :name="$category->name" :url="route('admin.categories.toggle-active', $category)" />
                                </td>
                                <td class="d-none d-lg-table-cell">{{ $category->sort_order }}</td>
                                <td class="d-none d-lg-table-cell text-sm">
                                    <time datetime="{{ $category->updated_at->toIso8601String() }}">{{ $category->updated_at->translatedFormat('j M Y') }}</time>
                                </td>
                                <td>
                                    <x-admin.row-actions :name="$category->name"
                                        :edit-url="route('admin.categories.edit', $category)"
                                        :delete-url="route('admin.categories.destroy', $category)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="yq-pagination">{{ $categories->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </div>
@endsection
