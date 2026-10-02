# Módulo Mural Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the "Em construção" placeholder for Mural with a real news feed: `Category`/`Article`/`article_likes` tables, an admin screen to create/edit/publish/delete articles (using the Tiptap editor already ported in the foundation), a public feed page with search/category/period filters and per-user likes, and an article detail page.

**Architecture:** Same proven pattern as the Services module: Livewire `*Index` full-page components with a `*Form` child in a dynamic-`wire:key` modal, gated by permission checks. Unlike Services, this reuses the `content.*` permissions already seeded in the foundation — no new permission is created. The admin screen is a generic `Article` manager (not filtered to one category), matching the foundation spec's decision that this is a shared content engine future modules (e.g. Notícias) could also use; only the public feed and sidebar label are specific to "Mural".

**Tech Stack:** Laravel 13, Livewire 4, Pest 4, Tailwind 4, spatie/laravel-permission (existing `content.*` permissions), the already-ported Tiptap editor component — no new dependencies.

**Spec:** `docs/superpowers/specs/2026-10-02-mural-module-design.md`

## Global Constraints

- No new permissions — reuse `content.view` (Editor/Gestor/Administrador/Super Administrador), `content.create`/`content.update` (same four), `content.publish` and `content.delete` (Gestor/Administrador/Super Administrador only — Editor has neither).
- No image upload / media library. Article covers are `cover_color` (string) + `cover_icon` (Font Awesome slug) + optional `cover_label` (short text) — exactly like Services' icon-only approach, just three fields instead of one.
- No department-based scoping for Gestor — explicitly deferred per the foundation spec; any Gestor can publish any article.
- Single draft → published step. No review queue, no notifications.
- No ownership restriction — anyone with `content.update` can edit any article, matching the Usuários/Serviços precedent.
- The admin article manager is category-agnostic; "Mural" is just this plan's public-facing route/label built on top of it.
- Follow the exact edit-modal wiring pattern from `UserIndex`/`ServiceLinkIndex` (dynamic `wire:key` + `editingId` property + a real "Editar" button per row) — do not repeat the gap the foundation's final review found.

## Review Focus

- A draft (unpublished) article must never appear on the `/mural` feed, and `/mural/{article}` for a draft must return 404 — not reachable by guessing the URL even for a user who could see it in the admin list.
- An Editor (has `content.view` but not `content.publish`/`content.delete`) must be blocked from publishing or deleting an article even by calling the Livewire action directly — not just by the button being hidden, since this is a real permission gap (unlike Services, where every role reaching the screen already had the one permission it checked).
- Liking the same article twice by the same user must toggle (like → unlike), never create two like rows or double-count.
- The "last 7 days" period filter must use `published_at`, correctly excluding an older published article from the results.
- Article `title`/`excerpt` must render escaped (XSS); `body` intentionally renders as raw trusted HTML (Tiptap output from permissioned staff) — confirm this is a deliberate `{!! !!}` only on `body`, nowhere else.

---

## File Structure

```
app/
  Models/Category.php
  Models/Article.php
  Livewire/
    Admin/Articles/ArticleIndex.php
    Admin/Articles/ArticleForm.php
    Mural/MuralIndex.php
    Mural/ArticleShow.php
database/
  factories/CategoryFactory.php
  factories/ArticleFactory.php
  migrations/2026_10_02_000002_create_categories_table.php
  migrations/2026_10_02_000003_create_articles_table.php
  migrations/2026_10_02_000004_create_article_likes_table.php
resources/views/livewire/
  admin/articles/article-index.blade.php
  admin/articles/article-form.blade.php
  mural/mural-index.blade.php
  mural/article-show.blade.php
config/sidebar.php          (modified)
routes/web.php               (modified)
tests/Feature/
  ArticleModelTest.php
  Admin/ArticleManagementTest.php
  MuralPageTest.php
  ArticleShowPageTest.php
```

---

### Task 1: Data layer — Category, Article, ArticleLike

