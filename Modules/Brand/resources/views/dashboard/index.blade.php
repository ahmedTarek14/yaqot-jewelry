@extends('layouts.admin.master')

@section('title', __('brand::brand.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('brand::brand.title') }}</li>
@endpush

@section('content')
    <div class="card">
        <x-admin.card-header :title="__('brand::brand.title')" :count="$brands->total()">
            <a href="{{ route('admin.brands.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ __('brand::brand.create') }}
            </a>
        </x-admin.card-header>

        <x-admin.listing-toolbar :listing="$listing" :placeholder="__('brand::brand.search_placeholder')">
            <x-admin.filter-select name="status" :label="__('admin.status.label')" :value="$listing->filter('status')"
                :options="['active' => __('admin.status.active'), 'inactive' => __('admin.status.inactive')]" />
        </x-admin.listing-toolbar>

        @if ($brands->isEmpty())
            <x-admin.empty-state :listing="$listing" :message="__('brand::brand.empty')"
                :create-url="route('admin.brands.create')" :create-label="__('brand::brand.create')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('brand::brand.table_caption') }}</caption>
                    <thead>
                        <tr>
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
                        @foreach ($brands as $brand)
                            <tr>
                                <td>
                                    <x-admin.name-cell :model="$brand" :url="route('admin.brands.edit', $brand)" />
                                </td>
                                <td class="d-none d-md-table-cell">{{ $brand->products_count }}</td>
                                <td>
                                    <x-admin.status-toggle :id="'active-'.$brand->id" :on="$brand->is_active"
                                        :name="$brand->name" :url="route('admin.brands.toggle-active', $brand)" />
                                </td>
                                <td class="d-none d-lg-table-cell">{{ $brand->sort_order }}</td>
                                <td class="d-none d-lg-table-cell text-sm">
                                    <time datetime="{{ $brand->updated_at->toIso8601String() }}">{{ $brand->updated_at->translatedFormat('j M Y') }}</time>
                                </td>
                                <td>
                                    <x-admin.row-actions :name="$brand->name"
                                        :edit-url="route('admin.brands.edit', $brand)"
                                        :delete-url="route('admin.brands.destroy', $brand)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($brands->hasPages())
                <div class="yq-pagination">{{ $brands->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </div>
@endsection
