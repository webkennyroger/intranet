# Módulo Mural — Design

**Data:** 2026-10-02
**Status:** Aprovado para planejamento
**Escopo:** Segundo dos dois sub-projetos de conteúdo que seguem a fundação da intranet. Implementa a seção "Mural" do menu lateral — hoje uma rota placeholder — como um feed real de notícias/avisos, com fluxo de rascunho→publicado, categorias, busca/filtro, curtidas e uma tela administrativa de gerenciamento. Reaproveita o motor de permissões `content.*` já seedado na fundação — nenhuma permissão nova é necessária.

## Contexto

O mockup `mural.html` mostra um feed de 9 cards de notícia em grade 3x3, cada um com: uma área de capa colorida com ícone/texto sobreposto (não é foto real), um badge de tipo ("Notícia"), categoria, título, resumo, botão de curtir com contador, data de publicação e link "Saiba mais". Acima do feed, um card de filtros: busca por palavra-chave, categoria (Institucional / Avisos e Campanhas / Sistemas & TI), período (7 dias / 30 dias / ano). Abaixo, paginação.

Isso mapeia diretamente no modelo genérico Article+Category já decidido no spec da fundação. Diferente de Serviços (um catálogo simples), o Mural é conteúdo editorial de verdade: tem corpo de texto, autor, fluxo de publicação e precisa do editor Tiptap já portado na fundação.

## Objetivos

- Modelo `Category` (nome, slug) — seedado com Institucional, Avisos e Campanhas, Sistemas & TI.
- Modelo `Article`: título, resumo, corpo (HTML via Tiptap), categoria, capa (cor + ícone + rótulo curto — **sem upload de imagem**, decisão explícita abaixo), autor, status rascunho/publicado, data de publicação.
- Curtidas por usuário (tabela `article_likes`, único por usuário+artigo), com toggle.
- Fluxo de publicação usando as permissões `content.*` já existentes: `content.create`/`content.update` (Editor, Gestor, Administrador, Super Administrador) criam/editam; `content.publish` (Gestor, Administrador, Super Administrador) publica/despublica; `content.delete` (Gestor, Administrador, Super Administrador — Editor não tem) exclui. Nenhuma permissão nova.
- Tela administrativa `/admin/mural`: lista de artigos (todos os status), criar/editar com o editor Tiptap, publicar/despublicar, excluir.
- Página pública `/mural`, substituindo o placeholder: feed paginado só de artigos publicados, com busca por palavra-chave, filtro de categoria, filtro de período, curtir/descurtir.
- Página de artigo `/mural/{article}`, acessada pelo link "Saiba mais": título, capa, corpo completo, data, curtidas.
- Entrada "Gerenciar Mural" no grupo ADMINISTRAÇÃO, visível só com `content.view`.

## Decisões explícitas (fora de escopo ou simplificadas)

- **Sem upload de imagem de capa.** O mockup usa áreas coloridas com ícone/texto sobreposto, não fotos — a capa é só `cor` (valor livre, ex. um hex ou nome de cor Tailwind) + `ícone` (slug Font Awesome) + `rótulo` curto opcional. Construir uma biblioteca de mídia/upload de arquivos é escopo maior, não pedido agora.
- **Curtidas por usuário, com toggle**, via tabela `article_likes` (chave única usuário+artigo) — evita contagem inflada por clique repetido.
- **Fluxo rascunho → publicado, uma etapa só.** Sem fila de revisão intermediária, sem notificação de aprovação. Usa exatamente as permissões `content.*` já seedadas.
- **Sem restrição por departamento do Gestor.** A fundação já registrou essa decisão como deferida ("enforcement logic arrives with the first content sub-project that needs it") — este módulo não implementa isso agora; qualquer Gestor pode publicar qualquer artigo, independente do campo `department` do usuário. Fica para um sub-projeto futuro se vier a ser necessário.
- **Sem comentários, sem compartilhamento, sem notificações.** Só o que o mockup mostra: curtir e ler.
- **Sem restrição de autoria.** Qualquer usuário com `content.update` pode editar qualquer artigo, não só o que ele mesmo criou — mesmo padrão (sem ownership) usado em Usuários e Serviços.
- **Admin de artigos é genérico, não só "Mural".** A tela administrativa gerencia `Article`s de qualquer categoria — é o mesmo motor que um futuro módulo "Notícias" reaproveitaria (conforme a decisão da fundação). O rótulo do menu lateral ("Gerenciar Mural") e a página pública (`/mural`) são específicos deste sub-projeto; a tela admin em si não filtra por categoria.

## Modelo de dados

Tabela `categories`:

| Coluna | Tipo |
|---|---|
| `id` | bigint |
| `name` | string |
| `slug` | string, unique |
| timestamps | |

Tabela `articles`:

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint | |
| `category_id` | bigint, nullable, FK → categories, `onDelete('set null')` | |
| `author_id` | bigint, FK → users | |
| `title` | string | |
| `excerpt` | text | Resumo mostrado no card |
| `body` | longtext | HTML gerado pelo editor Tiptap |
| `cover_color` | string | Ex. `#064e3b` ou um token simples |
| `cover_icon` | string | Slug Font Awesome, ex. `leaf` |
| `cover_label` | string, nullable | Texto curto sobreposto na capa, ex. "KIT SUSTENTABILIDADE" |
| `is_published` | boolean, default false | |
| `published_at` | timestamp, nullable | Preenchido quando `is_published` passa a `true` |
| timestamps | | |

