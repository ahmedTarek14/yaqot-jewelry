<?php

namespace App\Support;

use App\Rules\DecodableImage;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Throwable;

/**
 * Admin uploads on the public disk (decision D7):
 * - the file name is a server-generated UUID; the client name is never used;
 * - images larger than config('catalog.images.max_edge') on the long edge are scaled down;
 * - every image is re-encoded to WebP, which also strips EXIF metadata;
 * - files are removed through Storage, never @unlink.
 */
class ImageUploader
{
    /**
     * Validation rules for one uploaded image of the given type, a key of
     * config('catalog.images.min_pixels') (add 'nullable' or 'required' in front).
     * Every failing rule is reported; DecodableImage only decodes a file whose header
     * already shows an allowed type within max_pixels.
     *
     * @return array<int, string|DecodableImage>
     */
    public static function rules(string $type): array
    {
        $config = config('catalog.images');
        $min = self::minPixels($type);

        return [
            'image',
            'mimes:'.implode(',', $config['mimes']),
            'max:'.$config['max_kilobytes'],
            "dimensions:min_width=$min,min_height=$min"
                .',max_width='.$config['max_pixels'].',max_height='.$config['max_pixels'],
            new DecodableImage,
        ];
    }

    /**
     * The minimum width and height for an upload type. An unknown type is a programming
     * error, never a silent default.
     */
    public static function minPixels(string $type): int
    {
        $min = config("catalog.images.min_pixels.$type");

        if (! is_int($min)) {
            throw new InvalidArgumentException("No image size limits for upload type [$type].");
        }

        return $min;
    }

    /**
     * Store the image in $directory and return its path relative to the public disk.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $config = config('catalog.images');

        $image = (new ImageManager(new Driver))->read($file->getRealPath());
        $image->scaleDown($config['max_edge'], $config['max_edge']);

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, (string) $image->toWebp($config['quality']));

        return $path;
    }

    /**
     * Store the upload (if any), then run $write with its path. If $write throws,
     * the new file is removed and the exception continues to the handler, so a
     * failed save never leaves an orphaned file behind.
     *
     * @template T
     *
     * @param  Closure(?string): T  $write
     * @return T
     */
    public function storeWhile(?UploadedFile $file, string $directory, Closure $write): mixed
    {
        $path = $file ? $this->store($file, $directory) : null;

        try {
            return $write($path);
        } catch (Throwable $e) {
            $this->delete($path);

            throw $e;
        }
    }

    /**
     * storeWhile() for several files whose directory is known only inside $write,
     * e.g. products/{id} for a product that $write creates. $write receives
     *   $store = fn (UploadedFile $file, string $directory): string  (the stored path)
     * and every file stored through it is removed again if $write throws.
     *
     * @template T
     *
     * @param  Closure(Closure(UploadedFile, string): string): T  $write
     * @return T
     */
    public function storeDuring(Closure $write): mixed
    {
        $stored = [];

        $store = function (UploadedFile $file, string $directory) use (&$stored): string {
            return $stored[] = $this->store($file, $directory);
        };

        try {
            return $write($store);
        } catch (Throwable $e) {
            foreach ($stored as $path) {
                $this->delete($path);
            }

            throw $e;
        }
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }
}
