<?php

use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\User;
use Database\Seeders\AclSeeder;

/**
 * Regressão real do bug: o botão "Editar" fica dentro de um @island (a
 * tabela), então clicar nele só re-renderiza aquele island — um teste via
 * Livewire::test() não reproduz essa diferença (sempre renderiza tudo),
 * só um clique de verdade no navegador prova que o modal, agora movido
 * pra dentro do island, recebe o álbum certo.
 */
test('clicking Editar loads that album\'s title and photos into the modal, instead of staying empty/stale', function () {
    $this->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    $this->actingAs($admin);

    // image_path real (não o fake aleatório da factory, que 404 quando o
    // navegador carrega o <img src> de verdade e derruba o teste).
    $realImagePath = 'articles/body/W3XFdT5qLvU689MSmFQIwhMU4aJH32Og8tPcMF9Y.png';

    $first = PhotoAlbum::factory()->create(['title' => 'Primeiro Álbum']);
    Photo::factory()->for($first, 'album')->create(['image_path' => $realImagePath]);

    // Confere o VALOR do campo "Título do álbum" dentro do modal — checar
    // texto solto na página (assertSee) não funciona aqui: a tabela de
    // álbuns continua atrás do modal (só escurecida, não escondida), então
    // os dois títulos ficam "visíveis" o tempo todo de qualquer forma.
    $page = visit('/admin/fotos');

    // Espera o round-trip do Livewire (clique -> servidor -> DOM atualizado)
    // terminar antes de ler o valor do campo — value() lê o DOM na hora,
    // sem esperar/repetir como assertSee() faz.
    $page->click('[wire\\:click="edit('.$first->id.')"]')->wait(1);
    expect($page->value('[wire\\:model="title"]'))->toBe('Primeiro Álbum');
    $page->assertNoJavascriptErrors();
});

test('clicking Ver on an album opens the gallery modal with that album\'s photos', function () {
    $this->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    $this->actingAs($admin);

    $album = PhotoAlbum::factory()->create(['title' => 'Álbum com Fotos']);
    Photo::factory()->for($album, 'album')->create(['caption' => 'Foto da Galeria']);

    $page = visit('/admin/fotos');

    $page->click('[wire\\:click="view('.$album->id.')"]');
    $page->assertSee('Álbum com Fotos');
    $page->assertNoJavascriptErrors();
});
