{{-- Save / cancel bar at the end of an admin form. Sticky, so it stays reachable on long forms. --}}
@props(['cancelUrl'])

<div class="yq-form-actions">
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary mb-0">{{ __('admin.actions.cancel') }}</a>
    <button type="submit" class="btn btn-primary mb-0">{{ __('admin.actions.save') }}</button>
</div>
