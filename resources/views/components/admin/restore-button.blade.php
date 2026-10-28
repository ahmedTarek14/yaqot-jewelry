{{--
    Restore a soft-deleted record: a normal form PATCH to $url. The controller restores it and
    redirects back with a "success" flash. Use inside <x-admin.row-actions> for trashed rows.
--}}
@props(['url', 'name'])

<form method="POST" action="{{ $url }}" class="d-inline-flex">
    @csrf
    @method('PATCH')
    <button type="submit" class="btn btn-sm btn-outline-dark mb-0 yq-icon-btn"
        aria-label="{{ __('admin.actions.restore_item', ['name' => $name]) }}">
        <x-icon name="rotate-ccw" size="16" />
        <span class="d-none d-xxl-inline" aria-hidden="true">{{ __('admin.actions.restore') }}</span>
    </button>
</form>
