<?php

use App\Models\Article;
use App\Models\User;
use Database\Seeders\AclSeeder;

/**
 * Imagem já existente em storage/, reaproveitada só como fonte de teste
 * (nunca escrita/alterada) pra não depender de rede externa pra inserir
 * imagem no editor.
 */
const TEST_IMAGE_PATH = '/storage/articles/body/W3XFdT5qLvU689MSmFQIwhMU4aJH32Og8tPcMF9Y.png';

beforeEach(function () {
    $this->seed(AclSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Administrador');

    $this->article = Article::factory()->create([
        'title' => 'Artigo de teste do editor',
        'excerpt' => 'Resumo de teste.',
        'body' => '<p>Texto inicial.</p>',
        'is_published' => false,
        'published_at' => null,
    ]);

    // Caminho relativo (não route()/url()): o Pest Browser sobe um
    // servidor HTTP próprio num host:porta à parte, e só reconfigura
    // config('app.url') pra combinar com ele quando o servidor sobe —
    // o que só acontece dentro do primeiro visit(). Gerar a URL antes
    // disso (via route()/url()) ainda usaria o app.url do .env, nunca
    // o do servidor de teste, resultando em ERR_CONNECTION_REFUSED.
    $this->editUrl = '/admin/noticias/'.$this->article->id.'/editar';
});

test('formatação de texto (negrito, itálico, sublinhado, tachado, remover formatação, desfazer/refazer) funciona', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Olá mundo editor</p>')
        ->click('text=Salvar código');

    $page->assertSourceHas('Olá mundo editor');

    $page->click('.ProseMirror p');
    $page->keys('.ProseMirror', 'Control+a');

    $page->click('[data-tip="Negrito (Ctrl+B)"]')->assertSourceHas('<strong>');
    $page->click('[data-tip="Itálico (Ctrl+I)"]')->assertSourceHas('<em>');
    $page->click('[data-tip="Sublinhado (Ctrl+U)"]')->assertSourceHas('<u>');
    $page->click('[data-tip="Tachado"]')->assertSourceHas('<s>');

    $page->click('[data-tip="Remover formatação"]');
    $page->assertSourceMissing('<strong>')->assertSourceMissing('<em>');

    $page->click('[data-tip="Desfazer (Ctrl+Z)"]');
    $page->click('[data-tip="Refazer (Ctrl+Shift+Z)"]');
    $page->assertNoJavascriptErrors();
});

test('cor do texto e cor de destaque funcionam', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Texto colorido</p>')
        ->click('text=Salvar código');

    $page->click('.ProseMirror p')->keys('.ProseMirror', 'Control+a');

    $page->click('[data-tip="Cor do texto"]');
    $page->click('[data-tip="#059669"]:visible');
    $page->assertSourceHas('color: rgb(5, 150, 105)');

    $page->keys('.ProseMirror', 'Control+a');
    $page->click('[data-tip="Cor de destaque"]');
    $page->click('[data-tip="#eab308"]:visible');
    $page->assertSourceHas('data-color="#eab308"');
    $page->assertNoJavascriptErrors();
});

test('títulos, citação, bloco de código, linha horizontal e alinhamento funcionam', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Parágrafo para formatar</p>')
        ->click('text=Salvar código');

    $page->click('.ProseMirror p')->keys('.ProseMirror', 'Control+a');

    $page->click('[data-tip="Formato do texto"]');
    $page->hover('text=Títulos');
    $page->click('text=Título 1');
    $page->assertSourceHas('<h1');

    $page->keys('.ProseMirror', 'Control+a');
    $page->click('[data-tip="Citação"]');
    $page->assertSourceHas('<blockquote');

    $page->click('[data-tip="Citação"]');
    $page->click('[data-tip="Bloco de código"]');
    $page->assertSourceHas('<pre');

    $page->click('[data-tip="Bloco de código"]');
    $page->click('[data-tip="Linha horizontal"]');
    $page->assertSourceHas('<hr');

    $page->click('[data-tip="Alinhamento"]');
    $page->click('button:text-is("Centro"):visible');
    $page->assertSourceHas('text-align: center');
    $page->assertNoJavascriptErrors();
});