**Files:**
- Create: `database/migrations/2026_10_02_000002_create_categories_table.php`, `database/migrations/2026_10_02_000003_create_articles_table.php`, `database/migrations/2026_10_02_000004_create_article_likes_table.php`, `app/Models/Category.php`, `app/Models/Article.php`, `database/factories/CategoryFactory.php`, `database/factories/ArticleFactory.php`
- Test: `tests/Feature/ArticleModelTest.php`

**Interfaces:**
- Produces: `Category` (fillable: name, slug), `Article` (fillable: category_id, author_id, title, excerpt, body, cover_color, cover_icon, cover_label, is_published, published_at; `category()`, `author()`, `likedByUsers()`, `isLikedBy(User $user): bool`, scope `published()`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ArticleModelTest.php`:

```php
<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\QueryException;

test('an article can be created with the expected fields', function () {
    $category = Category::factory()->create();
    $author = User::factory()->create();

    $article = Article::create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Teste',
        'excerpt' => 'Resumo',
        'body' => '<p>Conteúdo</p>',
        'cover_color' => '#064e3b',
        'cover_icon' => 'newspaper',
        'is_published' => false,
    ]);

    expect($article->title)->toBe('Teste')
        ->and($article->category->id)->toBe($category->id)
        ->and($article->author->id)->toBe($author->id);
});

test('the published scope only returns published articles', function () {
    Article::factory()->create(['is_published' => true]);
    Article::factory()->create(['is_published' => false]);

    expect(Article::published()->count())->toBe(1);
});

test('a user can like and unlike an article', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();

    expect($article->isLikedBy($user))->toBeFalse();

    $article->likedByUsers()->attach($user->id);
    expect($article->refresh()->isLikedBy($user))->toBeTrue()
        ->and($article->likedByUsers()->count())->toBe(1);

    $article->likedByUsers()->detach($user->id);
    expect($article->refresh()->isLikedBy($user))->toBeFalse();
});

test('a user cannot like the same article twice at the database level', function () {
    $article = Article::factory()->create();
    $user = User::factory()->create();

    $article->likedByUsers()->attach($user->id);

    expect(fn () => $article->likedByUsers()->attach($user->id))
        ->toThrow(QueryException::class);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ArticleModelTest`
Expected: FAIL — `Category`/`Article` classes don't exist yet.

- [ ] **Step 3: Create the migrations**

Create `database/migrations/2026_10_02_000002_create_categories_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

Create `database/migrations/2026_10_02_000003_create_articles_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->string('title');
            $table->text('excerpt');
            $table->longText('body');
            $table->string('cover_color');
            $table->string('cover_icon');
            $table->string('cover_label')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

Create `database/migrations/2026_10_02_000004_create_article_likes_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['article_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_likes');
    }
};
```

- [ ] **Step 4: Create the Category model**

Create `app/Models/Category.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
```

- [ ] **Step 5: Create the Article model**

Create `app/Models/Article.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'author_id', 'title', 'excerpt', 'body',
        'cover_color', 'cover_icon', 'cover_label', 'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'article_likes')->withTimestamps();
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
```

- [ ] **Step 6: Create the factories**

Create `database/factories/CategoryFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
```

Create `database/factories/ArticleFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'author_id' => User::factory(),
            'title' => fake()->sentence(),
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'cover_color' => '#064e3b',
            'cover_icon' => 'newspaper',
            'cover_label' => null,
            'is_published' => false,
            'published_at' => null,
        ];
    }
}
```

- [ ] **Step 7: Run migrations and tests**

Run:
```bash
php artisan migrate --no-interaction
php artisan test --filter=ArticleModelTest
```
Expected: all 4 tests PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add Category and Article models with likes"
```

(Review `git status` before adding — only stage the files this task actually created/modified.)

---

### Task 2: Admin — article list, create/edit modal with Tiptap, publish/delete

