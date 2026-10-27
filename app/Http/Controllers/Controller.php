<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Success response for an admin AJAX form (admin.js shows the toast, then follows the redirect).
     */
    protected function savedResponse(string $redirect): JsonResponse
    {
        return response()->json(['message' => __('admin.messages.saved'), 'redirect' => $redirect]);
    }

    /**
     * Response for an index-page switch (admin.js updates the switch and its text badge).
     */
    protected function toggledResponse(bool $value, string $onLabel, string $offLabel): JsonResponse
    {
        $label = $value ? $onLabel : $offLabel;

        return response()->json([
            'value' => $value,
            'label' => $label,
            'message' => __('admin.messages.status_changed', ['status' => $label]),
        ]);
    }
}
