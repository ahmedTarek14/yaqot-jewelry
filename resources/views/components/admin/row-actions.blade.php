{{-- Edit link and delete button for an index row. Delete opens the shared #delete-modal. --}}
@props(['editUrl' => null, 'deleteUrl' => null, 'name'])

<div class="yq-row-actions">
    {{ $slot }}
    @if ($editUrl)
        <a href="{{ $editUrl }}" class="btn btn-sm btn-outline-dark mb-0 yq-icon-btn"
            aria-label="{{ __('admin.actions.edit_item', ['name' => $name]) }}">
            <x-icon name="edit" size="16" />
            <span class="d-none d-xl-inline" aria-hidden="true">{{ __('admin.actions.edit') }}</span>
        </a>
    @endif
    @if ($deleteUrl)
        <button type="button" class="btn btn-sm btn-outline-danger mb-0 yq-icon-btn" data-bs-toggle="modal"
            data-bs-target="#delete-modal" data-url="{{ $deleteUrl }}" data-name="{{ $name }}"
            aria-label="{{ __('admin.actions.delete_item', ['name' => $name]) }}">
            <x-icon name="trash" size="16" />
            <span class="d-none d-xl-inline" aria-hidden="true">{{ __('admin.actions.delete') }}</span>
        </button>
    @endif
</div>
