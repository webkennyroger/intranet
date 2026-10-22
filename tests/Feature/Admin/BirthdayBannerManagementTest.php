<?php

use App\Livewire\Admin\Birthdays\BirthdayBannerForm;
use App\Livewire\Admin\Birthdays\BirthdayBannerIndex;
use App\Models\BirthdayBanner;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function actingAsRoleForBirthdayBanners(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('the management page always lists all 12 months, even with no banners cadastrados', function () {
    actingAsRoleForBirthdayBanners('Administrador');

    Livewire::test(BirthdayBannerIndex::class)
        ->assertSee('Janeiro')
        ->assertSee('Dezembro')
        ->assertSeeHtml('Sem banner');
});

test('createForMonth prefills the form with that month without creating anything', function () {
    actingAsRoleForBirthdayBanners('Administrador');

    $component = Livewire::test(BirthdayBannerIndex::class)->call('createForMonth', 7);

    expect($component->get('prefillMonth'))->toBe(7)
        ->and($component->get('editingBannerId'))->toBeNull();

    Livewire::test(BirthdayBannerForm::class, ['prefillMonth' => 7])
        ->assertSet('month', '7');

    expect(BirthdayBanner::count())->toBe(0);
});

test('an Administrador can view the birthday banners management page', function () {
    actingAsRoleForBirthdayBanners('Administrador');

    $this->get(route('admin.birthday-banners.index'))->assertStatus(200);
});

test('creating a birthday banner stores the month and the uploaded image', function () {
    Storage::fake('public');
    actingAsRoleForBirthdayBanners('Administrador');
    $image = UploadedFile::fake()->image('outubro.jpg');

    Livewire::test(BirthdayBannerForm::class)
        ->set('month', '10')
        ->set('image', $image)
        ->call('save')
        ->assertHasNoErrors();

    $banner = BirthdayBanner::where('month', 10)->first();
    expect($banner)->not->toBeNull()
        ->and($banner->is_published)->toBeTrue();
    Storage::disk('public')->assertExists($banner->image_path);
});

test('creating a birthday banner without an image is rejected', function () {
    actingAsRoleForBirthdayBanners('Administrador');

    Livewire::test(BirthdayBannerForm::class)
        ->set('month', '5')
        ->call('save')
        ->assertHasErrors(['image']);

    expect(BirthdayBanner::where('month', 5)->exists())->toBeFalse();
});

test('creating a birthday banner with a month outside 1-12 is rejected', function () {
    Storage::fake('public');
    actingAsRoleForBirthdayBanners('Administrador');

    Livewire::test(BirthdayBannerForm::class)
        ->set('month', '13')
        ->set('image', UploadedFile::fake()->image('invalido.jpg'))
        ->call('save')
        ->assertHasErrors(['month']);

    expect(BirthdayBanner::count())->toBe(0);
});

test('creating a second birthday banner for the same month is rejected', function () {
    Storage::fake('public');
    actingAsRoleForBirthdayBanners('Administrador');
    BirthdayBanner::factory()->create(['month' => 10]);

    Livewire::test(BirthdayBannerForm::class)
        ->set('month', '10')
        ->set('image', UploadedFile::fake()->image('duplicado.jpg'))
        ->call('save')
        ->assertHasErrors(['month']);

    expect(BirthdayBanner::where('month', 10)->count())->toBe(1);
});

test('editing a birthday banner without a new image keeps the existing image', function () {
    actingAsRoleForBirthdayBanners('Administrador');
    $banner = BirthdayBanner::factory()->create(['month' => 3, 'image_path' => 'birthdays/original.jpg']);

    Livewire::test(BirthdayBannerForm::class, ['banner' => $banner])
        ->set('month', '4')
        ->call('save')
        ->assertHasNoErrors();

    expect($banner->refresh()->month)->toBe(4)
        ->and($banner->image_path)->toBe('birthdays/original.jpg');
});

test('editing a birthday banner can replace its image', function () {
    Storage::fake('public');
    actingAsRoleForBirthdayBanners('Administrador');
    $banner = BirthdayBanner::factory()->create(['month' => 3, 'image_path' => 'birthdays/original.jpg']);
    $newImage = UploadedFile::fake()->image('novo.jpg');

    Livewire::test(BirthdayBannerForm::class, ['banner' => $banner])
        ->set('image', $newImage)
        ->call('save')
        ->assertHasNoErrors();

    expect($banner->refresh()->image_path)->not->toBe('birthdays/original.jpg');
    Storage::disk('public')->assertExists($banner->image_path);
});

test('an Administrador can publish and unpublish a birthday banner', function () {
    actingAsRoleForBirthdayBanners('Administrador');
    $banner = BirthdayBanner::factory()->create(['is_published' => false]);

    Livewire::test(BirthdayBannerIndex::class)->call('togglePublish', $banner->id);
    expect($banner->refresh()->is_published)->toBeTrue();

    Livewire::test(BirthdayBannerIndex::class)->call('togglePublish', $banner->id);
    expect($banner->refresh()->is_published)->toBeFalse();
});

test('a Defensor cannot publish a birthday banner', function () {
    actingAsRoleForBirthdayBanners('Defensor');
    $banner = BirthdayBanner::factory()->create(['is_published' => false]);

    Livewire::test(BirthdayBannerIndex::class)->call('togglePublish', $banner->id);

    expect($banner->refresh()->is_published)->toBeFalse();
});

test('an Administrador can delete a birthday banner', function () {
    actingAsRoleForBirthdayBanners('Administrador');
    $banner = BirthdayBanner::factory()->create();

    Livewire::test(BirthdayBannerIndex::class)->call('delete', $banner->id);

    expect(BirthdayBanner::find($banner->id))->toBeNull();
});

test('a Defensor cannot delete a birthday banner', function () {
    actingAsRoleForBirthdayBanners('Defensor');
    $banner = BirthdayBanner::factory()->create();

    Livewire::test(BirthdayBannerIndex::class)->call('delete', $banner->id);

    expect(BirthdayBanner::find($banner->id))->not->toBeNull();
});
