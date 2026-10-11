<?php

use App\Support\VenuePresets;

test('legacy room presets resolve to the new venue purpose presets', function () {
    expect(VenuePresets::normalize('mega_concert'))->toBe('outdoor_concert')
        ->and(VenuePresets::normalize('sayhi_concert_d2'))->toBe('outdoor_concert')
        ->and(VenuePresets::normalize('theater_hall'))->toBe('theater')
        ->and(VenuePresets::normalize('convention_center'))->toBe('fixed_hall');
});

test('venue presets initialize distinct seat and quantity based zones', function () {
    expect(collect(VenuePresets::zones('outdoor_concert'))->contains(fn ($zone) => $zone['kind'] === 'standing'))->toBeTrue()
        ->and(collect(VenuePresets::zones('theater'))->contains(fn ($zone) => $zone['kind'] === 'seated'))->toBeTrue()
        ->and(collect(VenuePresets::zones('conference_tables'))->contains(fn ($zone) => $zone['kind'] === 'table'))->toBeTrue()
        ->and(collect(VenuePresets::zones('admission_only'))->contains(fn ($zone) => $zone['kind'] === 'admission'))->toBeTrue();
});
