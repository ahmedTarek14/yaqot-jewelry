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
        'header' => 56,
        'offcanvas' => 48,
        'sidebar' => 64,
        'footer' => 88,
        'login' => 104,
    ];

    /** Heights the command generates (each also at 2x). A placement uses the smallest one >= its height. */
    public const FILE_HEIGHTS = [56, 64, 88, 104];

    /** Placements below the fold. */
    private const LAZY = ['footer'];

    public const GENERATED_DIR = 'assets/images/brand/generated';

    private static ?array $manifest = null;

    public string $src;

    public string $srcset;

    public int $width;

    public int $height;

    public bool $lazy;

    public function __construct(public string $size = 'header')
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

            return;
        }

        $this->src = versioned_asset(self::GENERATED_DIR."/logo-h{$base}.webp");
        $this->srcset = $this->src.' 1x'
            .($twoX ? ', '.versioned_asset(self::GENERATED_DIR.'/logo-h'.($base * 2).'.webp').' 2x' : '');
        $this->width = (int) round($oneX['width'] * $this->height / $oneX['height']);
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