**Files:**
- Create: `app/Livewire/Admin/Articles/ArticleIndex.php`, `app/Livewire/Admin/Articles/ArticleForm.php`, `resources/views/livewire/admin/articles/article-index.blade.php`, `resources/views/livewire/admin/articles/article-form.blade.php`
- Modify: `routes/web.php`, `config/sidebar.php`
- Test: `tests/Feature/Admin/ArticleManagementTest.php`

**Interfaces:**
- Consumes: `Article`/`Category` models (Task 1), `<x-ui.data-table>`/`<x-ui.modal>`/`<x-ui.input>`/`<x-ui.select>`/`<x-ui.color-picker>`/`<x-ui.button>` (foundation UI kit), `<x-editor.tiptap>` (foundation editor component).
- Produces: route `admin.articles.index` (GET, `auth`+`admin`+`permission:content.view`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/ArticleManagementTest.php`:

```php
<?php

use App\Livewire\Admin\Articles\ArticleForm;
use App\Livewire\Admin\Articles\ArticleIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

function actingAsRole(string $role): User
{
    test()->seed(AclSeeder::class);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

test('an Editor can view the articles management page', function () {
    actingAsRole('Editor');

    $this->get(route('admin.articles.index'))->assertStatus(200);
});

test('creating an article saves it as a draft', function () {
    actingAsRole('Editor');
    $category = Category::factory()->create();

    Livewire::test(ArticleForm::class)
        ->set('title', 'Novo Artigo')
        ->set('excerpt', 'Resumo do artigo')
        ->set('body', '<p>Conteúdo</p>')
        ->set('categoryId', (string) $category->id)
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasNoErrors();

    $article = Article::where('title', 'Novo Artigo')->first();
    expect($article)->not->toBeNull()
        ->and($article->is_published)->toBeFalse();
});

test('creating an article without a body is rejected', function () {
    actingAsRole('Editor');

    Livewire::test(ArticleForm::class)
        ->set('title', 'Sem Corpo')
        ->set('excerpt', 'Resumo')
        ->set('body', '')
        ->set('coverColor', '#064e3b')
        ->set('coverIcon', 'newspaper')
        ->call('save')
        ->assertHasErrors(['body']);

    expect(Article::where('title', 'Sem Corpo')->exists())->toBeFalse();
});

test('editing an existing article updates it', function () {
    actingAsRole('Editor');
    $article = Article::factory()->create(['title' => 'Antigo']);

    Livewire::test(ArticleForm::class, ['article' => $article])
        ->set('title', 'Novo Título')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->refresh()->title)->toBe('Novo Título');
});

test('an Editor cannot publish an article', function () {
    actingAsRole('Editor');
    $article = Article::factory()->create(['is_published' => false]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);
})->throws(HttpException::class);

test('a Gestor can publish an article and it gets a published_at date', function () {
    actingAsRole('Gestor');
    $article = Article::factory()->create(['is_published' => false, 'published_at' => null]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull();
});

test('unpublishing an article preserves its original published_at', function () {
    actingAsRole('Gestor');
    $originalDate = now()->subDays(5);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => $originalDate]);

    Livewire::test(ArticleIndex::class)->call('togglePublish', $article->id);

    expect($article->refresh()->is_published)->toBeFalse()
        ->and($article->published_at->isSameMinute($originalDate))->toBeTrue();
});

test('an Editor cannot delete an article', function () {
    actingAsRole('Editor');
    $article = Article::factory()->create();

    Livewire::test(ArticleIndex::class)->call('delete', $article->id);
})->throws(HttpException::class);

