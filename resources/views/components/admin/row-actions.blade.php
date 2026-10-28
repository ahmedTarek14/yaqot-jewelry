{{--
    Edit link and delete button for an index row. Delete opens the shared #delete-modal.
    $confirm replaces the modal's question (":name" is filled in), e.g. for a soft delete.
    $permanent labels the button "Delete permanently" (e.g. a product in the trash).
--}}
@props(['editUrl' => null, 'deleteUrl' => null, 'name', 'confirm' => null, 'permanent' => false])

<div class="yq-row-actions">
    {{ $slot }}
    @if ($editUrl)
        <a href="{{ $editUrl }}" class="btn btn-sm btn-outline-dark mb-0 yq-icon-btn"
            aria-label="{{ __('admin.actions.edit_item', ['name' => $name]) }}">
            <x-icon name="edit" size="16" />
            <span class="d-none d-xxl-inline" aria-hidden="true">{{ __('admin.actions.edit') }}</span>
        </a>
    @endif
    @if ($deleteUrl)
        <button type="button" class="btn btn-sm btn-outline-danger mb-0 yq-icon-btn" data-bs-toggle="modal"
            data-bs-target="#delete-modal" data-url="{{ $deleteUrl }}" data-name="{{ $name }}"
            @if ($confirm) data-confirm="{{ $confirm }}" @endif
            aria-label="{{ __($permanent ? 'admin.actions.delete_permanently_item' : 'admin.actions.delete_item', ['name' => $name]) }}">
            <x-icon name="trash" size="16" />
            <span class="d-none d-xxl-inline" aria-hidden="true">{{ __($permanent ? 'admin.actions.delete_permanently' : 'admin.actions.delete') }}</span>
        </button>
    @endif
</div>
