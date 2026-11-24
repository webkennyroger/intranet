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

test('the default site setting uses green as the primary color', function () {
    expect(SiteSetting::current()->primary_color)->toBe('#00C950');
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

test('a Super Administrador can update the primary color', function () {
    actingAsRoleForAppearance('Super Administrador');

    Livewire::test(AppearanceForm::class)
        ->set('primaryColor', '#ec4899')
        ->call('save')
        ->assertHasNoErrors();

    expect(SiteSetting::current()->primary_color)->toBe('#EC4899');
});

test('saving requires the appearance.update permission', function () {
    actingAsRoleForAppearance('Administrador');

    Livewire::test(AppearanceForm::class)
        ->set('primaryColor', '#EC4899')
        ->call('save')
        ->assertForbidden();

    expect(SiteSetting::current()->primary_color)->toBe('#00C950');
});

test('an invalid hex color is rejected', function () {
    actingAsRoleForAppearance('Super Administrador');

    Livewire::test(AppearanceForm::class)
        ->set('primaryColor', 'not-a-color')
        ->call('save')
        ->assertHasErrors(['primaryColor']);

    expect(SiteSetting::current()->primary_color)->toBe('#00C950');
});

test('white is not offered as an accent color option, since buttons/links in white would be invisible on white cards', function () {
    actingAsRoleForAppearance('Super Administrador');

    $html = $this->get(route('admin.appearance.edit'))->getContent();

    expect($html)->not->toContain('background-color: #FFFFFF;');
});

test('the page offers only the primary color, none of the removed chrome options', function () {
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.appearance.edit'))
        ->assertSee('Cor de destaque')
        ->assertDontSee('Canto da marca')
        ->assertDontSee('Barra superior')
        ->assertDontSee('Menu lateral')
        ->assertDontSee('Cor do texto');
});

test('the admin layout derives the sidebar active highlight from the saved primary color', function () {
    SiteSetting::current()->update(['primary_color' => '#2563EB']);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        ->assertSee('--admin-primary: #2563EB', false)
        ->assertSee('--admin-sidebar-active-bg: '.SiteSetting::current()->primaryLightColor(), false)
        ->assertSee('--admin-sidebar-active-text: '.SiteSetting::current()->primaryHoverColor(), false)
        ->assertDontSee('--admin-sidebar:', false)
        ->assertDontSee('--admin-header:', false);
});

test('the public site layout reflects the saved primary color', function () {
    SiteSetting::current()->update(['primary_color' => '#EC4899']);
    actingAsRoleForAppearance('Defensor');

    $this->get(route('inicio'))
        ->assertSee('--brand-primary: #EC4899', false);
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
