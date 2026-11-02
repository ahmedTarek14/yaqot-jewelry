@extends('layouts.admin.master')

@section('title', __('product::product.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('product::product.title') }}</li>
@endpush

@section('content')
    <div class="card">
        <x-admin.card-header :title="__('product::product.title')" :count="$products->total()">
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ __('product::product.create') }}
            </a>
        </x-admin.card-header>

        <x-admin.listing-toolbar :listing="$listing" :placeholder="__('product::product.search_placeholder')">
            <x-admin.filter-select name="status" :label="__('product::product.filters.status')" :value="$listing->filter('status')"
                :options="['published' => __('admin.status.published'), 'draft' => __('admin.status.draft')]" />
            <x-admin.filter-select name="type" :label="__('product::product.filters.type')" :value="$listing->filter('type')"
                :options="$types" />
            <x-admin.filter-select name="category" :label="__('product::product.filters.category')"
                :value="$listing->filter('category')" :options="$categories" />
            <x-admin.filter-select name="collection" :label="__('product::product.filters.collection')"
                :value="$listing->filter('collection')" :options="$collections" />
            <x-admin.filter-select name="brand" :label="__('product::product.filters.brand')" :value="$listing->filter('brand')"
                :options="$brands + ['none' => __('product::product.filters.no_brand')]" />
            <x-admin.filter-select name="karat" :label="__('product::product.filters.karat')" :value="$listing->filter('karat')"
                :options="$karats" />
            <x-admin.filter-select name="featured" :label="__('product::product.filters.featured')" :value="$listing->filter('featured')"
                :options="['yes' => __('product::product.filters.featured_yes'), 'no' => __('product::product.filters.featured_no')]" />
            <x-admin.filter-select name="trashed" :label="__('product::product.filters.trashed')" :value="$listing->filter('trashed')"
                :options="['only' => __('product::product.filters.trashed_only'), 'with' => __('product::product.filters.trashed_with')]" />
        </x-admin.listing-toolbar>

        @if ($products->isEmpty())
            <x-admin.empty-state :listing="$listing" :message="__('product::product.empty')"
                :create-url="route('admin.products.create')" :create-label="__('product::product.create')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('product::product.table_caption') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="yq-table__thumb"><span class="visually-hidden">{{ __('admin.columns.image') }}</span></th>
                            <x-admin.sort-header :listing="$listing" column="name" :label="__('admin.columns.name')" />
                            <x-admin.sort-header :listing="$listing" column="sku" :label="__('product::product.columns.sku')"
                                class="d-none d-md-table-cell" />
                            <th scope="col">{{ __('admin.status.label') }}</th>
                            <th scope="col" class="d-none d-md-table-cell">{{ __('product::product.columns.featured') }}</th>
                            <x-admin.sort-header :listing="$listing" column="updated_at" :label="__('admin.columns.updated_at')"
                                class="d-none d-lg-table-cell" />
                            <th scope="col" class="text-end">{{ __('admin.actions.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td class="yq-table__thumb"><x-admin.thumbnail :url="$product->primaryImage?->url" /></td>
                                <td>
                                    {{-- A trashed product cannot be edited until it is restored: no link. --}}
                                    <x-admin.name-cell :model="$product"
                                        :url="$product->trashed() ? null : route('admin.products.edit', $product)" />
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span dir="ltr">{{ $product->sku }}</span>
                                    <span class="d-block text-xs text-secondary">{{ __("product::product.types.{$product->type}") }}</span>
                                </td>
                                @if ($product->trashed())
                                    <td><x-admin.badge :on="false" :label="__('admin.status.in_trash')" /></td>
                                    <td class="d-none d-md-table-cell"></td>
                                @else
                                    <td>
                                        <x-admin.status-toggle :id="'published-'.$product->id" :on="$product->is_published"
                                            :name="$product->name" :url="route('admin.products.toggle-published', $product)"
                                            :field="__('product::product.fields.is_published')"
                                            :on-label="__('admin.status.published')" :off-label="__('admin.status.draft')" />
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <x-admin.status-toggle :id="'featured-'.$product->id" :on="$product->is_featured"
                                            :name="$product->name" :url="route('admin.products.toggle-featured', $product)"
                                            :field="__('product::product.fields.is_featured')"
                                            :on-label="__('product::product.featured.on')" :off-label="__('product::product.featured.off')" />
                                    </td>
                                @endif
                                <td class="d-none d-lg-table-cell text-sm">
                                    <time datetime="{{ $product->updated_at->toIso8601String() }}">{{ $product->updated_at->translatedFormat('j M Y') }}</time>
                                </td>
                                <td>
                                    @if ($product->trashed())
                                        <x-admin.row-actions :name="$product->name" :permanent="true"
                                            :delete-url="route('admin.products.force-destroy', $product)"
                                            :confirm="__('product::product.messages.force_confirm')">
                                            <x-admin.restore-button :url="route('admin.products.restore', $product)" :name="$product->name" />
                                        </x-admin.row-actions>
                                    @else
                                        <x-admin.row-actions :name="$product->name"
                                            :edit-url="route('admin.products.edit', $product)"
                                            :delete-url="route('admin.products.destroy', $product)"
                                            :confirm="__('product::product.messages.trash_confirm')" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <div class="yq-pagination">{{ $products->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </div>
@endsection
