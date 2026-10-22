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

    expect($setting->primary_color)->toBe('#00C950')
        ->and($setting->nav_header_color)->toBe('#FFFFFF')
        ->and($setting->header_color)->toBe('#FFFFFF')
        ->and($setting->sidebar_color)->toBe('#FFFFFF')
        ->and($setting->text_color)->toBe('#0F172A');
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

test('an Administrador can update all five chrome colors independently', function () {
    actingAsRoleForAppearance('Super Administrador');

    Livewire::test(AppearanceForm::class)
        ->set('primaryColor', '#EC4899')
        ->set('navHeaderColor', '#2563EB')
        ->set('headerColor', '#F59E0B')
        ->set('sidebarColor', '#7C3AED')
        ->set('textColor', '#DC2626')
        ->call('save')
        ->assertHasNoErrors();

    $setting = SiteSetting::current();
    expect($setting->primary_color)->toBe('#EC4899')
        ->and($setting->nav_header_color)->toBe('#2563EB')
        ->and($setting->header_color)->toBe('#F59E0B')
        ->and($setting->sidebar_color)->toBe('#7C3AED')
        ->and($setting->text_color)->toBe('#DC2626');
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

    expect(SiteSetting::current()->primary_color)->toBe('#00C950');
});

test('the color picker never shows two checkmarks when the selected color matches both the row default and a palette swatch', function () {
    // "Branco" está na paleta compartilhada, mas "Menu lateral"/"Canto da
    // marca"/"Barra superior" já usam branco como padrão — sem filtrar a
    // paleta pelo default da própria linha, selecionar branco marcava o
    // botão "restaurar padrão" (hachurado) E o swatch branco da paleta ao
    // mesmo tempo, parecendo duas cores escolhidas.
    SiteSetting::current()->update(['sidebar_color' => '#FFFFFF']);
    actingAsRoleForAppearance('Super Administrador');

    $html = $this->get(route('admin.appearance.edit'))->getContent();
    $start = strpos($html, 'Menu lateral');
    $end = strpos($html, 'Cor do texto');
    $menuLateralRow = substr($html, $start, $end - $start);

    expect(substr_count($menuLateralRow, 'fa-solid fa-check'))->toBe(1);
});

test('white is not offered as an accent color option, since buttons/links in white would be invisible on white cards', function () {
    actingAsRoleForAppearance('Super Administrador');

    $html = $this->get(route('admin.appearance.edit'))->getContent();
    $start = strpos($html, 'Cor de destaque');
    $end = strpos($html, 'Canto da marca');
    $corDeDestaqueRow = substr($html, $start, $end - $start);

    expect($corDeDestaqueRow)->not->toContain('background-color: #FFFFFF;');
});

test('the color picker still shows exactly one checkmark when the selected color is a palette swatch distinct from the default', function () {
    SiteSetting::current()->update(['primary_color' => '#FB2C36']);
    actingAsRoleForAppearance('Super Administrador');

    $html = $this->get(route('admin.appearance.edit'))->getContent();
    $start = strpos($html, 'Cor de destaque');
    $end = strpos($html, 'Canto da marca');
    $corDeDestaqueRow = substr($html, $start, $end - $start);

    expect(substr_count($corDeDestaqueRow, 'fa-solid fa-check'))->toBe(1);
});

test('the admin layout reflects each saved chrome color independently', function () {
    SiteSetting::current()->update([
        'primary_color' => '#2563EB',
        'nav_header_color' => '#EC4899',
        'header_color' => '#F59E0B',
        'sidebar_color' => '#7C3AED',
        'text_color' => '#DC2626',
    ]);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        ->assertSee('--admin-primary: #2563EB', false)
        ->assertSee('--admin-nav-header: #EC4899', false)
        ->assertSee('--admin-header: #F59E0B', false)
        ->assertSee('--admin-sidebar: #7C3AED', false)
        ->assertSee('--text-primary: #DC2626', false);
});

test('the chosen text color only overrides light mode — dark mode always keeps light text regardless of the choice', function () {
    SiteSetting::current()->update(['text_color' => '#DC2626']);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        ->assertSee('--text-primary: #DC2626', false)
        ->assertSee('html.dark', false)
        ->assertSee('--text-primary: #f4f4f5', false);
});

test('the chosen text color directly sets chrome text (nav header, top bar, sidebar), matching the rest of the panel', function () {
    SiteSetting::current()->update([
        'nav_header_color' => '#FFFFFF',
        'header_color' => '#FFFFFF',
        'sidebar_color' => '#FFFFFF',
        'text_color' => '#DC2626',
    ]);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        ->assertSee('--admin-nav-header-text: #DC2626', false)
        ->assertSee('--admin-header-text: #DC2626', false)
        ->assertSee('--admin-sidebar-text: #DC2626', false);
});

test('the admin body no longer hardcodes a light-mode text color, so the main content area actually follows the chosen text color', function () {
    // layouts/admin.blade.php tinha "text-slate-800" fixo no <body> (só
    // virava "dark:text-(--text-primary)" no escuro) — qualquer elemento
    // do corpo do admin sem cor própria herdava esse cinza fixo, nunca a
    // "Cor do texto" escolhida. Mesma assinatura de bug já corrigida na
    // sidebar, mas no <body> inteiro — por isso "só muda header/sidebar".
    actingAsRoleForAppearance('Super Administrador');

    $html = $this->get(route('admin.users.index'))->getContent();
    $bodyTag = substr($html, strpos($html, '<body'), 300);

    expect($bodyTag)->not->toContain('text-slate-800')
        ->and($bodyTag)->toContain('color: var(--text-primary)');
});

test('the chosen text color applies to the sidebar even when the sidebar background is dark/saturated, not just when it is light', function () {
    // Antes desta correção, o texto do menu lateral era calculado por
    // contraste contra o fundo escolhido — num fundo escuro/saturado
    // (ex. rosa) isso sempre caía no branco fixo, ignorando por completo
    // a "Cor do texto" escolhida. Ela precisa valer sempre, não só
    // quando o fundo "permitir" por contraste.
    SiteSetting::current()->update([
        'sidebar_color' => '#EC4899',
        'text_color' => '#000000',
    ]);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        ->assertSee('--admin-sidebar-text: #000000', false);
});

test('a light sidebar still uses the light-sidebar active-highlight pair even with a custom text color', function () {
    SiteSetting::current()->update(['sidebar_color' => '#FFFFFF', 'text_color' => '#DC2626']);
    actingAsRoleForAppearance('Super Administrador');

    $this->get(route('admin.users.index'))
        // Antes desta correção, "sidebarIsLight" comparava com a string
        // fixa "#312a2a" — com "text_color" customizado, isso sempre dava
        // falso e o item ativo usava o par de sidebar ESCURA por engano.
        ->assertSee('--admin-sidebar-active-bg: '.SiteSetting::current()->primaryLightColor(), false)
        ->assertSee('--admin-sidebar-active-text: '.SiteSetting::current()->primaryHoverColor(), false);
});

test('the public site layout reflects the saved primary color', function () {
    SiteSetting::current()->update(['primary_color' => '#EC4899']);
    actingAsRoleForAppearance('Defensor');

    $this->get(route('inicio'))
        ->assertSee('--brand-primary: #EC4899', false);
});

test('the sidebar active item keeps readable contrast when the sidebar color is as dark as the primary color', function () {
    SiteSetting::current()->update(['primary_color' => '#0C4A34', 'sidebar_color' => '#0C4A34']);
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
