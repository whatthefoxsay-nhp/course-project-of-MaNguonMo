<?php

use App\Support\TicketTiers;

test('seated tiers price each zone from the base price', function () {
    $tiers = TicketTiers::for(200000, true);

    expect($tiers['svip_diamond']['price'])->toBe(350000)
        ->and($tiers['vip_gold']['price'])->toBe(280000)
        ->and($tiers['cat1_stand']['price'])->toBe(240000)
        ->and($tiers['cat2_wings']['price'])->toBe(200000)
        ->and($tiers['skybox_suite']['price'])->toBe(600000)
        ->and($tiers['standing_pit']['price'])->toBe(180000);
});

test('non seated events sell passes', function () {
    expect(array_keys(TicketTiers::for(200000, false)))->toBe(['vip_pass', 'standard_pass', 'combo_pass']);
});

test('legacy seat types map onto current zones', function () {
    expect(TicketTiers::normalizeSeatType('normal'))->toBe('cat2_wings')
        ->and(TicketTiers::normalizeSeatType('vip'))->toBe('vip_gold')
        ->and(TicketTiers::seatPrice('vip', 200000))->toBe(280000)
        ->and(TicketTiers::seatPrice('normal', 200000))->toBe(200000);
});

test('pass tiers are only valid for the matching event kind', function () {
    expect(TicketTiers::isPassTier('standing_pit', true))->toBeTrue()
        ->and(TicketTiers::isPassTier('standing_pit', false))->toBeFalse()
        ->and(TicketTiers::isPassTier('vip_pass', false))->toBeTrue()
        ->and(TicketTiers::isPassTier('vip_pass', true))->toBeFalse()
        ->and(TicketTiers::isPassTier('svip_diamond', true))->toBeFalse()
        ->and(TicketTiers::isPassTier('khong-ton-tai', false))->toBeFalse();
});

test('combo pass covers two seats at a shared price', function () {
    expect(TicketTiers::seatsPerUnit('combo_pass'))->toBe(2)
        ->and(TicketTiers::pricePerSeat('combo_pass', 200000, false))->toBe(170000)
        ->and(TicketTiers::passSeatTypes('combo_pass'))->toContain('cat1_stand');
});
