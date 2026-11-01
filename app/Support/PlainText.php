<?php

namespace App\Support;

/**
 * Long texts entered in the admin are plain text: a blank line starts a new
 * paragraph. Render each paragraph with {{ }} inside a <p> (front.css keeps
 * single line breaks with white-space: pre-line); never as HTML.
 */
final class PlainText
{
    /**
     * @return array<int, string>
     */
    public static function paragraphs(?string $text): array
    {
        if ($text === null) {
            return [];
        }

        $paragraphs = preg_split('/\R\s*\R/u', str_replace("\r\n", "\n", trim($text))) ?: [];

        return array_values(array_filter(array_map('trim', $paragraphs), fn (string $p) => $p !== ''));
    }
}
