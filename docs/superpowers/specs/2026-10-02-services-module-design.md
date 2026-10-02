# Módulo Serviços — Design

**Data:** 2026-10-02
**Status:** Aprovado para planejamento
**Escopo:** Primeiro dos dois sub-projetos de conteúdo que seguem a fundação da intranet (`2026-10-01-intranet-foundation-design.md`). Implementa a seção "Serviços" do menu lateral — hoje uma rota placeholder ("Em construção") — como um catálogo real de links institucionais, com tela administrativa de gerenciamento. O módulo Mural (feed de notícias) é um sub-projeto separado, a ser especificado depois.

## Contexto

O mockup `servico.html` mostra uma grade responsiva de ~24 "serviços" — cada um é só um ícone + nome + link para um sistema interno/externo da Defensoria (Portal do Defensor, Sistema Solar, Protocolo SEI, Vade Meccum, Chamados T.I., etc.). Não há categorias, busca, paginação, autor, nem fluxo de aprovação — é essencialmente um diretório de atalhos mantido manualmente pela administração.

Isso é estruturalmente diferente do modelo genérico Article/Category combinado no spec da fundação (pensado para conteúdo editorial como o Mural). Serviços não é conteúdo editorial — é uma lista de referência institucional, então recebe seu próprio modelo de dados simples, conforme a decisão já tomada no spec da fundação ("Base genérica + extensões por seção").

## Objetivos

- Modelo de dados `ServiceLink`: título, ícone, URL, ordem de exibição, ativo/inativo.
- Nova permissão `services.manage`, concedida a Super Administrador e Administrador (Gestor e Editor não gerenciam esta lista — são sistemas institucionais, não conteúdo por departamento).
- Tela administrativa `/admin/servicos`: listagem + modal de criar/editar + reordenação via botões subir/descer + ativar/desativar + excluir.
- Página pública `/servicos`, substituindo a rota placeholder atual, visível a qualquer usuário autenticado (sem restrição de permissão — é um diretório compartilhado, igual está hoje no `config/sidebar.php`), com a grade responsiva fiel ao mockup.
- Entrada "Gerenciar Serviços" no grupo ADMINISTRAÇÃO do menu lateral, visível só com a permissão `services.manage` (mesmo padrão da entrada "Usuários").
- Testes cobrindo CRUD administrativo, controle de acesso, reordenação e a página pública.

## Fora de escopo

- Categorias ou agrupamento dos serviços (decisão: lista única com ordem manual, igual ao mockup).
- Links internos à intranet (decisão: todo serviço é uma URL completa, sempre abre em nova aba — mesmo que a URL colada seja de uma rota interna, o campo é só texto livre).
- Drag-and-drop de reordenação (decisão: botões subir/descer, para não introduzir a dependência `@alpinejs/sort`, que não está instalada neste projeto e não tem outro uso).
- Qualquer coisa relacionada ao módulo Mural — spec separado.

## Modelo de dados

Tabela `service_links`:

| Coluna | Tipo | Observação |
|---|---|---|
| `id` | bigint | |
| `title` | string | Nome exibido, ex. "Vade Meccum" |
| `icon` | string | Slug do ícone Font Awesome solid, ex. `book-open` (renderizado como `fa-solid fa-{{ $icon }}`, mesma convenção já usada em `config/sidebar.php`) |
| `url` | string | URL completa, sempre externa/nova aba |
| `order` | unsigned integer, default 0 | Define a posição na grade; menor primeiro |
| `is_active` | boolean, default true | Serviços inativos não aparecem na página pública, mas continuam na tela administrativa |
| timestamps | | |

Model `App\Models\ServiceLink`: `fillable` = `title, icon, url, order, is_active`; sem soft deletes (exclusão é definitiva — é só uma entrada de catálogo, não há histórico/auditoria necessário aqui).

## Permissões

Adiciona `services.manage` ao `PermissionManager::inventory()`. Atualiza o `AclSeeder`:
- **Super Administrador**: já recebe tudo (via `inventory()`), nenhuma mudança de código necessária além de regenerar o inventário.
- **Administrador**: adiciona `services.manage` à sua lista de permissões.
- **Gestor** e **Editor**: sem acesso (nenhuma mudança).