test('a Gestor can delete an article', function () {
    actingAsRole('Gestor');
    $article = Article::factory()->create();

    Livewire::test(ArticleIndex::class)->call('delete', $article->id);

    expect(Article::find($article->id))->toBeNull();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ArticleManagementTest`
Expected: FAIL — route and components don't exist yet.

- [ ] **Step 3: Create the ArticleIndex component**

Create `app/Livewire/Admin/Articles/ArticleIndex.php`:

```php
<?php

namespace App\Livewire\Admin\Articles;

use App\Models\Article;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleIndex extends Component
{
    use WithPagination;

    public ?int $editingArticleId = null;

    #[On('article-saved')]
    public function refreshList(): void
    {
        $this->editingArticleId = null;
    }

    public function edit(int $articleId): void
    {
        $this->editingArticleId = $articleId;
    }

    public function closeForm(): void
    {
        $this->editingArticleId = null;
    }

    public function togglePublish(int $articleId): void
    {
        abort_unless(auth()->user()->can('content.publish'), 403);

        $article = Article::findOrFail($articleId);

        if ($article->is_published) {
            $article->update(['is_published' => false]);
        } else {
            $article->update([
                'is_published' => true,
                'published_at' => $article->published_at ?? now(),
            ]);
        }
    }

    public function delete(int $articleId): void
    {
        abort_unless(auth()->user()->can('content.delete'), 403);

        Article::findOrFail($articleId)->delete();
    }

    public function render()
    {
        return view('livewire.admin.articles.article-index', [
            'articles' => Article::with(['category', 'author'])->latest()->paginate(15),
            'editingArticle' => $this->editingArticleId ? Article::find($this->editingArticleId) : null,
        ]);
    }
}
```

- [ ] **Step 4: Create the ArticleForm component**

Create `app/Livewire/Admin/Articles/ArticleForm.php`:

```php
<?php

namespace App\Livewire\Admin\Articles;

use App\Models\Article;
use App\Models\Category;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ArticleForm extends Component
{
    public ?Article $article = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|max:500')]
    public string $excerpt = '';

    #[Validate('required|string')]
    public string $body = '';

    public string $categoryId = '';

    #[Validate('required|string|max:20')]
    public string $coverColor = '#064e3b';

    #[Validate('required|regex:/^[a-z0-9-]+$/')]
    public string $coverIcon = 'newspaper';

    public string $coverLabel = '';

    public function mount(?Article $article = null): void
    {
        if ($article) {
            $this->article = $article;
            $this->title = $article->title;
            $this->excerpt = $article->excerpt;
            $this->body = $article->body;
            $this->categoryId = $article->category_id ? (string) $article->category_id : '';
            $this->coverColor = $article->cover_color;
            $this->coverIcon = $article->cover_icon;
            $this->coverLabel = $article->cover_label ?? '';
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'category_id' => $this->categoryId !== '' ? (int) $this->categoryId : null,
            'cover_color' => $this->coverColor,
            'cover_icon' => $this->coverIcon,
            'cover_label' => $this->coverLabel !== '' ? $this->coverLabel : null,
        ];

        if ($this->article) {
            $this->article->update($data);
        } else {
            Article::create($data + [
                'author_id' => auth()->id(),
                'is_published' => false,
            ]);
        }

        $this->dispatch('article-saved');
        $this->reset(['title', 'excerpt', 'body', 'categoryId', 'coverLabel']);
        $this->coverColor = '#064e3b';
        $this->coverIcon = 'newspaper';
    }

    public function render()
    {
        return view('livewire.admin.articles.article-form', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
```

- [ ] **Step 5: Create the index view**

Create `resources/views/livewire/admin/articles/article-index.blade.php`:

```blade
<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800">Mural</h2>
        <div>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'article-form' }))">
                Novo artigo
            </x-ui.button>
        </div>
    </div>

    @if ($articles->isEmpty())
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum artigo cadastrado ainda.
        </div>
    @else
        <x-ui.data-table>
            <x-slot:header>
                <th class="px-6 py-3">Título</th>
                <th class="px-6 py-3">Categoria</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Curtidas</th>
                <th class="px-6 py-3">Ações</th>
            </x-slot:header>

            @foreach ($articles as $article)
                <tr>
                    <td class="px-6 py-3">{{ $article->title }}</td>
                    <td class="px-6 py-3">{{ $article->category?->name ?? '—' }}</td>
                    <td class="px-6 py-3">{{ $article->is_published ? 'Publicado' : 'Rascunho' }}</td>
                    <td class="px-6 py-3">{{ $article->likedByUsers()->count() }}</td>
                    <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                        <button wire:click="edit({{ $article->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'article-form' }))" class="text-sm text-emerald-700 hover:underline">
                            Editar
                        </button>
                        @can('content.publish')
                            <button wire:click="togglePublish({{ $article->id }})" class="text-sm text-amber-700 hover:underline">
                                {{ $article->is_published ? 'Despublicar' : 'Publicar' }}
                            </button>
                        @endcan
                        @can('content.delete')
                            <button wire:click="delete({{ $article->id }})" wire:confirm="Confirma a exclusão deste artigo?" class="text-sm text-red-700 hover:underline">
                                Excluir
                            </button>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $articles->links() }}
            </x-slot:footer>
        </x-ui.data-table>
    @endif

    <x-ui.modal name="article-form" maxWidth="4xl">
        <x-slot:header>Artigo</x-slot:header>
        <livewire:admin.articles.article-form :article="$editingArticle" :key="'article-form-'.($editingArticleId ?? 'new')" />
    </x-ui.modal>
