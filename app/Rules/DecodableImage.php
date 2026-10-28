<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\RuntimeException;
use Intervention\Image\ImageManager;

/**
 * The image can really be decoded. The image/mimes/dimensions rules read only the
 * header, so a truncated or corrupt file (or a format GD cannot read, such as an
 * animated WebP) would otherwise pass validation and fail in ImageUploader with a 500.
 * Part of ImageUploader::rules(). It decodes only a file whose header already shows an
 * allowed type within config('catalog.images.max_pixels'), which bounds GD's memory;
 * anything else is left to the other rules, so the admin never gets this message twice.
 */
class DecodableImage implements ValidationRule
{
    private const HEADER_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $header = @getimagesize($value->getRealPath());
        $max = config('catalog.images.max_pixels');

        if ($header === false || ! in_array($header[2], self::HEADER_TYPES, true) || $header[0] > $max || $header[1] > $max) {
            return; // image / mimes / dimensions report it
        }

        try {
            (new ImageManager(new Driver))->read($value->getRealPath());
        } catch (RuntimeException) {
            $fail('admin.validation.image_unreadable')->translate();
        }
    }
}
