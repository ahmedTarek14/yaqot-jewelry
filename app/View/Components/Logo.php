<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The brand logo as a responsive <img>.
 *
 * The image files and their dimensions come from
 * public/assets/images/brand/generated/manifest.json, which
 * `php artisan yaqot:brand-assets` writes from logo.png.
 */
class Logo extends Component
{
    /** Display height (CSS px) of each placement. */
    public const SIZES = [
        'header' => 72,
        'offcanvas' => 56,
        'sidebar' => 64,
        'footer' => 88,
        'login' => 104,
    ];

    /** Heights the command generates (each also at 2x). A placement uses the smallest one >= its height. */
    public const FILE_HEIGHTS = [56, 64, 72, 88, 104];

    /** Placements below the fold. */
    private const LAZY = ['footer'];

    public const GENERATED_DIR = 'assets/images/brand/generated';

    private static ?array $manifest = null;

    public string $src;

    public string $srcset;

    public int $width;

    public int $height;

    public bool $lazy;

    /**
     * The largest file of this placement: mask image of the gold glint (front.css "Motion").
     * Escaped for a single-quoted CSS string (Blade's {{ }} only escapes HTML, and the
     * browser decodes entities before CSS parsing).
     */
    public string $maskUrl;

    /**
     * @param  bool  $glint  wrap the image in .yq-glint, the signature light sweep (header/offcanvas only)
     */
    public function __construct(public string $size = 'header', public bool $glint = false)
    {
        if (! isset(self::SIZES[$size])) {
            $this->size = 'header';
        }

        $this->height = self::SIZES[$this->size];
        $this->lazy = in_array($this->size, self::LAZY, true);

        $base = self::fileHeightFor($this->height);
        $files = self::manifest()['files'] ?? [];
        $oneX = $files["logo-h{$base}.webp"] ?? null;
        $twoX = $files['logo-h'.($base * 2).'.webp'] ?? null;

        if ($oneX === null) {
            // Assets not generated yet: fall back to the source file at its own aspect ratio.
            $source = 'assets/images/brand/logo.png';
            [$sourceWidth, $sourceHeight] = @getimagesize(public_path($source)) ?: [1, 1];

            $this->src = versioned_asset($source);
            $this->srcset = '';
            $this->width = (int) round($sourceWidth * $this->height / $sourceHeight);
            $this->maskUrl = self::cssString($this->src);

            return;
        }

        $this->src = versioned_asset(self::GENERATED_DIR."/logo-h{$base}.webp");
        $twoXUrl = $twoX ? versioned_asset(self::GENERATED_DIR.'/logo-h'.($base * 2).'.webp') : null;
        $this->srcset = $this->src.' 1x'.($twoXUrl ? ", {$twoXUrl} 2x" : '');
        $this->maskUrl = self::cssString($twoXUrl ?? $this->src);
        $this->width = (int) round($oneX['width'] * $this->height / $oneX['height']);
    }

    /** Escape a value for use inside a single-quoted CSS string. */
    private static function cssString(string $value): string
    {
        return str_replace(["\r", "\n", '\\', "'"], ['', '', '\\\\', "\\'"], $value);
    }

    public static function fileHeightFor(int $displayHeight): int
    {
        foreach (self::FILE_HEIGHTS as $fileHeight) {
            if ($fileHeight >= $displayHeight) {
                return $fileHeight;
            }
        }

        return max(self::FILE_HEIGHTS);
    }

    private static function manifest(): array
    {
        if (self::$manifest === null) {
            $path = public_path(self::GENERATED_DIR.'/manifest.json');
            self::$manifest = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        }

        return self::$manifest;
    }

    public function render(): View
    {
        return view('components.logo');
    }
}
