<?php

use App\Livewire\Admin\Users\UserForm;
use App\Models\BirthdayBanner;
use App\Models\User;
use Database\Seeders\AclSeeder;

test('the birthdays page shows an empty state when no banner is published for the current month', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('birthdays.index'))
        ->assertOk()
        ->assertSee('Nenhum banner cadastrado para este mês.');
});

test('a user can have a birth date set via the admin user form', function () {
    $this->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');

    Livewire\Livewire::actingAs($admin)->test(UserForm::class)
        ->set('name', 'Novo Usuário')
        ->set('username', 'novo.usuario')
        ->set('email', 'novo@exemplo.com')
        ->set('password', 'password123')
        ->set('birthDate', '1990-05-15')
        ->call('save')
        ->assertHasNoErrors();

    $created = User::where('email', 'novo@exemplo.com')->first();
    expect($created->birth_date->format('Y-m-d'))->toBe('1990-05-15');
});

test('the birthdays page shows the current month\'s published banner image', function () {
    $user = User::factory()->create();
    $banner = BirthdayBanner::factory()->create(['month' => now()->month, 'is_published' => true]);
    $otherMonthBanner = BirthdayBanner::factory()->create(['month' => now()->subMonths(2)->month === now()->month ? now()->addMonths(2)->month : now()->subMonths(2)->month]);

    $this->actingAs($user)->get(route('birthdays.index'))
        ->assertOk()
        ->assertSee($banner->imageUrl(), false)
        ->assertDontSee($otherMonthBanner->imageUrl(), false);
});

test('the birthdays page does not show an unpublished banner for the current month', function () {
    $user = User::factory()->create();
    $banner = BirthdayBanner::factory()->create(['month' => now()->month, 'is_published' => false]);

    $this->actingAs($user)->get(route('birthdays.index'))
        ->assertOk()
        ->assertDontSee($banner->imageUrl(), false);
});
