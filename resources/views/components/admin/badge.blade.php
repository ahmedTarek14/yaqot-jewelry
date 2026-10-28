{{-- A read-only status badge (text, never colour alone). Index switches use <x-admin.status-toggle> instead. --}}
@props(['on' => true, 'label'])

<span {{ $attributes->class(['yq-badge', $on ? 'yq-badge--on' : 'yq-badge--off']) }}>{{ $label }}</span>
