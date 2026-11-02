<?php

namespace Modules\Auth\Support;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Thrown when deactivating or deleting an admin would lock the dashboard out
 * (yourself, or the last active admin). Expected, so it is never logged.
 * It renders itself like ModelInUseException: a redirect back with an `error` flash,
 * or 409 JSON when JSON is expected. Use {@see self::asValidation()} for a form field.
 */
class AdminSafeguardException extends RuntimeException implements ShouldntReport
{
    public const SELF = 'self';

    public const LAST = 'last';

    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function self(string $action): self
    {
        return new self(self::SELF, __("auth::admins.safeguards.self_$action"));
    }

    public static function lastActive(string $action): self
    {
        return new self(self::LAST, __("auth::admins.safeguards.last_$action"));
    }

    /**
     * The same refusal as a 422 under a form field.
     */
    public function asValidation(string $field): ValidationException
    {
        return ValidationException::withMessages([$field => [$this->getMessage()]]);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return back()->with('error', $this->getMessage());
    }
}