## Admin: `/admin/servicos`

Mesmo padrão arquitetural de `/admin/usuarios` (Tasks 12/13 da fundação):

- Middleware: `auth`, `admin`, `permission:services.manage`.
- `ServiceLinkIndex` (Livewire, full-page): tabela com título, ícone (preview do ícone real, não só o nome), URL, status ativo/inativo, e ações — mover para cima, mover para baixo, ativar/desativar, excluir. Botão "Novo serviço" abre modal com `ServiceLinkForm`, mesmo padrão de modal dinâmico com `wire:key` que já existe em `UserIndex`/`UserForm` (incluindo o botão "Editar" por linha — não repetir o gap que a revisão final da fundação encontrou lá).
- `ServiceLinkForm` (Livewire, embutido no modal): título, ícone (texto livre, com validação simples de formato — letras minúsculas e hífen), URL (validação `url`), ativo (checkbox). Criar e editar no mesmo componente, igual ao `UserForm`.
- Mover para cima/baixo: troca o valor de `order` com o item adjacente (swap simples, sem reordenar a tabela inteira).
- Exclusão: sem salvaguarda especial (diferente dos usuários, não há conceito de "último item obrigatório" aqui) — só confirmação via `wire:confirm`, igual ao botão excluir de usuários.

## Página pública: `/servicos`

- Rota nomeada `services.index`, substitui a entrada `placeholder.servicos` em `config/sidebar.php` (passa a apontar para esta rota real; permissão continua `null`, acessível a qualquer usuário autenticado).
- Livewire full-page component `ServicesIndex`, layout padrão (`layouts::app`).
- Busca `ServiceLink::where('is_active', true)->orderBy('order')->get()` e renderiza a grade responsiva fiel ao mockup: badge institucional "SERVIÇOS" no topo, grade `grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4`, cada item como link (`target="_blank" rel="noopener"`) com ícone circular com borda e nome.
- Estado vazio (nenhum serviço ativo cadastrado): mensagem simples, sem quebrar o layout.

## Menu lateral

Duas mudanças em `config/sidebar.php`:
1. Grupo CONTEÚDO → item "Serviços": `route` muda de `placeholder.servicos` para `services.index`.
2. Grupo ADMINISTRAÇÃO → novo item "Gerenciar Serviços", `route` = `admin.services.index`, `permission` = `services.manage`.

## Testes

- **Modelo/migração**: fábrica `ServiceLinkFactory` para os testes.
- **Controle de acesso**: Super Administrador e Administrador acessam `/admin/servicos` (200); Gestor e Editor recebem 403; acesso direto pela URL (não só esconder o link) é bloqueado pelo middleware, igual ao padrão de usuários.
- **CRUD administrativo**: criar serviço válido; validação de URL inválida rejeitada; editar título/ícone/URL/ativo; excluir remove de fato.
- **Reordenação**: mover um item para cima troca sua `order` com o anterior; mover o primeiro item para cima não faz nada (não existe anterior); mesma lógica simétrica para mover para baixo no último item.
- **Página pública**: usuário autenticado vê os serviços ativos na ordem correta; serviço inativo não aparece; usuário não autenticado é redirecionado para login; link renderizado tem `target="_blank"`.
- **Menu lateral**: usuário sem `services.manage` não vê "Gerenciar Serviços"; usuário com a permissão vê; o item "Serviços" do grupo CONTEÚDO aponta para a página real, não mais para o placeholder.

## Riscos / decisões em aberto

- **Ícones**: a validação do campo `icon` é só de formato (string, sem espaços, caracteres de slug), não verifica se o ícone realmente existe no conjunto do Font Awesome carregado via CDN — um ícone inválido simplesmente não renderiza nada visível, sem erro. Aceitável para este módulo; não é um caso que vale a complexidade de validar contra uma lista fixa de ícones.
- **Troca de `order`**: como é uma simples troca de dois valores (não uma reindexação completa), se dois serviços acabarem com o mesmo valor de `order` por qualquer motivo futuro (ex. inserção manual direta no banco), a ordenação entre eles fica indefinida mas estável — não é um bug que trava a aplicação, só uma ordem não determinística entre esses dois itens específicos.
