@extends('layouts.admin.master')

@section('title', __($lang.'.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __($lang.'.title') }}</li>
@endpush

@section('content')
    <div class="card">
        <x-admin.card-header :title="__($lang.'.title')" :count="$options->total()">
            <a href="{{ route($routes.'.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ __($lang.'.create') }}
            </a>
        </x-admin.card-header>

        <x-admin.listing-toolbar :listing="$listing" :placeholder="__($lang.'.search_placeholder')">
            <x-admin.filter-select name="status" :label="__('admin.status.label')" :value="$listing->filter('status')"
                :options="['active' => __('admin.status.active'), 'inactive' => __('admin.status.inactive')]" />
        </x-admin.listing-toolbar>

        @if ($options->isEmpty())
            <x-admin.empty-state :listing="$listing" :message="__($lang.'.empty')"
                :create-url="route($routes.'.create')" :create-label="__($lang.'.create')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __($lang.'.table_caption') }}</caption>
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
                        @foreach ($options as $option)
                            <tr>
                                <td>
                                    <x-admin.name-cell :model="$option" :url="route($routes.'.edit', $option->id)" />
                                </td>
                                <td class="d-none d-md-table-cell">{{ $option->products_count }}</td>
                                <td>
                                    <x-admin.status-toggle :id="'active-'.$option->id" :on="$option->is_active"
                                        :name="$option->name" :url="route($routes.'.toggle-active', $option->id)" />
                                </td>
                                <td class="d-none d-lg-table-cell">{{ $option->sort_order }}</td>
                                <td class="d-none d-lg-table-cell text-sm">
                                    <time datetime="{{ $option->updated_at->toIso8601String() }}">{{ $option->updated_at->translatedFormat('j M Y') }}</time>
                                </td>
                                <td>
                                    <x-admin.row-actions :name="$option->name"
                                        :edit-url="route($routes.'.edit', $option->id)"
                                        :delete-url="route($routes.'.destroy', $option->id)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($options->hasPages())
                <div class="yq-pagination">{{ $options->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </div>
@endsection
