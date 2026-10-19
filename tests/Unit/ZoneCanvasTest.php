<?php

use App\Support\ZoneCanvas;

test('zone canvas keeps custom terrain position size and shape within the map', function () {
    expect(ZoneCanvas::normalize([
        'canvas' => ['x' => 32.5, 'y' => 41, 'width' => 27, 'height' => 18, 'shape' => 'trapezoid'],
    ], 0, 1))->toBe([
        'x' => 32.5,
        'y' => 41.0,
        'width' => 27.0,
        'height' => 18.0,
        'shape' => 'trapezoid',
        'points' => [],
    ]);
});

test('zone canvas safely normalizes invalid shapes and off-map coordinates', function () {
    $canvas = ZoneCanvas::normalize([
        'canvas' => ['x' => 120, 'y' => -4, 'width' => 40, 'height' => 40, 'shape' => 'freeform-script'],
    ], 0, 1);

    expect($canvas['x'])->toBe(95.0)
        ->and($canvas['y'])->toBe(0.0)
        ->and($canvas['width'])->toBe(5.0)
        ->and($canvas['shape'])->toBe('rounded');
});

test('zone canvas persists and clamps custom polygon vertices', function () {
    $canvas = ZoneCanvas::normalize([
        'canvas' => [
            'shape' => 'polygon',
            'points' => [['x' => -10, 'y' => 25], ['x' => 55, 'y' => 10], ['x' => 120, 'y' => 110]],
        ],
    ], 0, 1);

    expect($canvas['shape'])->toBe('polygon')
        ->and($canvas['points'])->toBe([
            ['x' => 0.0, 'y' => 25.0],
            ['x' => 55.0, 'y' => 10.0],
            ['x' => 100.0, 'y' => 100.0],
        ]);
});
