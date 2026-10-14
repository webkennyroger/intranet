<?php

use App\Models\SiteSetting;

test('the dark-mode active color matches the ESDEP reference green closely', function () {
    $setting = new SiteSetting(['primary_color' => '#0C4A34']);

    expect($setting->darkActiveBgColor())->toBe('#17362b')
        ->and($setting->darkActiveTextColor())->toBe('#51d2a4');
});

test('a different chosen hue produces a different dark-mode active color, not a fixed green', function () {
    $blue = new SiteSetting(['primary_color' => '#2563EB']);
    $pink = new SiteSetting(['primary_color' => '#EC4899']);

    expect($blue->darkActiveBgColor())->not->toBe((new SiteSetting(['primary_color' => '#0C4A34']))->darkActiveBgColor())
        ->and($pink->darkActiveBgColor())->not->toBe($blue->darkActiveBgColor())
        ->and($blue->darkActiveTextColor())->toBe('#5179d2')
        ->and($pink->darkActiveTextColor())->toBe('#d25191');
});

test('dark surface tokens are neutral regardless of the chosen hue', function () {
    expect(SiteSetting::darkBgFor('#FFFFFF'))->toBe('#18181b')
        ->and(SiteSetting::darkBgFor('#0C4A34'))->toBe('#18181b')
        ->and(SiteSetting::darkBodyBgFor('#FFFFFF'))->toBe('#0e0e11')
        ->and(SiteSetting::darkBodyBgFor('#0C4A34'))->toBe('#0e0e11');
});

test('an achromatic default color (white) falls back to the app\'s standard light text token for dark-mode accents', function () {
    expect(SiteSetting::darkTextFor('#FFFFFF'))->toBe('#f4f4f5');
});
