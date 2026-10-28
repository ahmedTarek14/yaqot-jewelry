@extends('layouts.admin.master')

@section('title', __('branch::branch.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('branch::branch.title') }}</li>
@endpush

@section('content')
    <div class="card">
        <x-admin.card-header :title="__('branch::branch.title')" :count="$branches->total()">
            <a href="{{ route('admin.branches.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ __('branch::branch.create') }}
            </a>
        </x-admin.card-header>

        <x-admin.listing-toolbar :listing="$listing" :placeholder="__('branch::branch.search_placeholder')">
            <x-admin.filter-select name="status" :label="__('admin.status.label')" :value="$listing->filter('status')"
                :options="['active' => __('admin.status.active'), 'inactive' => __('admin.status.inactive')]" />
        </x-admin.listing-toolbar>

        @if ($branches->isEmpty())
            <x-admin.empty-state :listing="$listing" :message="__('branch::branch.empty')"
                :create-url="route('admin.branches.create')" :create-label="__('branch::branch.create')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('branch::branch.table_caption') }}</caption>
                    <thead>
                        <tr>
                            <x-admin.sort-header :listing="$listing" column="name" :label="__('admin.columns.name')" />
                            <th scope="col" class="d-none d-md-table-cell">{{ __('branch::branch.columns.phone') }}</th>
                            <th scope="col">{{ __('admin.status.label') }}</th>
                            <x-admin.sort-header :listing="$listing" column="sort_order" :label="__('admin.columns.sort_order')"
                                class="d-none d-lg-table-cell" />
                            <x-admin.sort-header :listing="$listing" column="updated_at" :label="__('admin.columns.updated_at')"
                                class="d-none d-lg-table-cell" />
                            <th scope="col" class="text-end">{{ __('admin.actions.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td>
                                    <x-admin.name-cell :model="$branch" :url="route('admin.branches.edit', $branch)" />
                                </td>
                                <td class="d-none d-md-table-cell"><bdi dir="ltr">{{ $branch->phone }}</bdi></td>
                                <td>
                                    <x-admin.status-toggle :id="'active-'.$branch->id" :on="$branch->is_active"
                                        :name="$branch->name" :url="route('admin.branches.toggle-active', $branch)" />
                                </td>
                                <td class="d-none d-lg-table-cell">{{ $branch->sort_order }}</td>
                                <td class="d-none d-lg-table-cell text-sm">
                                    <time datetime="{{ $branch->updated_at->toIso8601String() }}">{{ $branch->updated_at->translatedFormat('j M Y') }}</time>
                                </td>
                                <td>
                                    <x-admin.row-actions :name="$branch->name"
                                        :edit-url="route('admin.branches.edit', $branch)"
                                        :delete-url="route('admin.branches.destroy', $branch)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($branches->hasPages())
                <div class="yq-pagination">{{ $branches->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </div>
@endsection
