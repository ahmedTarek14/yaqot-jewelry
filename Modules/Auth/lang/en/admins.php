<?php

return [

    'title' => 'Admin users',
    'singular' => 'Admin user',
    'create' => 'Add admin user',
    'edit' => 'Edit admin user',
    'search_placeholder' => 'Search by name or email…',
    'empty' => 'No admin users yet.',
    'table_caption' => 'Admin users with their email, status and last login',
    'you' => 'You',
    'never' => 'Never',
    'last_login' => 'Last login',

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'email_help' => 'Used to log in.',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'new_password' => 'New password',
        'new_password_confirmation' => 'Confirm new password',
        'current_password' => 'Current password',
        // REVIEW:
        'password_policy' => 'At least 10 characters, with upper and lower case letters, a number and a symbol.',
        // REVIEW:
        'new_password_help' => 'Leave empty to keep the current password. At least 10 characters, with upper and lower case letters, a number and a symbol.',
        // REVIEW:
        'is_active_help' => 'A deactivated admin cannot log in, and their open sessions end on the next request.',
        // REVIEW:
        'current_password_help' => 'Needed only to change your email or your password.',
    ],

    'form' => [
        'details' => 'Details',
        'password' => 'Password',
        'access' => 'Access',
        // REVIEW:
        'password_set_for_other' => 'Setting a new password signs this admin out of any browser that chose “Remember me”. Tell them the new password yourself: no email is sent.',
    ],

    'account' => [
        'title' => 'My account',
        'profile' => 'Profile',
        'password' => 'Change password',
        // REVIEW:
        'password_description' => 'Enter your current password to set a new one. You stay logged in on this browser.',
    ],

    'safeguards' => [
        // REVIEW:
        'self_deactivate' => 'You cannot deactivate your own account.',
        // REVIEW:
        'self_delete' => 'You cannot delete your own account.',
        // REVIEW:
        'last_deactivate' => 'This is the last active admin, so it cannot be deactivated. Activate or add another admin first.',
        // REVIEW:
        'last_delete' => 'This is the last active admin, so it cannot be deleted. Activate or add another admin first.',
    ],

    'login' => [
        // REVIEW:
        'deactivated' => 'This account has been deactivated. Please contact another administrator.',
    ],

];