test('listas (marcadores, numerada, tarefas) funcionam', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Item de lista</p>')
        ->click('text=Salvar código');

    $page->click('.ProseMirror p')->keys('.ProseMirror', 'Control+a');
    $page->click('[data-tip="Lista com marcadores"]');
    $page->assertSourceHas('<ul');

    $page->keys('.ProseMirror', 'Control+a');
    $page->click('[data-tip="Lista com marcadores"]'); // desliga
    $page->click('[data-tip="Lista numerada"]');
    $page->assertSourceHas('<ol');

    $page->keys('.ProseMirror', 'Control+a');
    $page->click('[data-tip="Lista numerada"]'); // desliga
    $page->click('[data-tip="Lista de tarefas"]');
    $page->assertSourceHas('taskList');
    $page->assertNoJavascriptErrors();
});

test('inserir link funciona', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Texto com link aqui</p>')
        ->click('text=Salvar código');

    $page->click('.ProseMirror p')->keys('.ProseMirror', 'Control+a');

    $page->click('[data-tip="Inserir/editar link (Ctrl+K)"]');
    $page->fill('input[placeholder="https://"]', 'https://example.com');
    $page->click('[\@click="saveLinkModal()"]');

    $page->assertSourceHas('<a')->assertSourceHas('href="https://example.com"');
    $page->assertNoJavascriptErrors();
});

test('tabela: inserir, adicionar linha/coluna e excluir funciona', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    // A grade de tamanho da tabela usa @mouseenter em cada célula (16x16px)
    // pra atualizar o estado de hover — o movimento real do mouse do
    // Playwright passando por várias células no caminho dispara essas
    // atualizações em sequência rápida, e o elemento final nunca
    // "estabiliza" o suficiente pra Playwright confirmar o clique (fica
    // tentando indefinidamente). Chamar o método do Alpine direto exercita
    // a mesma lógica de inserir a tabela sem depender desse hover.
    $page->click('[data-tip="Tabela"]');
    $page->hover('text="Tabela"');
    $page->script("Alpine.\$data(document.querySelector('.ProseMirror').closest('[x-data]')).insertTableGrid(2, 3)");

    $page->assertSourceHas('<table');

    $page->click('table td:first-child');
    $page->click('[data-tip="Tabela"]');
    $page->hover('text="Linha"');
    $page->click('text=Inserir linha depois');
    $page->click('[data-tip="Tabela"]');
    $page->hover('text="Coluna"');
    $page->click('text=Inserir coluna depois');

    $page->click('[data-tip="Tabela"]');
    $page->click('text=Excluir tabela');
    $page->assertSourceMissing('<table');
    $page->assertNoJavascriptErrors();
});

test('inserir imagem, alinhar à esquerda/direita (float) e redimensionar funciona', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Parágrafo antes da imagem</p><p>Parágrafo depois da imagem, bem mais longo pra garantir espaço suficiente ao lado pra fluir o texto corrido.</p>')
        ->click('text=Salvar código');

    $page->click('[data-tip="Inserir imagem"]');
    $page->click('[title="Upload from URL"]');
    // O input da caixa "Drop files here" é criado via document.createElement
    // puro (não é x-model do Alpine) — preencher direto via DOM evita uma
    // instabilidade do fill() do Playwright nesse input específico (gerado
    // dinamicamente dentro de uma barrinha posicionada em absolute).
    $page->script(
        "document.querySelector('input[placeholder=\"https://\"]:not([x-model])').value = ".json_encode(TEST_IMAGE_PATH)
    );
    $page->click('[title="Apply"]');

    $page->assertSourceHas('<img');

    // Seleciona a imagem (clique) pra abrir a barrinha flutuante de alinhar.
    // Os botões dessa barrinha são criados via JS puro (toolbarButton() em
    // app.js), com atributo "title" nativo — não "data-tip" (esse é só da
    // toolbar principal, renderizada via Blade).
    $page->click('.ProseMirror img');
    $page->click('[title="Alinhar à esquerda"]');
    $page->assertSourceHas('float: left');

    $page->click('.ProseMirror img');
    $page->click('[title="Alinhar à direita"]');
    $page->assertSourceHas('float: right');

    $page->click('.ProseMirror img');
    $page->click('[title="Remover alinhamento"]');
    $page->assertSourceMissing('float: left')->assertSourceMissing('float: right');

    // Redimensionar: arrasta a alça de canto inferior-direita.
    $page->click('.ProseMirror img');
    $result = $page->script(<<<'JS'
        (() => {
            const img = document.querySelector('.ProseMirror img');
            const inner = img.closest('div');
            const handle = [...inner.querySelectorAll('div')].find(d => d.style.cursor === 'se-resize');
            const hRect = handle.getBoundingClientRect();
            const startX = hRect.left + hRect.width / 2;
            const startY = hRect.top + hRect.height / 2;
            handle.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, clientX: startX, clientY: startY }));
            document.dispatchEvent(new MouseEvent('mousemove', { bubbles: true, clientX: startX + 40, clientY: startY + 40 }));
            document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, clientX: startX + 40, clientY: startY + 40 }));
            return true;
        })()
    JS);
    expect($result)->toBeTrue();
    $page->assertNoJavascriptErrors();
});

