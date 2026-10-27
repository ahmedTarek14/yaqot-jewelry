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
        'validation_failed' => 'Please review the highlighted fields.',
        'session_expired' => 'Your session has expired. Please refresh the page and try again.',
        'error' => 'An unexpected error occurred. Please try again.',
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
    ],

];
