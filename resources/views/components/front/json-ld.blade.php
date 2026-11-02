{{--
    <x-front.json-ld :data="['@context' => 'https://schema.org', '@type' => '...']" />
    Adds one structured-data block to the page's <head> (layouts.front.master's `meta` stack).
    JSON_HEX_* escapes <, >, & and quotes, so no value can close the script tag.
--}}
@props(['data'])

@push('meta')
    <script type="application/ld+json">@json($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endpush
