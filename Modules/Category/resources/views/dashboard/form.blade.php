@extends('layouts.admin.master')

@section('title', $category->exists ? __('category::category.edit') : __('category::category.create'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm">
        <a class="opacity-7 text-dark" href="{{ route('admin.categories.index') }}">{{ __('category::category.title') }}</a>
    </li>
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
        {{ $category->exists ? __('category::category.edit') : __('category::category.create') }}
    </li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" enctype="multipart/form-data" novalidate
        action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-admin.form-section :title="__('admin.form.content')">
                    <x-admin.translatable-fields :model="$category" :fields="[
                        'name' => ['label' => __('category::category.fields.name'), 'required' => true, 'maxlength' => 255],
                        'description' => [
                            'label' => __('category::category.fields.description'),
                            'type' => 'textarea',
                            'maxlength' => 5000,
                            'help' => __('category::category.fields.description_help'),
                        ],
                    ]" />
                </x-admin.form-section>
            </div>

            <div class="col-lg-4">
                <x-admin.form-section :title="__('admin.form.image.label')" :description="__('category::category.image_help')">
                    <x-admin.image-input type="categories" name="image" :url="$category->publicImageUrl()" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('admin.form.settings')">
                    <x-admin.switch name="is_active" :label="__('admin.form.is_active')" :checked="$category->is_active"
                        :help="__('admin.form.is_active_help')" />
                    <x-admin.field name="sort_order" type="number" min="0" max="65535" inputmode="numeric"
                        :label="__('admin.form.sort_order')" :value="$category->sort_order" :required="true"
                        :help="__('admin.form.sort_order_help')" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.categories.index')" />
    </form>
@endsection
