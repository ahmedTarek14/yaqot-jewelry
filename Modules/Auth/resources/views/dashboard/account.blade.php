@extends('layouts.admin.master')

@section('title', __('auth::admins.account.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('auth::admins.account.title') }}</li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" novalidate action="{{ route('admin.account.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-6">
                <x-admin.form-section :title="__('auth::admins.account.profile')">
                    <x-admin.field name="name" maxlength="255" autocomplete="name" :label="__('auth::admins.fields.name')"
                        :value="$admin->name" :required="true" />
                    <x-admin.field name="email" type="email" maxlength="255" autocomplete="username" inputmode="email"
                        :label="__('auth::admins.fields.email')" :value="$admin->email" :required="true"
                        :help="__('auth::admins.fields.email_help')" />
                </x-admin.form-section>
            </div>

            <div class="col-lg-6">
                <x-admin.form-section :title="__('auth::admins.account.password')"
                    :description="__('auth::admins.account.password_description')">
                    <x-admin.field name="current_password" type="password" autocomplete="current-password"
                        :label="__('auth::admins.fields.current_password')"
                        :help="__('auth::admins.fields.current_password_help')" />
                    <x-admin.field name="password" type="password" autocomplete="new-password"
                        :label="__('auth::admins.fields.new_password')"
                        :help="__('auth::admins.fields.password_policy')" />
                    <x-admin.field name="password_confirmation" type="password" autocomplete="new-password"
                        :label="__('auth::admins.fields.new_password_confirmation')" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.dashboard')" />
    </form>
@endsection
