{{--
    Description, canonical, robots, Open Graph and Twitter tags of every public page.
    $seo comes from LayoutComposer (App\Support\SeoMeta). A page can pass $metaDescription,
    $metaImage and $metaImageAlt from its controller; without them the Settings description
    and the brand mark are used. Nothing is output for a description that is not set.
--}}
@if (filled($description = $metaDescription ?? $seo['description']))
    <meta name="description" content="{{ $description }}">
    <meta property="og:description" content="{{ $description }}">
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if ($seo['robots'])
    <meta name="robots" content="{{ $seo['robots'] }}">
@endif
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $seo['siteName'] }}">
<meta property="og:title" content="@hasSection('title')@yield('title') | @endif{{ $seo['siteName'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
@foreach ($seo['alternateLocales'] as $alternateLocale)
    <meta property="og:locale:alternate" content="{{ $alternateLocale }}">
@endforeach
<meta property="og:image" content="{{ $metaImage ?? $seo['image'] }}">
<meta property="og:image:alt" content="{{ $metaImageAlt ?? $seo['imageAlt'] }}">
<meta name="twitter:card" content="{{ isset($metaImage) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="@hasSection('title')@yield('title') | @endif{{ $seo['siteName'] }}">
<meta name="twitter:image" content="{{ $metaImage ?? $seo['image'] }}">
