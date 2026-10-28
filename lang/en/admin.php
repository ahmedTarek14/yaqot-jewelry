<?php

return [

    'title' => 'Dashboard',

    'nav' => [
        'main' => 'Dashboard menu',
        'toggle' => 'Toggle menu',
        'dashboard' => 'Home',
        'products' => 'Products',
        'categories' => 'Categories',
        'collections' => 'Collections',
        'brands' => 'Brands',
        'branches' => 'Branches',
        'settings' => 'Settings',
    ],

    'navbar' => [
        'breadcrumb' => 'Breadcrumb',
        'view_site' => 'View site',
        'logout' => 'Log out',
    ],

    'language' => [
        'switch_to' => 'Switch to :language',
    ],

    'actions' => [
        'create' => 'Add new',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'search' => 'Search',
        'clear' => 'Clear',
        'actions' => 'Actions',
        'edit_item' => 'Edit “:name”',
        'delete_item' => 'Delete “:name”',
        'delete_permanently' => 'Delete permanently',
        'delete_permanently_item' => 'Delete “:name” permanently',
        'restore' => 'Restore',
        'restore_item' => 'Restore “:name”',
        'view_all' => 'View all',
        'view_on_site' => 'View on site',
        'view_on_site_soon' => 'Available once the public site pages are live.',
    ],

    'listing' => [
        'search_placeholder' => 'Search by name…',
        'filters' => 'Search and filters',
        'any' => 'All',
        'missing_translation' => '(no name in this language)',
        'empty' => 'Nothing here yet.',
        'empty_filtered' => 'Nothing matches your search or filters.',
        'sort_by' => 'Sort by :column',
    ],

    'status' => [
        'label' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'published' => 'Published',
        'draft' => 'Draft',
        'in_trash' => 'In trash',
        'toggle' => ':field: :name',
    ],

    'form' => [
        'required' => 'required',
        'content' => 'Content',
        'languages' => 'Languages',
        'language_names' => [
            'ar' => 'Arabic',
            'en' => 'English',
        ],
        'tab_has_errors' => '(has errors)',
        'settings' => 'Visibility and order',
        'image' => [
            'label' => 'Image',
            'hint' => 'JPG, PNG or WebP, up to :size MB, :min–:max px per side. Larger images are scaled down to :edge px.',
            'none' => 'No image yet',
            'current' => 'Current image',
            'preview' => 'Preview of the selected image (not saved yet)',
            'remove' => 'Remove the current image',
        ],
        'multiselect' => [
            'search' => 'Search :field…',
            'selected' => 'Selected: :count',
            'none_match' => 'No matches.',
            'empty' => 'Nothing to choose from yet.',
        ],
        'gallery' => [
            'label' => 'Images',
            'add' => 'Add images',
            'help' => 'Up to :max images. The first image is the primary one; drag the images or use the arrows to reorder them. Nothing changes until you save.',
            'empty' => 'No images yet.',
            'primary' => 'Primary',
            'removed_note' => 'Removed on save',
            'new_note' => 'New',
            'move_earlier' => 'Move image earlier',
            'move_later' => 'Move image later',
            'remove' => 'Remove image',
        ],
        'sort_order' => 'Sort order',
        'sort_order_help' => 'Lower numbers appear first.',
        'is_active' => 'Active',
        'is_active_help' => 'Inactive items are hidden from the public site.',
    ],

    'columns' => [
        'name' => 'Name',
        'image' => 'Image',
        'products' => 'Products',
        'sort_order' => 'Order',
        'updated_at' => 'Updated',
    ],

    'modals' => [
        'delete_title' => 'Confirm deletion',
        'delete_body' => 'Are you sure you want to delete “:name”? This cannot be undone.',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Delete',
    ],

    'messages' => [
        'saved' => 'Saved successfully.',
        'deleted' => 'Deleted successfully.',
        'restored' => 'Restored successfully.',
        'validation_failed' => 'Please review the highlighted fields.',
        'session_expired' => 'Your session has expired. Please refresh the page and try again.',
        'error' => 'An unexpected error occurred. Please try again.',
        'too_large' => 'Nothing was saved: the files are too large to send together. Add fewer images at a time, then save again.',
        'saving' => 'Saving…',
        'close' => 'Close',
        'status_changed' => 'Status changed to “:status”.',
        'in_use' => 'This item is linked to :products, so it cannot be deleted. Deactivate it instead.',
        'products_count' => '{1} 1 product|[2,*] :count products',
        'in_trash_joiner' => ' ',
        'in_trash_count' => '{1} (1 of them is in the trash and still counts)|[2,*] (:count of them are in the trash and still count)',
    ],

    'validation' => [
        'egyptian_mobile' => 'The :attribute must be an Egyptian mobile number, for example 01012345678.',
        'image_unreadable' => 'The :attribute could not be read as an image. It may be damaged or in an unsupported format (for example an animated WebP); please export it again as JPG, PNG or WebP.',
        'egyptian_phone' => 'The :attribute must be an Egyptian landline, mobile or hotline number, for example 0223456789, 01012345678 or 19XXX.',
    ],

    // admin.js (passed through LayoutComposer). :position and :total are filled in the browser.
    'scripts' => [
        'gallery_image' => 'Image :position',
        'gallery_move_earlier' => 'Move image :position earlier',
        'gallery_move_later' => 'Move image :position later',
        'gallery_remove' => 'Remove image :position',
        'gallery_keep' => 'Keep image :position',
        'gallery_discard' => 'Discard new image :position',
        'gallery_moved' => 'Image moved to position :position of :total.',
        'gallery_marked' => 'Image :position will be removed when you save.',
        'gallery_kept' => 'Image :position will be kept.',
        'gallery_discarded' => 'New image discarded.',
        'gallery_added' => 'New images added: :count. They upload when you save.',
        'gallery_type_rejected' => '“:name” is not a JPG, PNG or WebP image.',
    ],

];
