<?php

namespace Modules\Analytics\Support;

use Illuminate\Support\Carbon;

/**
 * Geometry of the dashboard's two-series line chart, computed on the server so the Blade
 * view only prints it (inline SVG, no JavaScript, no library). One shared y axis from 0.
 */
final class LineChart
{
    public const WIDTH = 520;

    public const HEIGHT = 240;

    private const LEFT = 40;

    private const RIGHT = 14;

    private const TOP = 14;

    private const BOTTOM = 30;

    /** Number of y-axis intervals (so ticks sit at 0, 1/4, 1/2, 3/4 and the top). */
    private const INTERVALS = 4;

    /**
     * @param  array<int, array{date: string, views: int, whatsapp_clicks: int}>  $days  oldest first
     * @return array{width: int, height: int, max: int, yTicks: array<int, array{value: int, y: float, x1: float, x2: float}>, xTicks: array<int, array{x: float, date: string}>, series: array<string, array{points: string, count: int}>, plotLeft: float, plotRight: float, plotBottom: float}
     */
    public static function build(array $days): array
    {
        $count = count($days);
        $plotLeft = (float) self::LEFT;
        $plotRight = (float) (self::WIDTH - self::RIGHT);
        $plotTop = (float) self::TOP;
        $plotBottom = (float) (self::HEIGHT - self::BOTTOM);

        $highest = 0;
        foreach ($days as $day) {
            $highest = max($highest, $day['views'], $day['whatsapp_clicks']);
        }

        $step = self::niceStep($highest);
        $max = $step * self::INTERVALS;

        $x = fn (int $index): float => $count > 1
            ? round($plotLeft + ($plotRight - $plotLeft) * $index / ($count - 1), 2)
            : round(($plotLeft + $plotRight) / 2, 2);
        $y = fn (int $value): float => round($plotBottom - ($plotBottom - $plotTop) * $value / $max, 2);

        $yTicks = [];
        for ($i = 0; $i <= self::INTERVALS; $i++) {
            $yTicks[] = ['value' => $i * $step, 'y' => $y($i * $step), 'x1' => $plotLeft, 'x2' => $plotRight];
        }

        // About five date labels: first, last and evenly spaced ones between.
        $xTicks = [];
        if ($count > 0) {
            $labelStep = max(1, (int) round(($count - 1) / 4));
            for ($i = $count - 1; $i >= 0; $i -= $labelStep) {
                $xTicks[] = ['x' => $x($i), 'date' => $days[$i]['date']];
            }
            $xTicks = array_reverse($xTicks);
        }

        $series = [];
        foreach (['views', 'whatsapp_clicks'] as $key) {
            $points = [];
            foreach ($days as $index => $day) {
                $points[] = $x($index).','.$y($day[$key]);
            }
            $series[$key] = ['points' => implode(' ', $points), 'count' => $count];
        }

        return [
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'max' => $max,
            'yTicks' => $yTicks,
            'xTicks' => $xTicks,
            'series' => $series,
            'plotLeft' => $plotLeft,
            'plotRight' => $plotRight,
            'plotBottom' => $plotBottom,
        ];
    }

    /**
     * The smallest "nice" step (1, 2, 5, 10, 20, 50, ...) whose four intervals reach $highest.
     */
    private static function niceStep(int $highest): int
    {
        $magnitude = 1;

        while (true) {
            foreach ([1, 2, 5] as $multiplier) {
                $step = $multiplier * $magnitude;

                if ($step * self::INTERVALS >= $highest) {
                    return $step;
                }
            }

            $magnitude *= 10;
        }
    }

    /**
     * Label for an axis date ("12 Nov"), in the current locale.
     */
    public static function label(string $date): string
    {
        return Carbon::parse($date)->translatedFormat('j M');
    }
}
