{{--
    Shared delete confirmation. Any button with
        data-bs-toggle="modal" data-bs-target="#delete-modal" data-url="{{ route('admin.x.destroy', $x) }}"
    opens it; admin.js copies data-url into the form action.
--}}
<div class="modal fade" id="delete-modal" tabindex="-1" aria-labelledby="delete-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="delete-form" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h2 class="modal-title h5" id="delete-modal-title">{{ __('admin.modals.delete_title') }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('admin.messages.close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{{ __('admin.modals.delete_body') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">{{ __('admin.modals.cancel') }}</button>
                <button type="submit" class="btn btn-danger mb-0">{{ __('admin.modals.confirm_delete') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Feedback toasts (admin.js). A session flash from a redirect is shown on load. --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="yq-toasts" aria-live="polite" aria-atomic="true"></div>
@if (session('success'))
    <div hidden data-flash-message="{{ session('success') }}" data-flash-type="success"></div>
@elseif (session('error'))
    <div hidden data-flash-message="{{ session('error') }}" data-flash-type="danger"></div>
@endif

@stack('modals')