Índice: `(is_published, published_at)` — mesma lógica de performance do índice de Serviços, para a query da página pública.

Tabela `article_likes`:

| Coluna | Tipo |
|---|---|
| `id` | bigint |
| `article_id` | bigint, FK → articles, `onDelete('cascade')` |
| `user_id` | bigint, FK → users, `onDelete('cascade')` |
| timestamps | |

Único composto `(article_id, user_id)`.

Models: `Category` (fillable: name, slug; `hasMany(Article::class)`), `Article` (fillable: title, excerpt, body, category_id, cover_color, cover_icon, cover_label, is_published; `belongsTo(Category::class)`, `belongsTo(User::class, 'author_id')`, `belongsToMany(User::class, 'article_likes')->withTimestamps()`, acessor `likesCount()`/`isLikedBy(User $user)`).

## Admin: `/admin/mural`

- Middleware: `auth`, `admin`, `permission:content.view`.
- `ArticleIndex` (Livewire, full-page): tabela com título, categoria, status (rascunho/publicado), curtidas, data, ações — Editar, Publicar/Despublicar (só visível/permitido com `content.publish`), Excluir (só visível/permitido com `content.delete`). Mesmo padrão de modal dinâmico com `wire:key` do `UserIndex`/`ServiceLinkIndex` (com o botão "Editar" por linha — não repetir o gap que a fundação já corrigiu).
- `ArticleForm` (Livewire, embutido no modal): título, resumo, categoria (select), cor da capa, ícone da capa, rótulo da capa, corpo (editor Tiptap já portado na fundação, integrado via o evento `content-change` que o componente já dispara).
- Publicar: `abort_unless(auth()->user()->can('content.publish'), 403)`; seta `is_published = true` e `published_at = now()` se ainda não tinha data.
- Despublicar: mesma permissão; seta `is_published = false` (mantém `published_at` histórico).
- Excluir: `abort_unless(auth()->user()->can('content.delete'), 403)`.

## Página pública: `/mural`

- Rota `mural.index`, substitui `placeholder.mural` em `config/sidebar.php` (permissão continua `null`).
- `MuralIndex` (Livewire, full-page, com `WithPagination`): propriedades reativas `search` (palavra-chave em título/resumo), `categoryId` (filtro), `period` (`7d`/`30d`/`year`/vazio).
- Query: `Article::published()->when(...)->orderByDesc('published_at')->paginate(9)` — "published" como scope local (`is_published = true`).
- Cada card: capa (cor+ícone+rótulo), categoria, título, resumo, botão de curtir (toggle via Livewire, mostra contador atualizado), data, link "Saiba mais" → `mural.show`.

## Página de artigo: `/mural/{article}`

- Rota `mural.show`, `auth` apenas.
- Mostra artigos publicados; se o artigo existe mas não está publicado, 404 (não deixa adivinhar rascunhos pela URL).
- Título, capa, corpo renderizado (HTML do Tiptap, sem escapar — já é HTML controlado por usuários com `content.create`/`update`, mesmo nível de confiança que o restante do admin), data, curtidas.

## Menu lateral

- Grupo CONTEÚDO → "Mural": `route` muda de `placeholder.mural` para `mural.index`.
- Grupo ADMINISTRAÇÃO → novo item "Gerenciar Mural", `route` = `admin.articles.index`, `permission` = `content.view`.

## Testes

- **Modelo**: fábricas `CategoryFactory`/`ArticleFactory`; curtir/descurtir via a relação `belongsToMany`.
- **Controle de acesso admin**: usuários com `content.view` (Editor, Gestor, Administrador, Super Administrador) acessam `/admin/mural`; publicar exige `content.publish` (Editor tentando publicar recebe 403, mesmo acessando a lista); excluir exige `content.delete` (Editor recebe 403).
- **CRUD administrativo**: criar artigo em rascunho; editar; publicar seta `published_at`; despublicar preserva `published_at` anterior; excluir remove.
- **Página pública**: só artigos publicados aparecem; busca por palavra-chave filtra; filtro de categoria filtra; filtro de período (7 dias) exclui artigo mais antigo; paginação de 9 por página; curtir incrementa e é idempotente por usuário (clicar duas vezes = curtir depois descurtir, não dois registros); título/resumo escapados (XSS).
- **Página de artigo**: artigo publicado é acessível; artigo em rascunho retorna 404 mesmo para quem tem permissão de ver (a visualização pública nunca expõe rascunho pela URL — quem precisa ver rascunho usa a tela admin).
- **Menu lateral**: "Gerenciar Mural" só aparece com `content.view`; "Mural" aponta para a página real.

## Riscos / decisões em aberto

- **Corpo do artigo é HTML não sanitizado além do que o editor já produz.** Mesma confiança que o resto do painel administrativo (usuários com `content.create`/`update` já são contas internas, não público anônimo) — não é escapado no `{{ }}` porque precisa renderizar como HTML real; isso é intencional, não uma omissão.
- **Filtro de período usa `published_at`, não `created_at`** — um artigo despublicado e republicado depois de muito tempo "perde" sua data original de publicação a cada ciclo. Aceitável para este módulo; não há requisito de preservar histórico de publicações.
