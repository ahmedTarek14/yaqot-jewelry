@extends('layouts.admin.master')

@section('title', __('setting::setting.title'))

@push('breadcrumb')
    <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ __('setting::setting.title') }}</li>
@endpush

@section('content')
    <form class="ajax-form" method="POST" enctype="multipart/form-data" novalidate action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <p class="text-sm text-muted">{{ __('setting::setting.intro') }}</p>

        <div class="row g-4">
            <div class="col-lg-8">
                @foreach ($heroSlides as $slide)
                    <x-admin.form-section :title="__('setting::setting.sections.hero.slide', ['number' => $slide, 'subject' => __('setting::setting.sections.hero.subjects.'.$slide)])"
                        :description="$loop->first ? __('setting::setting.sections.hero.description') : null">
                        <x-admin.switch :name="'hero_'.$slide.'_enabled'" :label="__('setting::setting.fields.hero_enabled')"
                            :checked="($values['hero_'.$slide.'_enabled'] ?? '1') !== '0'"
                            :help="__('setting::setting.fields.hero_enabled_help')" />
                        <x-admin.translatable-fields :id="'hero-'.$slide.'-translations'" :model="null" :values="$values" :fields="[
                            'hero_'.$slide.'_title' => ['label' => __('setting::setting.fields.hero_title'), 'maxlength' => 255],
                            'hero_'.$slide.'_subtitle' => ['label' => __('setting::setting.fields.hero_subtitle'), 'type' => 'textarea', 'maxlength' => 500, 'rows' => 2],
                            'hero_'.$slide.'_cta_label' => ['label' => __('setting::setting.fields.hero_cta_label'), 'maxlength' => 100],
                        ]" />
                        <x-admin.field :name="'hero_'.$slide.'_cta_url'" type="text" inputmode="url" dir="ltr" maxlength="2048"
                            autocomplete="off" spellcheck="false"
                            :label="__('setting::setting.fields.hero_cta_url')" :value="$values['hero_'.$slide.'_cta_url']"
                            :help="__('setting::setting.fields.hero_cta_help')" />
                    </x-admin.form-section>
                @endforeach

                <x-admin.form-section :title="__('setting::setting.sections.homepage.title')"
                    :description="__('setting::setting.sections.homepage.description')">
                    <x-admin.select name="prominent_collection" :label="__('setting::setting.fields.prominent_collection')"
                        :options="$collections" :value="$values['prominent_collection']"
                        :placeholder="__('setting::setting.fields.prominent_collection_none')"
                        :help="__('setting::setting.fields.prominent_collection_help')" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('setting::setting.sections.about.title')"
                    :description="__('setting::setting.sections.about.description')">
                    <x-admin.translatable-fields id="about-translations" :model="null" :values="$values" :fields="[
                        'about_content' => [
                            'label' => __('setting::setting.fields.about_content'),
                            'type' => 'textarea',
                            'maxlength' => 10000,
                            'rows' => 10,
                            'help' => __('setting::setting.fields.about_content_help'),
                        ],
                    ]" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('setting::setting.sections.footer.title')"
                    :description="__('setting::setting.sections.footer.description')">
                    <x-admin.translatable-fields id="footer-translations" :model="null" :values="$values" :fields="[
                        'footer_text' => ['label' => __('setting::setting.fields.footer_text'), 'type' => 'textarea', 'maxlength' => 1000, 'rows' => 3],
                    ]" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('setting::setting.sections.seo.title')"
                    :description="__('setting::setting.sections.seo.description')">
                    <x-admin.translatable-fields id="seo-translations" :model="null" :values="$values" :fields="[
                        'meta_description' => [
                            'label' => __('setting::setting.fields.meta_description'),
                            'type' => 'textarea',
                            'maxlength' => 300,
                            'rows' => 3,
                            'help' => __('setting::setting.fields.meta_description_help'),
                        ],
                    ]" />
                </x-admin.form-section>
            </div>

            <div class="col-lg-4">
                <x-admin.form-section :title="__('setting::setting.sections.contact.title')"
                    :description="__('setting::setting.sections.contact.description')">
                    <x-admin.field name="whatsapp_number" type="tel" inputmode="tel" dir="ltr" autocomplete="off" maxlength="32"
                        :label="__('setting::setting.fields.whatsapp_number')" :value="$values['whatsapp_number']"
                        :help="__('setting::setting.fields.whatsapp_number_help')" />
                    <x-admin.field name="phone" type="tel" inputmode="tel" dir="ltr" autocomplete="off" maxlength="32"
                        :label="__('setting::setting.fields.phone')" :value="$values['phone']"
                        :help="__('setting::setting.fields.phone_help')" />
                    <x-admin.field name="email" type="email" inputmode="email" dir="ltr" autocomplete="off" maxlength="255"
                        :label="__('setting::setting.fields.email')" :value="$values['email']" />
                </x-admin.form-section>

                <x-admin.form-section :title="__('setting::setting.sections.social.title')"
                    :description="__('setting::setting.sections.social.description')">
                    @foreach (['facebook_url', 'instagram_url', 'tiktok_url'] as $key)
                        <x-admin.field :name="$key" type="url" inputmode="url" dir="ltr" maxlength="2048"
                            :label="__('setting::setting.fields.'.$key)" :value="$values[$key]"
                            :help="__('setting::setting.fields.url_help')" />
                    @endforeach
                </x-admin.form-section>

                <x-admin.form-section :title="__('setting::setting.sections.analytics.title')"
                    :description="__('setting::setting.sections.analytics.description')">
                    <x-admin.field name="ga_measurement_id" type="text" dir="ltr" autocomplete="off" maxlength="20" spellcheck="false"
                        placeholder="G-XXXXXXXXXX"
                        :label="__('setting::setting.fields.ga_measurement_id')" :value="$values['ga_measurement_id']"
                        :help="__('setting::setting.fields.ga_measurement_id_help')" />
                </x-admin.form-section>
            </div>
        </div>

        <x-admin.form-actions :cancel-url="route('admin.dashboard')" />
    </form>
@endsection
