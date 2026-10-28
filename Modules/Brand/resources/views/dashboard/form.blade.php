@extends('layouts.admin.master')

@section('title', $brand->exists ? __('brand::brand.edit') : __('brand::brand.create'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm">
        <a class="opacity-7 text-dark" href="{{ route('admin.brands.index') }}">{{ __('brand::brand.title') }}</a>
    </li>
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
        {{ $brand->exists ? __('brand::brand.edit') : __('brand::brand.create') }}
    </li>
@endpush

@section('content')
    {{-- Text only: no logo field until the owner confirms permission to use brand logos. --}}
    <form class="ajax-form" method="POST" enctype="multipart/form-data" novalidate
        action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}">
        @csrf
        @if ($brand->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <x-admin.form-section :title="__('admin.form.content')">
                    <x-admin.translatable-fields :model="$brand" :fields="[
                        'name' => [
                            'label' => __('brand::brand.fields.name'),
                            'required' => true,
                            'maxlength' => 255,
                            'help' => __('brand::brand.fields.name_help'),
                        ],
                    ]" />
                </x-admin.form-section>
            </div>

            <div class="col-lg-5">
                <x-admin.form-section :title="__('admin.form.settings')">
                    <x-admin.switch name="is_active" :label="__('admin.form.is_active')" :checked="$brand->is_active"
                        :help="__('admin.form.is_active_help')" />
                    <x-admin.field name="sort_order" type="number" min="0" max="65535" inputmode="numeric"
                        :label="__('admin.form.sort_order')" :value="$brand->sort_order" :required="true"
                        :help="__('admin.form.sort_order_help')" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.brands.index')" />
    </form>
@endsection
