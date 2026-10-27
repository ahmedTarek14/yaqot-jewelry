<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown when deleting a record that products still reference, trashed ones
 * included. The admin should deactivate it instead.
 *
 * It is an expected outcome, not a fault: it is not logged, and the admin gets
 * the explanation back (a flash toast after the delete modal's form POST).
 */
class ModelInUseException extends RuntimeException implements ShouldntReport
{
    public function __construct(
        public readonly int $productsCount = 0,
        public readonly int $trashedCount = 0,
    ) {
        parent::__construct(self::describe($productsCount, $trashedCount));
    }

    public static function describe(int $productsCount, int $trashedCount): string
    {
        $products = trans_choice('admin.messages.products_count', $productsCount);

        // trans_choice trims its segments, so the joiner is its own string:
        // a space in English ("5 products (2 of them…)"), nothing in Arabic ("5 منتجات، منها…").
        if ($trashedCount > 0) {
            $products .= __('admin.messages.in_trash_joiner').trans_choice('admin.messages.in_trash_count', $trashedCount);
        }

        return __('admin.messages.in_use', ['products' => $products]);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return back()->with('error', $this->getMessage());
    }
}
