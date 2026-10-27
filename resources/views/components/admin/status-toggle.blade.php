{{--
    An index-row switch for a boolean (active, published, featured). admin.js sends
    a PATCH to $url and updates the switch and its text badge from the JSON reply
    { value, label, message }. The status is always shown as text, never by colour alone.
--}}
@props(['url', 'on', 'name', 'field' => null, 'onLabel' => null, 'offLabel' => null, 'id'])

<div class="yq-status-toggle">
    <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}" @checked($on)
            data-toggle-url="{{ $url }}"
            aria-label="{{ __('admin.status.toggle', ['field' => $field ?? __('admin.status.active'), 'name' => $name]) }}">
    </div>
    <span class="yq-badge {{ $on ? 'yq-badge--on' : 'yq-badge--off' }}" data-toggle-label>
        {{ $on ? ($onLabel ?? __('admin.status.active')) : ($offLabel ?? __('admin.status.inactive')) }}
    </span>
</div>
