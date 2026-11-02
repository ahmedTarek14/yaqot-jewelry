<?php

namespace App\Support;

/**
 * The upload limits PHP really runs with (read from ini at runtime, never assumed): on a shared host
 * they are fixed and can be lower than the 64M / 8M a full 12-image gallery would like
 * (CONVENTIONS §6). The admin shows them and refuses a save that cannot fit before sending it.
 */
final class UploadLimits
{
    /** Bytes the multipart envelope and the other form fields can add to the files in a request. */
    public const OVERHEAD = 65536;

    /** post_max_size in bytes: the whole request, all files together. 0 means no limit. */
    public static function postMaxBytes(): int
    {
        return self::bytes('post_max_size');
    }

    /** upload_max_filesize in bytes: one file. 0 means no limit. */
    public static function fileMaxBytes(): int
    {
        return self::bytes('upload_max_filesize');
    }

    /**
     * "20 MB" in the current language.
     */
    public static function format(int $bytes): string
    {
        if ($bytes <= 0) {
            return __('admin.limits.unlimited');
        }

        $megabytes = $bytes / 1048576;
        $value = number_format($megabytes, $megabytes < 10 ? 1 : 0, '.', '');

        // "2.5", "8" (not "8.0"); never trim the zeros of a whole number such as "20".
        return __('admin.limits.megabytes', ['value' => str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value]);
    }

    /**
     * What the admin shows: a request is the real limit for a save, so it is the smaller of the two
     * that matter for a gallery (a file can never be larger than the request it travels in).
     *
     * @return array{post: int, file: int, postLabel: string, fileLabel: string}
     */
    public static function summary(): array
    {
        return self::summaryFor(self::postMaxBytes(), self::fileMaxBytes());
    }

    /**
     * @return array{post: int, file: int, postLabel: string, fileLabel: string}
     */
    public static function summaryFor(int $post, int $file): array
    {
        // A single file is also bounded by the request that carries it.
        if ($post > 0 && ($file === 0 || $file > $post)) {
            $file = $post;
        }

        return [
            'post' => $post,
            'file' => $file,
            'postLabel' => self::format($post),
            'fileLabel' => self::format($file),
        ];
    }

    private static function bytes(string $directive): int
    {
        $value = ini_get($directive);

        return is_string($value) && $value !== '' ? max(0, ini_parse_quantity($value)) : 0;
    }
}