</div>
```

- [ ] **Step 6: Create the form view**

Create `resources/views/livewire/admin/articles/article-form.blade.php`. The Tiptap editor component dispatches a bubbling `content-change` DOM event with the HTML as its detail whenever its content changes (see `resources/views/components/editor/tiptap.blade.php`'s `@script` block) — listen for it on the `<form>` wrapper to sync it into the `body` property, since the editor's own hidden input only binds to a local Alpine variable, not directly to `wire:model`:

```blade
<form wire:submit="save" class="space-y-4" x-on:content-change="$wire.set('body', $event.detail)">
    <x-ui.input wire:model="title" label="Título" />
    <x-ui.input wire:model="excerpt" label="Resumo" />

    <x-ui.select wire:model="categoryId" label="Categoria">
        <option value="">Sem categoria</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.color-picker wire:model="coverColor" label="Cor da capa" />
    <x-ui.input wire:model="coverIcon" label="Ícone da capa (Font Awesome)" hint="Ex: leaf, newspaper, bullhorn" />
    <x-ui.input wire:model="coverLabel" label="Rótulo da capa (opcional)" />

    <div>
        <label class="block text-sm font-medium mb-1.5" style="color: var(--text-secondary);">Conteúdo</label>
        <x-editor.tiptap name="body" :value="$body" placeholder="Escreva o conteúdo do artigo..." height="300px" />
        @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
```

- [ ] **Step 7: Add the admin route**

Edit `routes/web.php`, add inside the existing `admin` route group, alongside the `services.manage` group:

```php
    Route::middleware('permission:content.view')->group(function () {
        Route::get('/mural', \App\Livewire\Admin\Articles\ArticleIndex::class)->name('articles.index');
    });
```

- [ ] **Step 8: Add the sidebar entry**

Edit `config/sidebar.php`, add to the `ADMINISTRAÇÃO` group's `items` array:

```php
            ['label' => 'Gerenciar Mural', 'icon' => 'newspaper', 'uri' => '/admin/mural', 'route' => 'admin.articles.index', 'permission' => 'content.view'],
```

- [ ] **Step 9: Run the tests**

Run: `php artisan test --filter=ArticleManagementTest`
Expected: all 8 tests PASS.

- [ ] **Step 10: Run the full suite**

Run: `php artisan test`
Expected: all tests pass, no regressions.

- [ ] **Step 11: Commit**

```bash
git add -A
git commit -m "Add admin screen for managing Mural articles"
```

---

### Task 3: Public Mural feed — search, filters, likes

**Files:**
- Create: `app/Livewire/Mural/MuralIndex.php`, `resources/views/livewire/mural/mural-index.blade.php`
- Modify: `routes/web.php`, `config/sidebar.php`
- Test: `tests/Feature/MuralPageTest.php`

**Interfaces:**
- Consumes: `Article`/`Category` models (Task 1).
- Produces: route `mural.index` (GET, `auth`), replacing `placeholder.mural`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/MuralPageTest.php`:

