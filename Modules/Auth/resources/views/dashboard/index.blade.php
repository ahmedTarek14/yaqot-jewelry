@extends('layouts.admin.master')

@section('title', __('auth::admins.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('auth::admins.title') }}</li>
@endpush

@section('content')
    <div class="card">
        <x-admin.card-header :title="__('auth::admins.title')" :count="$admins->total()">
            <a href="{{ route('admin.admins.create') }}" class="btn btn-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                <x-icon name="plus" size="16" /> {{ __('auth::admins.create') }}
            </a>
        </x-admin.card-header>

        <x-admin.listing-toolbar :listing="$listing" :placeholder="__('auth::admins.search_placeholder')">
            <x-admin.filter-select name="status" :label="__('admin.status.label')" :value="$listing->filter('status')"
                :options="['active' => __('admin.status.active'), 'inactive' => __('admin.status.inactive')]" />
        </x-admin.listing-toolbar>

        @if ($admins->isEmpty())
            <x-admin.empty-state :listing="$listing" :message="__('auth::admins.empty')"
                :create-url="route('admin.admins.create')" :create-label="__('auth::admins.create')" />
        @else
            <div class="table-responsive">
                <table class="table yq-table mb-0">
                    <caption class="visually-hidden">{{ __('auth::admins.table_caption') }}</caption>
                    <thead>
                        <tr>
                            <x-admin.sort-header :listing="$listing" column="name" :label="__('auth::admins.fields.name')" />
                            <x-admin.sort-header :listing="$listing" column="email" :label="__('auth::admins.fields.email')"
                                class="d-none d-md-table-cell" />
                            <x-admin.sort-header :listing="$listing" column="is_active" :label="__('admin.status.label')" />
                            <x-admin.sort-header :listing="$listing" column="last_login_at" :label="__('auth::admins.last_login')"
                                class="d-none d-lg-table-cell" />
                            <th scope="col" class="text-end">{{ __('admin.actions.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($admins as $admin)
                            <tr>
                                <td>
                                    <a href="{{ $admin->isSignedIn() ? route('admin.account.edit') : route('admin.admins.edit', $admin) }}" class="yq-table__title">{{ $admin->name }}</a>
                                    @if ($admin->isSignedIn())
                                        <span class="yq-badge yq-badge--on ms-1">{{ __('auth::admins.you') }}</span>
                                    @endif
                                    <bdi class="d-block d-md-none text-xs text-muted">{{ $admin->email }}</bdi>
                                </td>
                                <td class="d-none d-md-table-cell"><bdi>{{ $admin->email }}</bdi></td>
                                <td>
                                    @if ($admin->isSignedIn())
                                        <x-admin.badge :on="$admin->is_active"
                                            :label="$admin->is_active ? __('admin.status.active') : __('admin.status.inactive')" />
                                    @else
                                        <x-admin.status-toggle :id="'active-'.$admin->id" :on="$admin->is_active"
                                            :name="$admin->name" :url="route('admin.admins.toggle-active', $admin)" />
                                    @endif
                                </td>
                                <td class="d-none d-lg-table-cell text-sm">
                                    @if ($admin->last_login_at)
                                        <time datetime="{{ $admin->last_login_at->toIso8601String() }}">{{ $admin->last_login_at->translatedFormat('j M Y, H:i') }}</time>
                                    @else
                                        {{ __('auth::admins.never') }}
                                    @endif
                                </td>
                                <td>
                                    <x-admin.row-actions :name="$admin->name"
                                        :edit-url="$admin->isSignedIn() ? route('admin.account.edit') : route('admin.admins.edit', $admin)"
                                        :delete-url="$admin->isSignedIn() ? null : route('admin.admins.destroy', $admin)" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($admins->hasPages())
                <div class="yq-pagination">{{ $admins->onEachSide(1)->links() }}</div>
            @endif
        @endif
    </div>
@endsection