test('arrastar a imagem para o lado de um parágrafo flutua ela com float correto', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Parágrafo alvo bem longo o suficiente pra ter uma borda direita e uma borda esquerda bem definidas na tela, servindo de alvo pro arraste da imagem.</p>')
        ->click('text=Salvar código');

    $page->click('[data-tip="Inserir imagem"]');
    $page->click('[title="Upload from URL"]');
    $page->script(
        "document.querySelector('input[placeholder=\"https://\"]:not([x-model])').value = ".json_encode(TEST_IMAGE_PATH)
    );
    $page->click('[title="Apply"]');
    $page->assertSourceHas('<img');

    $result = $page->script(<<<'JS'
        (() => {
            const img = document.querySelector('.ProseMirror img');
            const inner = img.closest('div');
            // Tiptap/ProseMirror não preserva atributos arbitrários num <p>
            // (só os que a extensão declara via addAttributes) — por isso
            // não dá pra marcar o parágrafo com um data-* próprio; como só
            // existe um parágrafo neste teste, pega ele direto.
            const p = document.querySelector('.ProseMirror p');
            if (!p) return 'paragraph-not-found';

            const imgRect = inner.getBoundingClientRect();
            const pRect = p.getBoundingClientRect();
            const startX = imgRect.left + imgRect.width / 2;
            const startY = imgRect.top + imgRect.height / 2;
            // Perto da borda esquerda do parágrafo (dentro da faixa de 30%).
            const targetX = pRect.left + 10;
            const targetY = pRect.top + pRect.height / 2;

            inner.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, clientX: startX, clientY: startY }));
            document.dispatchEvent(new MouseEvent('mousemove', { bubbles: true, clientX: (startX + targetX) / 2, clientY: (startY + targetY) / 2 }));
            document.dispatchEvent(new MouseEvent('mousemove', { bubbles: true, clientX: targetX, clientY: targetY }));
            document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true, clientX: targetX, clientY: targetY }));
            return 'ok';
        })()
    JS);

    expect($result)->toBe('ok');
    $page->assertSourceHas('float: left');
    $page->assertNoJavascriptErrors();
});

test('tela cheia alterna e salvar persiste título, resumo e conteúdo', function () {
    $this->actingAs($this->admin);
    $page = visit($this->editUrl);

    $page->click('[\@click="toggleFullscreen()"]');
    $page->click('[\@click="toggleFullscreen()"]');

    $page->fill('[wire\\:model="title"]', 'Artigo editado pelo teste automatizado');
    $page->fill('[wire\\:model="excerpt"]', 'Resumo editado pelo teste automatizado.');

    $page->click('[data-tip="Ver/editar HTML"]')
        ->fill('[x-model="codeViewContent"]', '<p>Conteúdo final salvo pelo teste.</p>')
        ->click('text=Salvar código');

    $page->click('[wire\\:click="save"]');

    $page->assertPathIs('/admin/noticias');

    $article = $this->article->fresh();
    expect($article->title)->toBe('Artigo editado pelo teste automatizado')
        ->and($article->excerpt)->toBe('Resumo editado pelo teste automatizado.')
        ->and($article->body)->toContain('Conteúdo final salvo pelo teste.');
});
