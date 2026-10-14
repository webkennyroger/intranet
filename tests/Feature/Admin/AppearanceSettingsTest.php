<?php

use App\Livewire\Admin\Appearance\AppearanceForm;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsRoleForAppearance(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('the default site setting uses green only for the primary color, white for the chrome', function () {
    $setting = SiteSetting::current();

    expect($setting->primary_color)->toBe('#0C4A34')
        ->and($setting->nav_header_color)->toBe('#FFFFFF')
        ->and($setting->header_color)->toBe('#FFFFFF')
        ->and($setting->sidebar_color)->toBe('#FFFFFF');
});

test('a Super Administrador can view the appearance settings page', function () {
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.appearance.edit'))->assertStatus(200);
});

test('an Administrador cannot view the appearance settings page', function () {
    actingAsRoleForAppearance('Administrador');

    $this->get(route('admin.appearance.edit'))->assertStatus(403);
});

test('a Defensor cannot view the appearance settings page', function () {
    actingAsRoleForAppearance('Defensor');

    $this->get(route('admin.appearance.edit'))->assertStatus(403);
});

test('an Administrador can update all four chrome colors independently', function () {
    actingAsRoleForAppearance('Super Administrador');

    Livewire::test(AppearanceForm::class)
        ->set('primaryColor', '#EC4899')
        ->set('navHeaderColor', '#2563EB')
        ->set('headerColor', '#F59E0B')
        ->set('sidebarColor', '#7C3AED')
        ->call('save')
        ->assertHasNoErrors();

    $setting = SiteSetting::current();
    expect($setting->primary_color)->toBe('#EC4899')
        ->and($setting->nav_header_color)->toBe('#2563EB')
        ->and($setting->header_color)->toBe('#F59E0B')
        ->and($setting->sidebar_color)->toBe('#7C3AED');
});

test('clicking a palette swatch saves that field immediately, with no separate save step', function () {
    actingAsRoleForAppearance('Super Administrador');

    Livewire::test(AppearanceForm::class)
        ->call('pick', 'sidebarColor', '#7C3AED');

    expect(SiteSetting::current()->sidebar_color)->toBe('#7C3AED');
});

test('an invalid hex color is rejected', function () {
    actingAsRoleForAppearance('Super Administrador');

    Livewire::test(AppearanceForm::class)
        ->set('primaryColor', 'not-a-color')
        ->call('save')
        ->assertHasErrors(['primaryColor']);

    expect(SiteSetting::current()->primary_color)->toBe('#0C4A34');
});

test('the admin layout reflects each saved chrome color independently', function () {
    SiteSetting::current()->update([
        'primary_color' => '#2563EB',
        'nav_header_color' => '#EC4899',
        'header_color' => '#F59E0B',
        'sidebar_color' => '#7C3AED',
    ]);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        ->assertSee('--admin-primary: #2563EB', false)
        ->assertSee('--admin-nav-header: #EC4899', false)
        ->assertSee('--admin-header: #F59E0B', false)
        ->assertSee('--admin-sidebar: #7C3AED', false);
});

test('the public site layout reflects the saved primary color', function () {
    SiteSetting::current()->update(['primary_color' => '#EC4899']);
    actingAsRoleForAppearance('Defensor');

    $this->get(route('inicio'))
        ->assertSee('--brand-primary: #EC4899', false);
});

test('the sidebar active item keeps readable contrast when the sidebar color is as dark as the primary color', function () {
    SiteSetting::current()->update(['sidebar_color' => '#0C4A34']);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        // --admin-sidebar is #0C4A34 (light mode) / #17362b (.dark block) —
        // the active highlight must differ from both, in both blocks, or it
        // disappears into the sidebar's own background.
        ->assertSee('--admin-sidebar-active-bg: #275d4a', false)
        ->assertSee('--admin-sidebar-active-text: #51d2a4', false)
        ->assertDontSee('--admin-sidebar-active-bg: #17362b', false)
        ->assertDontSee('--admin-sidebar-active-text: #093b29', false);
});

test('readable text color flips between dark and light based on background brightness', function () {
    expect(SiteSetting::readableTextFor('#FFFFFF'))->toBe('#312a2a')
        ->and(SiteSetting::readableTextFor('#000000'))->toBe('#f4f4f5')
        ->and(SiteSetting::readableTextFor('#0C4A34'))->toBe('#f4f4f5');
});

test('hero gradient stops get progressively darker and follow the primary color', function () {
    $setting = SiteSetting::current();
    $setting->update(['primary_color' => '#22B573']);

    [$stop0, $stop35, $stop65, $stop100] = $setting->heroGradientStops();

    expect($stop0)->toBe('#22B573')
        ->and($stop100)->toBe($setting->primaryHoverColor())
        ->and(hexBrightness($stop0))->toBeGreaterThan(hexBrightness($stop35))
        ->and(hexBrightness($stop35))->toBeGreaterThan(hexBrightness($stop65))
        ->and(hexBrightness($stop65))->toBeGreaterThan(hexBrightness($stop100));
});

function hexBrightness(string $hex): int
{
    [$red, $green, $blue] = sscanf($hex, '#%02x%02x%02x');

    return $red + $green + $blue;
}
