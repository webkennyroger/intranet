<?php

use App\Livewire\Admin\Users\UserForm;
use App\Models\User;
use Database\Seeders\AclSeeder;

test('the birthdays page only shows users born in the current month, ordered by day', function () {
    $user = User::factory()->create();
    $laterInMonth = User::factory()->create(['name' => 'Aniversariante Dia 25', 'birth_date' => now()->setDay(25)]);
    $earlierInMonth = User::factory()->create(['name' => 'Aniversariante Dia 05', 'birth_date' => now()->setDay(5)]);
    $otherMonth = User::factory()->create(['name' => 'Aniversariante Outro Mês', 'birth_date' => now()->subMonths(2)]);
    $noBirthDate = User::factory()->create(['name' => 'Sem Data', 'birth_date' => null]);

    $response = $this->actingAs($user)->get(route('birthdays.index'))
        ->assertOk()
        ->assertSee('Aniversariante Dia 25')
        ->assertSee('Aniversariante Dia 05')
        ->assertDontSee('Aniversariante Outro Mês')
        ->assertDontSee('Sem Data');

    $content = $response->getContent();
    expect(strpos($content, 'Aniversariante Dia 05'))->toBeLessThan(strpos($content, 'Aniversariante Dia 25'));
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