```php
<?php

use App\Livewire\Mural\MuralIndex;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

test('a guest is redirected to login from the mural page', function () {
    $this->get('/mural')->assertRedirect(route('login'));
});

test('only published articles appear on the mural page', function () {
    $user = User::factory()->create();
    Article::factory()->create(['title' => 'Publicado', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Rascunho', 'is_published' => false]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertSee('Publicado')->assertDontSee('Rascunho');
});

test('searching by keyword filters the mural feed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Article::factory()->create(['title' => 'Campanha de Natal', 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Outro Assunto', 'is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)
        ->set('search', 'Natal')
        ->assertSee('Campanha de Natal')
        ->assertDontSee('Outro Assunto');
});

test('filtering by category shows only articles in that category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    Article::factory()->create(['title' => 'Da Categoria A', 'category_id' => $categoryA->id, 'is_published' => true, 'published_at' => now()]);
    Article::factory()->create(['title' => 'Da Categoria B', 'category_id' => $categoryB->id, 'is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)
        ->set('categoryId', (string) $categoryA->id)
        ->assertSee('Da Categoria A')
        ->assertDontSee('Da Categoria B');
});

test('filtering by the last 7 days excludes older articles', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Article::factory()->create(['title' => 'Recente', 'is_published' => true, 'published_at' => now()->subDays(2)]);
    Article::factory()->create(['title' => 'Antigo', 'is_published' => true, 'published_at' => now()->subDays(20)]);

    Livewire::test(MuralIndex::class)
        ->set('period', '7d')
        ->assertSee('Recente')
        ->assertDontSee('Antigo');
});

test('toggling like adds and then removes the like for the acting user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(MuralIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeTrue();

    Livewire::test(MuralIndex::class)->call('toggleLike', $article->id);
    expect($article->refresh()->isLikedBy($user))->toBeFalse();
});

test('an article title is escaped in the rendered feed', function () {
    $user = User::factory()->create();
    Article::factory()->create(['title' => '<script>alert(1)</script>', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get('/mural');

    $response->assertDontSee('<script>alert(1)</script>', false);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=MuralPageTest`
Expected: FAIL — route still resolves to the old placeholder.

- [ ] **Step 3: Create the MuralIndex component**

Create `app/Livewire/Mural/MuralIndex.php`:

