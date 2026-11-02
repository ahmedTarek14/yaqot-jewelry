@extends('layouts.admin.master')

@section('title', $option->exists ? __($lang.'.edit') : __($lang.'.create'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm">
        <a class="opacity-7 text-dark" href="{{ route($routes.'.index') }}">{{ __($lang.'.title') }}</a>
    </li>
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
        {{ $option->exists ? __($lang.'.edit') : __($lang.'.create') }}
    </li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" novalidate
        action="{{ $option->exists ? route($routes.'.update', $option->id) : route($routes.'.store') }}">
        @csrf
        @if ($option->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <x-admin.form-section :title="__('admin.form.content')">
                    <x-admin.translatable-fields :model="$option" :fields="[
                        'name' => [
                            'label' => __($lang.'.fields.name'),
                            'required' => true,
                            'maxlength' => 255,
                            'help' => __($lang.'.fields.name_help'),
                        ],
                    ]" />
                </x-admin.form-section>
            </div>

            <div class="col-lg-5">
                <x-admin.form-section :title="__('admin.form.settings')">
                    <x-admin.switch name="is_active" :label="__('admin.form.is_active')" :checked="$option->is_active"
                        :help="__('admin.form.is_active_help')" />
                    <x-admin.field name="sort_order" type="number" min="0" max="65535" inputmode="numeric"
                        :label="__('admin.form.sort_order')" :value="$option->sort_order" :required="true"
                        :help="__('admin.form.sort_order_help')" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route($routes.'.index')" />
    </form>
@endsection
