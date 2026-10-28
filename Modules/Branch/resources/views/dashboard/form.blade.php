@extends('layouts.admin.master')

@section('title', $branch->exists ? __('branch::branch.edit') : __('branch::branch.create'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm">
        <a class="opacity-7 text-dark" href="{{ route('admin.branches.index') }}">{{ __('branch::branch.title') }}</a>
    </li>
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
        {{ $branch->exists ? __('branch::branch.edit') : __('branch::branch.create') }}
    </li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" enctype="multipart/form-data" novalidate
        action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}">
        @csrf
        @if ($branch->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-admin.form-section :title="__('admin.form.content')">
                    <x-admin.translatable-fields :model="$branch" :fields="[
                        'name' => ['label' => __('branch::branch.fields.name'), 'required' => true, 'maxlength' => 255],
                        'address' => [
                            'label' => __('branch::branch.fields.address'),
                            'type' => 'textarea',
                            'required' => true,
                            'maxlength' => 500,
                            'rows' => 3,
                        ],
                        'working_hours' => [
                            'label' => __('branch::branch.fields.working_hours'),
                            'type' => 'textarea',
                            'maxlength' => 500,
                            'rows' => 3,
                            'help' => __('branch::branch.fields.working_hours_help'),
                        ],
                    ]" />
                </x-admin.form-section>
            </div>

            <div class="col-lg-4">
                <x-admin.form-section :title="__('branch::branch.sections.contact')">
                    <x-admin.field name="phone" type="tel" inputmode="tel" dir="ltr" autocomplete="off" maxlength="32"
                        :label="__('branch::branch.fields.phone')" :value="$branch->phone" :required="true"
                        :help="__('branch::branch.fields.phone_help')" />
                    <x-admin.field name="whatsapp" type="tel" inputmode="tel" dir="ltr" autocomplete="off" maxlength="32"
                        :label="__('branch::branch.fields.whatsapp')" :value="$branch->whatsapp"
                        :help="__('branch::branch.fields.whatsapp_help')" />
                    <x-admin.field name="map_url" type="url" inputmode="url" dir="ltr" maxlength="2048"
                        :label="__('branch::branch.fields.map_url')" :value="$branch->map_url"
                        :help="__('branch::branch.fields.map_url_help')" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('admin.form.settings')">
                    <x-admin.switch name="is_active" :label="__('admin.form.is_active')" :checked="$branch->is_active"
                        :help="__('admin.form.is_active_help')" />
                    <x-admin.field name="sort_order" type="number" min="0" max="65535" inputmode="numeric"
                        :label="__('admin.form.sort_order')" :value="$branch->sort_order" :required="true"
                        :help="__('admin.form.sort_order_help')" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.branches.index')" />
    </form>
@endsection
