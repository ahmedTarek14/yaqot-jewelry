{{--
    A calm "nothing here" message; the slot holds optional actions (e.g. clear filters).
    <x-front.empty-state :title="__('...')" :text="__('...')" icon="search">
        <a href="..." class="btn btn-outline-gold">...</a>
    </x-front.empty-state>
--}}
@props(['title', 'text' => null, 'icon' => 'gem'])

<div {{ $attributes->class('yq-empty') }} data-reveal>
    <span class="yq-empty__icon"><x-icon :name="$icon" size="28" /></span>
    <h2 class="yq-empty__title">{{ $title }}</h2>
    @if ($text)
        <p class="yq-empty__text">{{ $text }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="yq-empty__actions">{{ $slot }}</div>
    @endif
</div>
