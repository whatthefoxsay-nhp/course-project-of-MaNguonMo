<?php

namespace App\Support;

final class ZoneCanvas
{
    private const SHAPES = ['rectangle', 'rounded', 'ellipse', 'trapezoid', 'polygon'];

    /** @return array{x: float, y: float, width: float, height: float, shape: string, points: list<array{x: float, y: float}>} */
    public static function normalize(?array $meta, int $index, int $total): array
    {
        $saved = is_array($meta['canvas'] ?? null) ? $meta['canvas'] : [];
        $total = max(1, $total);
        $columns = max(1, min(10, (int) ceil(sqrt($total * 1.4))));
        $rows = (int) ceil($total / $columns);
        $horizontalGap = 1.2;
        $verticalGap = 1.5;
        $width = (90 - ($columns - 1) * $horizontalGap) / $columns;
        $height = max(5, min(19, (77 - ($rows - 1) * $verticalGap) / $rows));
        $column = $index % $columns;
        $row = intdiv($index, $columns);
        $defaults = [
            'x' => 5 + $column * ($width + $horizontalGap),
            'y' => 18 + $row * ($height + $verticalGap),
            'width' => $width,
            'height' => $height,
            'shape' => 'rounded',
            'points' => [],
        ];

        $canvas = array_merge($defaults, array_intersect_key($saved, $defaults));
        $x = max(0, min(95, (float) $canvas['x']));
        $y = max(0, min(95, (float) $canvas['y']));

        $points = is_array($canvas['points']) ? array_slice($canvas['points'], 0, 12) : [];
        $points = array_values(array_filter(array_map(fn ($point) => is_array($point) && isset($point['x'], $point['y']) ? [
            'x' => round(max(0, min(100, (float) $point['x'])), 2),
            'y' => round(max(0, min(100, (float) $point['y'])), 2),
        ] : null, $points)));
        if ($canvas['shape'] === 'polygon' && count($points) < 3) {
            $points = [['x' => 12.0, 'y' => 0.0], ['x' => 88.0, 'y' => 0.0], ['x' => 100.0, 'y' => 100.0], ['x' => 0.0, 'y' => 100.0]];
        }

        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round(max(5, min(100 - $x, (float) $canvas['width'])), 2),
            'height' => round(max(5, min(100 - $y, (float) $canvas['height'])), 2),
            'shape' => in_array($canvas['shape'], self::SHAPES, true) ? $canvas['shape'] : 'rounded',
            'points' => $points,
        ];
    }
}
