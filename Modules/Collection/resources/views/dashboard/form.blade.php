@extends('layouts.admin.master')

@section('title', $collection->exists ? __('collection::collection.edit') : __('collection::collection.create'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm">
        <a class="opacity-7 text-dark" href="{{ route('admin.collections.index') }}">{{ __('collection::collection.title') }}</a>
    </li>
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
        {{ $collection->exists ? __('collection::collection.edit') : __('collection::collection.create') }}
    </li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" enctype="multipart/form-data" novalidate
        action="{{ $collection->exists ? route('admin.collections.update', $collection) : route('admin.collections.store') }}">
        @csrf
        @if ($collection->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-admin.form-section :title="__('admin.form.content')">
                    <x-admin.translatable-fields :model="$collection" :fields="[
                        'name' => ['label' => __('collection::collection.fields.name'), 'required' => true, 'maxlength' => 255],
                        'description' => [
                            'label' => __('collection::collection.fields.description'),
                            'type' => 'textarea',
                            'maxlength' => 5000,
                            'help' => __('collection::collection.fields.description_help'),
                        ],
                    ]" />
                </x-admin.form-section>
            </div>

            <div class="col-lg-4">
                <x-admin.form-section :title="__('admin.form.image.label')" :description="__('collection::collection.image_help')">
                    <x-admin.image-input type="collections" name="image" :url="$collection->publicImageUrl()" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('admin.form.settings')">
                    <x-admin.switch name="is_active" :label="__('admin.form.is_active')" :checked="$collection->is_active"
                        :help="__('admin.form.is_active_help')" />
                    <x-admin.switch name="show_on_homepage" :label="__('collection::collection.fields.show_on_homepage')"
                        :checked="$collection->show_on_homepage" :help="__('collection::collection.fields.show_on_homepage_help')" />
                    <x-admin.field name="sort_order" type="number" min="0" max="65535" inputmode="numeric"
                        :label="__('admin.form.sort_order')" :value="$collection->sort_order" :required="true"
                        :help="__('admin.form.sort_order_help')" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.collections.index')" />
    </form>
@endsection
