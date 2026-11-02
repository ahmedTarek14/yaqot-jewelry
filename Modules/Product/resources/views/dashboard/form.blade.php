@extends('layouts.admin.master')

@section('title', $product->exists ? __('product::product.edit') : __('product::product.create'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm">
        <a class="opacity-7 text-dark" href="{{ route('admin.products.index') }}">{{ __('product::product.title') }}</a>
    </li>
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
        {{ $product->exists ? __('product::product.edit') : __('product::product.create') }}
    </li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" enctype="multipart/form-data" novalidate
        action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-admin.form-section :title="__('admin.form.content')">
                    <x-admin.translatable-fields :model="$product" :fields="[
                        'name' => ['label' => __('product::product.fields.name'), 'required' => true, 'maxlength' => 255],
                        'description' => [
                            'label' => __('product::product.fields.description'),
                            'type' => 'textarea',
                            'maxlength' => 5000,
                            'help' => __('product::product.fields.description_help'),
                        ],
                    ]" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('product::product.sections.images')" :description="__('product::product.images_help')">
                    <x-admin.image-gallery type="products" :images="$images" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('product::product.sections.details')">
                    <div class="row">
                        <div class="col-md-6">
                            <x-admin.field name="sku" :label="__('product::product.fields.sku')" :value="$product->sku"
                                :required="true" maxlength="64" dir="ltr" autocomplete="off"
                                :help="__('product::product.fields.sku_help')" />
                        </div>
                        <div class="col-md-6">
                            <x-admin.select name="brand_id" :label="__('product::product.fields.brand')" :options="$brands"
                                :value="$product->brand_id" :placeholder="__('product::product.fields.no_brand')" />
                        </div>
                        <div class="col-md-6">
                            <x-admin.select name="type" :label="__('product::product.fields.type')" :options="$types"
                                :value="$product->type" :required="true" data-jewelry-toggle="jewelry"
                                :help="__('product::product.fields.type_help')" />
                        </div>
                        <div class="col-md-6">
                            <x-admin.select name="karat" :label="__('product::product.fields.karat')" :options="$karats"
                                :value="$product->karat" :placeholder="__('product::product.fields.not_specified')" />
                        </div>
                        {{-- Gold colour and stones are for jewelry only: admin.js hides and disables them for
                             bars and coins; the server rejects (and clears) them anyway. --}}
                        <div class="col-md-6" data-jewelry-only @if (! $product->isJewelry()) hidden @endif>
                            <x-admin.select name="gold_color_id" :label="__('product::product.fields.gold_color')" :options="$goldColors"
                                :value="$product->gold_color_id" :placeholder="__('product::product.fields.not_specified')"
                                :disabled="! $product->isJewelry()" />
                        </div>
                        <div class="col-md-6" data-jewelry-only @if (! $product->isJewelry()) hidden @endif>
                            <x-admin.select name="stone_type_id" :label="__('product::product.fields.stone_type')" :options="$stoneTypes"
                                :value="$product->stone_type_id" :placeholder="__('product::product.fields.not_specified')"
                                :disabled="! $product->isJewelry()" />
                        </div>
                        <div class="col-md-6">
                            <x-admin.field name="weight_grams" type="number" step="0.001" min="0" max="99999.999" inputmode="decimal"
                                :label="__('product::product.fields.weight_grams')" :value="$product->weight_grams"
                                :help="__('product::product.fields.weight_help')" />
                        </div>
                    </div>
                </x-admin.form-section>
            </div>

            <div class="col-lg-4">
                <x-admin.form-section :title="__('product::product.sections.publishing')">
                    <x-admin.switch name="is_published" :label="__('product::product.fields.is_published')"
                        :checked="$product->is_published" :help="__('product::product.fields.is_published_help')" />
                    <x-admin.switch name="is_featured" :label="__('product::product.fields.is_featured')"
                        :checked="$product->is_featured" :help="__('product::product.fields.is_featured_help')" />

                    @if ($viewUrl)
                        <a href="{{ $viewUrl }}" target="_blank" rel="noopener" aria-describedby="help-view-on-site"
                            class="btn btn-outline-dark btn-sm mb-0 d-inline-flex align-items-center gap-1">
                            <x-icon name="external-link" size="16" /> {{ __('admin.actions.view_on_site') }}
                        </a>
                        <div class="form-text" id="help-view-on-site">{{ __('product::product.fields.view_on_site_help') }}</div>
                    @else
                        <button type="button" class="btn btn-outline-dark btn-sm mb-0 d-inline-flex align-items-center gap-1"
                            disabled aria-disabled="true" aria-describedby="help-view-on-site">
                            <x-icon name="external-link" size="16" /> {{ __('admin.actions.view_on_site') }}
                        </button>
                        <div class="form-text" id="help-view-on-site">{{ __('admin.actions.view_on_site_unsaved') }}</div>
                    @endif
                </x-admin.form-section>

                <x-admin.form-section :title="__('product::product.sections.classification')">
                    <x-admin.multiselect name="categories" :label="__('product::product.fields.categories')"
                        :options="$categories" :selected="$selectedCategories" />
                    <x-admin.multiselect name="collections" :label="__('product::product.fields.collections')"
                        :options="$collections" :selected="$selectedCollections" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.products.index')" />
    </form>
@endsection
