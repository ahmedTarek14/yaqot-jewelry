@extends('layouts.admin.master')

@section('title', $admin->exists ? __('auth::admins.edit') : __('auth::admins.create'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm">
        <a class="opacity-7 text-dark" href="{{ route('admin.admins.index') }}">{{ __('auth::admins.title') }}</a>
    </li>
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
        {{ $admin->exists ? __('auth::admins.edit') : __('auth::admins.create') }}
    </li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" novalidate autocomplete="off"
        action="{{ $admin->exists ? route('admin.admins.update', $admin) : route('admin.admins.store') }}">
        @csrf
        @if ($admin->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <x-admin.form-section :title="__('auth::admins.form.details')">
                    <x-admin.field name="name" maxlength="255" autocomplete="off" :label="__('auth::admins.fields.name')"
                        :value="$admin->name" :required="true" />
                    <x-admin.field name="email" type="email" maxlength="255" autocomplete="off" inputmode="email"
                        :label="__('auth::admins.fields.email')" :value="$admin->email" :required="true"
                        :help="__('auth::admins.fields.email_help')" />

                    @if ($admin->exists)
                        <x-admin.switch name="is_active" :label="__('admin.form.is_active')" :checked="$admin->is_active"
                            :help="__('auth::admins.fields.is_active_help')" />
                    @endif
                </x-admin.form-section>
            </div>

            <div class="col-lg-6">
                <x-admin.form-section :title="__('auth::admins.form.password')"
                    :description="$admin->exists ? __('auth::admins.form.password_set_for_other') : null">
                    <x-admin.field name="password" type="password" autocomplete="new-password"
                        :label="$admin->exists ? __('auth::admins.fields.new_password') : __('auth::admins.fields.password')"
                        :required="! $admin->exists"
                        :help="$admin->exists ? __('auth::admins.fields.new_password_help') : __('auth::admins.fields.password_policy')" />
                    <x-admin.field name="password_confirmation" type="password" autocomplete="new-password"
                        :label="$admin->exists ? __('auth::admins.fields.new_password_confirmation') : __('auth::admins.fields.password_confirmation')"
                        :required="! $admin->exists" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.admins.index')" />
    </form>
@endsection
