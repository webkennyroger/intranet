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

test('an achromatic default color (white) falls back to the app\'s standard dark surface tokens, not a computed gray or a reddish tint', function () {
    expect(SiteSetting::darkBgFor('#FFFFFF'))->toBe('#0c221a')
        ->and(SiteSetting::darkTextFor('#FFFFFF'))->toBe('#e6f2ec')
        ->and(SiteSetting::darkBodyBgFor('#FFFFFF'))->toBe('#081712');
});