```php
<?php

namespace App\Livewire\Mural;

use App\Models\Article;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class MuralIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $period = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'categoryId', 'period'], true)) {
            $this->resetPage();
        }
    }

    public function toggleLike(int $articleId): void
    {
        $article = Article::findOrFail($articleId);
        $user = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
        } else {
            $article->likedByUsers()->attach($user->id);
        }
    }

    public function render()
    {
        $query = Article::published()->with('category');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('excerpt', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryId !== '') {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->period !== '') {
            $from = match ($this->period) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                'year' => now()->startOfYear(),
                default => null,
            };

            if ($from) {
                $query->where('published_at', '>=', $from);
            }
        }

        return view('livewire.mural.mural-index', [
            'articles' => $query->orderByDesc('published_at')->paginate(9),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Create the public view**

Create `resources/views/livewire/mural/mural-index.blade.php`:

```blade
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Mural</h1>
        <p class="text-slate-500 text-sm mt-1 font-medium">Acompanhe as últimas notícias e informações.</p>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <x-ui.input wire:model.live.debounce.400ms="search" label="Buscar por palavra chave" />
            </div>
            <div class="md:col-span-4">
                <x-ui.select wire:model.live="categoryId" label="Categoria">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="md:col-span-3">
                <x-ui.select wire:model.live="period" label="Período">
                    <option value="">Todos</option>
                    <option value="7d">Últimos 7 dias</option>
                    <option value="30d">Últimos 30 dias</option>
                    <option value="year">Este ano</option>
                </x-ui.select>
            </div>
        </div>
    </div>

    @if ($articles->isEmpty())
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhuma notícia encontrada.
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($articles as $article)
                <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col">
                    <div class="relative h-52 flex items-center justify-center p-4 overflow-hidden" style="background-color: {{ $article->cover_color }};">
                        <div class="absolute top-3 right-3 z-10">
                            <span class="bg-emerald-800/90 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Notícia</span>
                        </div>
                        <div class="text-center text-white space-y-2 z-10 p-2">
                            <i class="fa-solid fa-{{ $article->cover_icon }} text-3xl"></i>
                            @if ($article->cover_label)
                                <p class="text-xs font-bold uppercase tracking-widest">{{ $article->cover_label }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <button wire:click="toggleLike({{ $article->id }})" class="flex items-center gap-1.5 {{ $article->isLikedBy(auth()->user()) ? 'text-emerald-700' : 'text-slate-500' }} hover:text-emerald-700 font-medium">
                                    <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-thumbs-up"></i>
                                    <span>{{ $article->likedByUsers()->count() }}</span>
                                </button>
                                <span class="flex items-center gap-1">
                                    <i class="fa-regular fa-calendar"></i>
                                    {{ $article->published_at?->format('d/m/Y') }}
                                </span>
                            </div>
                            <h2 class="font-extrabold text-slate-800 text-sm leading-snug">{{ $article->title }}</h2>
                            <p class="text-xs text-slate-600 line-clamp-3 leading-relaxed">{{ $article->excerpt }}</p>
                        </div>

                        <div class="pt-2">
                            <a href="{{ route('mural.show', $article) }}" class="text-emerald-700 font-bold text-xs hover:underline inline-flex items-center gap-1">
                                Saiba mais <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pt-4">
            {{ $articles->links() }}
        </div>
    @endif
</div>
```

- [ ] **Step 5: Replace the placeholder route**

Edit `routes/web.php`, add right after the `/servicos` route:

```php
Route::get('/mural', \App\Livewire\Mural\MuralIndex::class)
    ->middleware('auth')
    ->name('mural.index');
```

- [ ] **Step 6: Update the sidebar config**

Edit `config/sidebar.php`, change the CONTEÚDO group's "Mural" entry's `route` from `placeholder.mural` to `mural.index`.

- [ ] **Step 7: Run the tests**

Run: `php artisan test --filter=MuralPageTest`
Expected: all 7 PASS.

- [ ] **Step 8: Run the full suite**

Run: `php artisan test`
Expected: all tests pass.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "Add the public Mural feed, replacing its placeholder"
```

---

### Task 4: Article detail page

**Files:**
- Create: `app/Livewire/Mural/ArticleShow.php`, `resources/views/livewire/mural/article-show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ArticleShowPageTest.php`

**Interfaces:**
- Consumes: `Article` model (Task 1).
- Produces: route `mural.show` (GET `/mural/{article}`, `auth`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ArticleShowPageTest.php`:

```php
<?php

use App\Livewire\Mural\ArticleShow;
use App\Models\Article;
use App\Models\User;
use Livewire\Livewire;

test('a published article is viewable', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Artigo Publicado', 'is_published' => true, 'published_at' => now()]);

    $response = $this->actingAs($user)->get(route('mural.show', $article));

    $response->assertStatus(200)->assertSee('Artigo Publicado');
});

test('a draft article returns 404 on the public show page', function () {
    $user = User::factory()->create();
    $article = Article::factory()->create(['title' => 'Rascunho', 'is_published' => false]);

    $this->actingAs($user)->get(route('mural.show', $article))->assertStatus(404);
});

test('a guest is redirected to login from the article show page', function () {
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    $this->get(route('mural.show', $article))->assertRedirect(route('login'));
});

test('liking an article from the show page updates the like count', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $article = Article::factory()->create(['is_published' => true, 'published_at' => now()]);

    Livewire::test(ArticleShow::class, ['article' => $article])->call('toggleLike');

    expect($article->refresh()->isLikedBy($user))->toBeTrue();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ArticleShowPageTest`
Expected: FAIL — route and component don't exist yet.

- [ ] **Step 3: Create the ArticleShow component**

Create `app/Livewire/Mural/ArticleShow.php`:

```php
<?php

namespace App\Livewire\Mural;

use App\Models\Article;
use Livewire\Component;

class ArticleShow extends Component
{
    public Article $article;

    public function mount(Article $article): void
    {
        abort_unless($article->is_published, 404);

        $this->article = $article;
    }

    public function toggleLike(): void
    {
        $user = auth()->user();

        if ($this->article->isLikedBy($user)) {
            $this->article->likedByUsers()->detach($user->id);
        } else {
            $this->article->likedByUsers()->attach($user->id);
        }
    }

    public function render()
    {
        return view('livewire.mural.article-show');
    }
}
```

- [ ] **Step 4: Create the view**

Create `resources/views/livewire/mural/article-show.blade.php`:

```blade
<div class="max-w-3xl mx-auto space-y-6">
    <a href="{{ route('mural.index') }}" class="text-emerald-700 text-sm font-bold hover:underline inline-flex items-center gap-1">
        <i class="fa-solid fa-arrow-left text-[10px]"></i> Voltar ao Mural
    </a>

    <div class="rounded-2xl overflow-hidden border border-slate-200">
        <div class="h-52 flex items-center justify-center" style="background-color: {{ $article->cover_color }};">
            <i class="fa-solid fa-{{ $article->cover_icon }} text-4xl text-white"></i>
        </div>
        <div class="p-6 bg-white space-y-4">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <button wire:click="toggleLike" class="flex items-center gap-1.5 {{ $article->isLikedBy(auth()->user()) ? 'text-emerald-700' : 'text-slate-500' }} hover:text-emerald-700 font-medium">
                    <i class="fa-{{ $article->isLikedBy(auth()->user()) ? 'solid' : 'regular' }} fa-thumbs-up"></i>
                    <span>{{ $article->likedByUsers()->count() }}</span>
                </button>
                <span>{{ $article->published_at?->format('d/m/Y') }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-800">{{ $article->title }}</h1>
            <div class="prose prose-sm max-w-none">{!! $article->body !!}</div>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Add the route**

Edit `routes/web.php`, add right after the `/mural` route:

```php
Route::get('/mural/{article}', \App\Livewire\Mural\ArticleShow::class)
    ->middleware('auth')
    ->name('mural.show');
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --filter=ArticleShowPageTest`
Expected: all 4 PASS.

- [ ] **Step 7: Run the full suite**

Run: `php artisan test`
Expected: all tests pass.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add the article detail page"
```

---

### Task 5: Final polish and manual verification

**Files:**
- No new files — formatting, full-suite verification, demo data, and a real browser check.

- [ ] **Step 1: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`. If the working tree happens to be clean when this runs (nothing pending), also run `vendor/bin/pint --test --format agent` against the whole project to catch anything a prior "dirty" check would have silently skipped — this exact gap bit the Services module's final review.

- [ ] **Step 2: Run the entire test suite**

Run: `php artisan test`
Expected: all tests pass, zero failures, zero risky.

- [ ] **Step 3: Build frontend assets**

Run: `npm run build`

- [ ] **Step 4: Seed demo categories and articles, then manually verify in the browser**

Seed 3 categories (Institucional, Avisos e Campanhas, Sistemas & TI) and a handful of published articles with varied covers/categories/dates, then check logged in as the seeded Super Administrador:
- `/admin/mural` — create an article, confirm it starts as a draft; edit it; publish it; confirm `published_at` is set; unpublish and republish, confirm the date doesn't reset; delete one.
- `/mural` — confirm published articles show in the 3-column responsive grid, search/category/period filters work, liking toggles the heart/thumbs icon and count.
- `/mural/{article}` — "Saiba mais" opens the full article with its body rendered.
- Log in as a seeded Editor (or temporarily reassign a role) and confirm the Publicar/Excluir buttons don't even render on `/admin/mural` for that role (the `@can` directives), and that calling the underlying actions directly would 403 (already covered by the automated tests — this is just a sanity glance).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Run Pint and verify the Mural module end-to-end"
```
