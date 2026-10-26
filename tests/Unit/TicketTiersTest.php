<?php

use App\Support\TicketTiers;

test('seated tiers price each zone from the base price', function () {
    $tiers = TicketTiers::for(200000);

    expect($tiers['svip_diamond']['price'])->toBe(350000)
        ->and($tiers['vip_gold']['price'])->toBe(280000)
        ->and($tiers['cat1_stand']['price'])->toBe(240000)
        ->and($tiers['cat2_wings']['price'])->toBe(200000)
        ->and($tiers['skybox_suite']['price'])->toBe(600000)
        ->and($tiers['standing_pit']['price'])->toBe(180000);
});

test('legacy seat types map onto current zones', function () {
    expect(TicketTiers::normalizeSeatType('normal'))->toBe('cat2_wings')
        ->and(TicketTiers::normalizeSeatType('vip'))->toBe('vip_gold')
        ->and(TicketTiers::seatPrice('vip', 200000))->toBe(280000)
        ->and(TicketTiers::seatPrice('normal', 200000))->toBe(200000);
});
