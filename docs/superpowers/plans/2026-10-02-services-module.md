# Módulo Serviços Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the "Em construção" placeholder for Serviços with a real, working module: a `service_links` table, an admin screen to manage the catalog (create/edit/reorder/activate/delete), and a responsive public page matching the `servico.html` mockup.

**Architecture:** Follows the exact pattern already established by the Usuários module (Tasks 12/13 of the foundation plan): a Livewire `*Index` full-page component with a `*Form` child embedded in a modal via a dynamic `wire:key`, gated by a dedicated permission. A new, simple `ServiceLink` Eloquent model (no relations, no soft deletes) backs it — this is a catalog, not editorial content.

**Tech Stack:** Laravel 13, Livewire 4, Pest 4, Tailwind 4, spatie/laravel-permission (already installed, no new dependencies).

**Spec:** `docs/superpowers/specs/2026-10-02-services-module-design.md`

## Global Constraints

- No categories/grouping on service links — a single ordered list (per spec's explicit decision).
- No drag-and-drop reordering — up/down buttons swap `order` values. Do not add `@alpinejs/sort` or any new npm/composer dependency.
- Every service link URL is treated as external: links render with `target="_blank" rel="noopener"`.
- `services.manage` permission: granted to Super Administrador (via full inventory) and Administrador only. Gestor and Editor get no access, enforced by route middleware (`permission:services.manage`), not just by hiding the sidebar link.
- The public `/servicos` page requires only `auth` (no permission check) — matches `config/sidebar.php`'s existing `permission => null` for that item.
- Follow the exact edit-modal wiring pattern from `UserIndex`/`UserForm` (dynamic `wire:key` + `editingId` property) — the foundation's final review caught a real bug where an edit button was missing; do not repeat that gap here.

## Review Focus

- A service link with an invalid URL (not a valid absolute URL) must be rejected by validation, not silently saved and rendered as a broken/dangerous link.
- Moving the first item up (or the last item down) must be a no-op, not an error or an out-of-bounds order value.
- A Gestor or Editor hitting `/admin/servicos` directly by URL must get 403, not just fail to see the sidebar link.
- An inactive service link must never appear on the public `/servicos` page, even though it still appears in the admin list.
- The title/icon fields must be escaped on render (Blade's default `{{ }}` escaping) so a service link title containing HTML/script-like text can't inject into the page — this matters because the data entered here is admin-controlled today but the model itself has no server-side HTML-stripping, so the escaping is the only thing standing between this and XSS if an account is ever compromised.

---

## File Structure

```
app/
  Models/ServiceLink.php
  Livewire/
    Admin/Services/ServiceLinkIndex.php
    Admin/Services/ServiceLinkForm.php
    Services/ServicesIndex.php
database/
  factories/ServiceLinkFactory.php
  migrations/2026_10_02_000001_create_service_links_table.php
resources/views/livewire/
  admin/services/service-link-index.blade.php
  admin/services/service-link-form.blade.php
  services/services-index.blade.php
config/sidebar.php          (modified)
routes/web.php               (modified)
app/Services/PermissionManager.php   (modified)
database/seeders/AclSeeder.php       (modified)
tests/Feature/
  ServiceLinkModelTest.php
  Admin/ServiceLinkManagementTest.php
  ServicesPageTest.php
```

---

### Task 1: Data layer — migration, model, factory, permission

**Files:**
- Create: `database/migrations/2026_10_02_000001_create_service_links_table.php`, `app/Models/ServiceLink.php`, `database/factories/ServiceLinkFactory.php`
- Modify: `app/Services/PermissionManager.php`, `database/seeders/AclSeeder.php`
- Test: `tests/Feature/ServiceLinkModelTest.php`

**Interfaces:**
- Produces: `ServiceLink` model with `fillable = [title, icon, url, order, is_active]`, `is_active` cast to boolean. `services.manage` permission, granted to Super Administrador and Administrador.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceLinkModelTest.php`:

```php
<?php

use App\Models\ServiceLink;
use App\Services\PermissionManager;
use Database\Seeders\AclSeeder;
use Spatie\Permission\Models\Role;

test('a service link can be created with the expected fields', function () {
    $link = ServiceLink::create([
        'title' => 'Vade Meccum',
        'icon' => 'book-open',
        'url' => 'https://example.com/vade-meccum',
        'order' => 1,
        'is_active' => true,
    ]);

    expect($link->title)->toBe('Vade Meccum')
        ->and($link->is_active)->toBeTrue();
});

test('services.manage is in the permission inventory', function () {
    expect(PermissionManager::inventory())->toContain('services.manage');
});

test('Administrador role has services.manage after seeding', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Administrador')->first();

    expect($role->hasPermissionTo('services.manage'))->toBeTrue();
});

test('Gestor and Editor roles do not have services.manage', function () {
    $this->seed(AclSeeder::class);

    expect(Role::where('name', 'Gestor')->first()->hasPermissionTo('services.manage'))->toBeFalse()
        ->and(Role::where('name', 'Editor')->first()->hasPermissionTo('services.manage'))->toBeFalse();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ServiceLinkModelTest`
Expected: FAIL — `ServiceLink` class and `services.manage` permission don't exist yet.

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_10_02_000001_create_service_links_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_links', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('icon');
            $table->string('url');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_links');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/ServiceLink.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceLink extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'icon', 'url', 'order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 5: Create the factory**

Create `database/factories/ServiceLinkFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\ServiceLink;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceLinkFactory extends Factory
{
    protected $model = ServiceLink::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true),
            'icon' => 'desktop',
            'url' => fake()->url(),
            'order' => 0,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 6: Add the permission to the inventory**

Edit `app/Services/PermissionManager.php`, add `'services.manage',` as its own line after `'settings.manage',`:

```php
    public static function inventory(): array
    {
        return [
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
            'roles.view',
            'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
            'media.upload',
            'settings.manage',
            'services.manage',
        ];
    }
```

- [ ] **Step 7: Grant it to Administrador**

Edit `database/seeders/AclSeeder.php`, add `'services.manage',` to the `Administrador` array (after `'settings.manage',`):

```php
            'Administrador' => [
                'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
                'roles.view',
                'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
                'media.upload',
                'settings.manage',
                'services.manage',
            ],
```

(Super Administrador automatically gets it via `PermissionManager::inventory()`. Gestor and Editor arrays are untouched.)

- [ ] **Step 8: Run migrations and tests**

Run:
```bash
php artisan migrate --no-interaction
php artisan test --filter=ServiceLinkModelTest
```
Expected: all 4 tests PASS.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "Add ServiceLink model, migration, and services.manage permission"
```

---

### Task 2: Admin screen — list, create/edit modal, reorder

**Files:**
- Create: `app/Livewire/Admin/Services/ServiceLinkIndex.php`, `app/Livewire/Admin/Services/ServiceLinkForm.php`, `resources/views/livewire/admin/services/service-link-index.blade.php`, `resources/views/livewire/admin/services/service-link-form.blade.php`
- Modify: `routes/web.php`, `config/sidebar.php`
- Test: `tests/Feature/Admin/ServiceLinkManagementTest.php`

**Interfaces:**
- Consumes: `ServiceLink` model (Task 1), `<x-ui.data-table>`/`<x-ui.modal>`/`<x-ui.input>`/`<x-ui.checkbox>`/`<x-ui.button>` (already in the UI kit).
- Produces: route `admin.services.index` (GET, `auth`+`admin`+`permission:services.manage`), consistent with `admin.users.index`'s pattern.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/ServiceLinkManagementTest.php`:

```php
<?php

use App\Livewire\Admin\Services\ServiceLinkIndex;
use App\Models\ServiceLink;
use App\Models\User;
use Database\Seeders\AclSeeder;
use Livewire\Livewire;

function actingAsServicesAdmin(): User
{
    test()->seed(AclSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Administrador');
    test()->actingAs($admin);

    return $admin;
}

test('an Administrador can view the services management page', function () {
    actingAsServicesAdmin();

    $this->get(route('admin.services.index'))->assertStatus(200);
});

test('a Gestor is forbidden from the services management page', function () {
    $this->seed(AclSeeder::class);
    $gestor = User::factory()->create();
    $gestor->assignRole('Gestor');

    $this->actingAs($gestor)
        ->get(route('admin.services.index'))
        ->assertStatus(403);
});

test('an Editor is forbidden from the services management page', function () {
    $this->seed(AclSeeder::class);
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $this->actingAs($editor)
        ->get(route('admin.services.index'))
        ->assertStatus(403);
});

test('creating a service link with a valid url succeeds', function () {
    actingAsServicesAdmin();

    // ServiceLinkForm is a separate Livewire component embedded in
    // ServiceLinkIndex's modal via wire:key; exercise it directly here
    // to prove validation + persistence (ServiceLinkIndex itself has no
    // title/icon/url properties of its own to set).
    Livewire::test(\App\Livewire\Admin\Services\ServiceLinkForm::class)
        ->set('title', 'Vade Meccum')
        ->set('icon', 'book-open')
        ->set('url', 'https://example.com/vade-meccum')
        ->call('save')
        ->assertHasNoErrors();

    expect(ServiceLink::where('title', 'Vade Meccum')->exists())->toBeTrue();
});

test('creating a service link with an invalid url is rejected', function () {
    actingAsServicesAdmin();

    Livewire::test(\App\Livewire\Admin\Services\ServiceLinkForm::class)
        ->set('title', 'Link Quebrado')
        ->set('icon', 'link')
        ->set('url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['url']);

    expect(ServiceLink::where('title', 'Link Quebrado')->exists())->toBeFalse();
});

test('editing an existing service link updates it', function () {
    actingAsServicesAdmin();
    $link = ServiceLink::factory()->create(['title' => 'Nome Antigo']);

    Livewire::test(\App\Livewire\Admin\Services\ServiceLinkForm::class, ['link' => $link])
        ->set('title', 'Nome Novo')
        ->call('save')
        ->assertHasNoErrors();

    expect($link->refresh()->title)->toBe('Nome Novo');
});

test('moving a service link up swaps its order with the previous one', function () {
    actingAsServicesAdmin();
    $first = ServiceLink::factory()->create(['order' => 1]);
    $second = ServiceLink::factory()->create(['order' => 2]);

    Livewire::test(ServiceLinkIndex::class)->call('moveUp', $second->id);

    expect($first->refresh()->order)->toBe(2)
        ->and($second->refresh()->order)->toBe(1);
});

test('moving the first service link up does nothing', function () {
    actingAsServicesAdmin();
    $first = ServiceLink::factory()->create(['order' => 1]);

    Livewire::test(ServiceLinkIndex::class)->call('moveUp', $first->id);

    expect($first->refresh()->order)->toBe(1);
});

test('moving the last service link down does nothing', function () {
    actingAsServicesAdmin();
    $last = ServiceLink::factory()->create(['order' => 1]);

    Livewire::test(ServiceLinkIndex::class)->call('moveDown', $last->id);

    expect($last->refresh()->order)->toBe(1);
});

test('deleting a service link removes it', function () {
    actingAsServicesAdmin();
    $link = ServiceLink::factory()->create();

    Livewire::test(ServiceLinkIndex::class)->call('delete', $link->id);

    expect(ServiceLink::find($link->id))->toBeNull();
});

test('toggling active status flips it', function () {
    actingAsServicesAdmin();
    $link = ServiceLink::factory()->create(['is_active' => true]);

    Livewire::test(ServiceLinkIndex::class)->call('toggleActive', $link->id);

    expect($link->refresh()->is_active)->toBeFalse();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ServiceLinkManagementTest`
Expected: FAIL — none of the classes/routes exist yet.

- [ ] **Step 3: Create the ServiceLinkIndex component**

Create `app/Livewire/Admin/Services/ServiceLinkIndex.php`:

```php
<?php

namespace App\Livewire\Admin\Services;

use App\Models\ServiceLink;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceLinkIndex extends Component
{
    public ?int $editingLinkId = null;

    #[On('service-link-saved')]
    public function refreshList(): void
    {
        $this->editingLinkId = null;
    }

    public function edit(int $linkId): void
    {
        $this->editingLinkId = $linkId ?: null;
    }

    public function closeForm(): void
    {
        $this->editingLinkId = null;
    }

    public function delete(int $linkId): void
    {
        ServiceLink::findOrFail($linkId)->delete();
    }

    public function toggleActive(int $linkId): void
    {
        $link = ServiceLink::findOrFail($linkId);
        $link->update(['is_active' => ! $link->is_active]);
    }

    public function moveUp(int $linkId): void
    {
        $this->swapOrder($linkId, 'up');
    }

    public function moveDown(int $linkId): void
    {
        $this->swapOrder($linkId, 'down');
    }

    private function swapOrder(int $linkId, string $direction): void
    {
        $current = ServiceLink::findOrFail($linkId);

        $neighbor = $direction === 'up'
            ? ServiceLink::where('order', '<', $current->order)->orderByDesc('order')->first()
            : ServiceLink::where('order', '>', $current->order)->orderBy('order')->first();

        if (! $neighbor) {
            return;
        }

        [$currentOrder, $neighborOrder] = [$current->order, $neighbor->order];

        $current->update(['order' => $neighborOrder]);
        $neighbor->update(['order' => $currentOrder]);
    }

    public function render()
    {
        return view('livewire.admin.services.service-link-index', [
            'links' => ServiceLink::orderBy('order')->get(),
            'editingLink' => $this->editingLinkId ? ServiceLink::find($this->editingLinkId) : null,
        ]);
    }
}
```

Note: `edit(0)` (called from a "Novo serviço" trigger with no id) sets `editingLinkId` to `null` via `$linkId ?: null`, which is how the test in Step 1 opens a blank form — mirrors `UserIndex`'s `closeForm()` pattern but keeps a single `edit()` entry point for both "new" and "edit this row".

- [ ] **Step 4: Create the ServiceLinkForm component**

Create `app/Livewire/Admin/Services/ServiceLinkForm.php`:

```php
<?php

namespace App\Livewire\Admin\Services;

use App\Models\ServiceLink;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ServiceLinkForm extends Component
{
    public ?ServiceLink $link = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|regex:/^[a-z0-9-]+$/')]
    public string $icon = '';

    #[Validate('required|url|max:2048')]
    public string $url = '';

    public bool $isActive = true;

    public function mount(?ServiceLink $link = null): void
    {
        if ($link) {
            $this->link = $link;
            $this->title = $link->title;
            $this->icon = $link->icon;
            $this->url = $link->url;
            $this->isActive = $link->is_active;
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'icon' => $this->icon,
            'url' => $this->url,
            'is_active' => $this->isActive,
        ];

        if ($this->link) {
            $this->link->update($data);
        } else {
            $data['order'] = (ServiceLink::max('order') ?? 0) + 1;
            ServiceLink::create($data);
        }

        $this->dispatch('service-link-saved');
        $this->reset(['title', 'icon', 'url']);
        $this->isActive = true;
    }

    public function render()
    {
        return view('livewire.admin.services.service-link-form');
    }
}
```

- [ ] **Step 5: Create the index view**

Create `resources/views/livewire/admin/services/service-link-index.blade.php`:

```blade
<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800">Serviços</h2>
        <div>
            <x-ui.button wire:click="closeForm" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'service-link-form' }))">
                Novo serviço
            </x-ui.button>
        </div>
    </div>

    <x-ui.data-table>
        <x-slot:header>
            <th class="px-6 py-3">Ícone</th>
            <th class="px-6 py-3">Título</th>
            <th class="px-6 py-3">URL</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Ações</th>
        </x-slot:header>

        @foreach ($links as $link)
            <tr>
                <td class="px-6 py-3"><i class="fa-solid fa-{{ $link->icon }} text-emerald-800"></i></td>
                <td class="px-6 py-3">{{ $link->title }}</td>
                <td class="px-6 py-3 text-slate-500 truncate max-w-xs">{{ $link->url }}</td>
                <td class="px-6 py-3">{{ $link->is_active ? 'Ativo' : 'Inativo' }}</td>
                <td class="px-6 py-3 space-x-2 whitespace-nowrap">
                    <button wire:click="moveUp({{ $link->id }})" class="text-sm text-slate-500 hover:underline">
                        <i class="fa-solid fa-arrow-up"></i>
                    </button>
                    <button wire:click="moveDown({{ $link->id }})" class="text-sm text-slate-500 hover:underline">
                        <i class="fa-solid fa-arrow-down"></i>
                    </button>
                    <button wire:click="edit({{ $link->id }})" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'service-link-form' }))" class="text-sm text-emerald-700 hover:underline">
                        Editar
                    </button>
                    <button wire:click="toggleActive({{ $link->id }})" class="text-sm text-amber-700 hover:underline">
                        {{ $link->is_active ? 'Desativar' : 'Ativar' }}
                    </button>
                    <button wire:click="delete({{ $link->id }})" wire:confirm="Confirma a exclusão deste serviço?" class="text-sm text-red-700 hover:underline">
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    <x-ui.modal name="service-link-form" maxWidth="lg">
        <x-slot:header>Serviço</x-slot:header>
        <livewire:admin.services.service-link-form :link="$editingLink" :key="'service-link-form-'.($editingLinkId ?? 'new')" />
    </x-ui.modal>
</div>
```

- [ ] **Step 6: Create the form view**

Create `resources/views/livewire/admin/services/service-link-form.blade.php`:

```blade
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="title" label="Título" />
    <x-ui.input wire:model="icon" label="Ícone (Font Awesome)" hint="Nome do ícone sem o prefixo, ex: book-open" />
    <x-ui.input wire:model="url" type="url" label="URL" />
    <x-ui.checkbox wire:model="isActive" label="Ativo" />

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
```

- [ ] **Step 7: Add the admin route**

Edit `routes/web.php`, add inside the existing `Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(...)` block, alongside the `users.view` group:

```php
    Route::middleware('permission:services.manage')->group(function () {
        Route::get('/servicos', \App\Livewire\Admin\Services\ServiceLinkIndex::class)->name('services.index');
    });
```

- [ ] **Step 8: Add the sidebar entry**

Edit `config/sidebar.php`, add to the `ADMINISTRAÇÃO` group's `items` array (after the "Usuários" entry):

```php
            ['label' => 'Gerenciar Serviços', 'icon' => 'sliders', 'uri' => '/admin/servicos', 'route' => 'admin.services.index', 'permission' => 'services.manage'],
```

- [ ] **Step 9: Run the tests**

Run: `php artisan test --filter=ServiceLinkManagementTest`
Expected: all 10 tests PASS.

- [ ] **Step 10: Run the full suite**

Run: `php artisan test`
Expected: all tests pass, no regressions (this also confirms the new sidebar entry doesn't break `SidebarTest`, since that test uses a synthetic config override and doesn't read the real `config/sidebar.php`).

- [ ] **Step 11: Commit**

```bash
git add -A
git commit -m "Add admin screen for managing service links"
```

---

### Task 3: Public page — responsive services directory

**Files:**
- Create: `app/Livewire/Services/ServicesIndex.php`, `resources/views/livewire/services/services-index.blade.php`
- Modify: `routes/web.php`, `config/sidebar.php`
- Test: `tests/Feature/ServicesPageTest.php`

**Interfaces:**
- Consumes: `ServiceLink` model (Task 1).
- Produces: route `services.index` (GET, `auth` only), replacing `placeholder.servicos`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ServicesPageTest.php`:

```php
<?php

use App\Models\ServiceLink;
use App\Models\User;

test('a guest is redirected to login from the services page', function () {
    $this->get('/servicos')->assertRedirect(route('login'));
});

test('an authenticated user sees active service links in order', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => 'Primeiro', 'order' => 1, 'is_active' => true]);
    ServiceLink::factory()->create(['title' => 'Segundo', 'order' => 2, 'is_active' => true]);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertStatus(200)
        ->assertSeeInOrder(['Primeiro', 'Segundo']);
});

test('an inactive service link does not appear on the public page', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => 'Visível', 'is_active' => true]);
    ServiceLink::factory()->create(['title' => 'Escondido', 'is_active' => false]);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertSee('Visível')->assertDontSee('Escondido');
});

test('service link renders as a new-tab external link', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => 'Teste', 'url' => 'https://exemplo.gov.br']);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertSee('target="_blank"', false)
        ->assertSee('https://exemplo.gov.br', false);
});

test('a service link title is escaped in the rendered page', function () {
    $user = User::factory()->create();
    ServiceLink::factory()->create(['title' => '<script>alert(1)</script>']);

    $response = $this->actingAs($user)->get('/servicos');

    $response->assertDontSee('<script>alert(1)</script>', false);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ServicesPageTest`
Expected: FAIL — route doesn't exist yet (still resolves to the placeholder).

- [ ] **Step 3: Create the public Livewire component**

Create `app/Livewire/Services/ServicesIndex.php`:

```php
<?php

namespace App\Livewire\Services;

use App\Models\ServiceLink;
use Livewire\Component;

class ServicesIndex extends Component
{
    public function render()
    {
        return view('livewire.services.services-index', [
            'links' => ServiceLink::where('is_active', true)->orderBy('order')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Create the public view**

Create `resources/views/livewire/services/services-index.blade.php`, structure derived from `servico.html`:

```blade
<div class="max-w-7xl mx-auto space-y-8">
    <div class="flex items-center">
        <div class="bg-dpemt-dark text-white text-xs font-extrabold uppercase px-4 py-1.5 rounded-full shadow-sm tracking-wider flex items-center gap-2" style="background-color: #0C4A34;">
            <i class="fa-solid fa-sliders text-xs"></i>
            <span>SERVIÇOS</span>
        </div>
    </div>

    @if ($links->isEmpty())
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-500">
            Nenhum serviço cadastrado por enquanto.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-8">
            @foreach ($links as $link)
                <a href="{{ $link->url }}" target="_blank" rel="noopener" class="flex items-center gap-4 p-3 rounded-2xl bg-white border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-500/50 hover:bg-emerald-50/30 transition group">
                    <div class="w-14 h-14 rounded-full border-2 border-emerald-800 flex items-center justify-center shrink-0 bg-white group-hover:scale-105 transition">
                        <i class="fa-solid fa-{{ $link->icon }} text-xl text-emerald-800"></i>
                    </div>
                    <span class="font-extrabold text-sm text-slate-800 leading-snug group-hover:text-emerald-800 transition">
                        {{ $link->title }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div>
```

- [ ] **Step 5: Replace the placeholder route**

Edit `routes/web.php` — add the real route right after the `/dashboard` route definition (before the placeholder loop):

```php
Route::get('/servicos', \App\Livewire\Services\ServicesIndex::class)
    ->middleware('auth')
    ->name('services.index');
```

- [ ] **Step 6: Update the sidebar config**

Edit `config/sidebar.php`, change the CONTEÚDO group's "Serviços" entry's `route` from `placeholder.servicos` to `services.index` (keep `uri` and `permission` as they are):

```php
            ['label' => 'Serviços', 'icon' => 'sliders', 'uri' => '/servicos', 'route' => 'services.index', 'permission' => null],
```

Because this item's `route` no longer starts with `placeholder.`, the placeholder-registration loop in `routes/web.php` will automatically skip it — no change needed to that loop.

- [ ] **Step 7: Run the tests**

Run: `php artisan test --filter=ServicesPageTest`
Expected: all 5 PASS.

- [ ] **Step 8: Run the full suite**

Run: `php artisan test`
Expected: all tests pass. Specifically re-check `PlaceholderRoutesTest` — it iterates `config('sidebar')` and only tests items whose route starts with `placeholder.`, so removing `placeholder.servicos` shrinks that test's loop by one item without needing any edit to the test itself.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "Add the public Serviços page, replacing its placeholder"
```

---

### Task 4: Final polish and manual verification

**Files:**
- No new files — formatting, full-suite verification, and a real browser check.

- [ ] **Step 1: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 2: Run the entire test suite**

Run: `php artisan test`
Expected: all tests pass, zero failures, zero risky.

- [ ] **Step 3: Build frontend assets**

Run: `npm run build`

- [ ] **Step 4: Seed and manually verify in the browser**

Run `php artisan db:seed` (idempotent) if the local database doesn't already have the AclSeeder's roles, then start the app (`php artisan serve` + the app already has built assets) and manually check, logged in as the seeded Super Administrador:
- `/admin/servicos` — create 2-3 service links (reuse a few titles from `servico.html` for realism, e.g. "Vade Meccum", "Protocolo SEI"), confirm they appear, reorder them with the up/down buttons, edit one, deactivate one, delete one.
- `/servicos` — confirm the active links appear in order, the deactivated one does not, each opens in a new tab, and the grid is responsive (resize the window / check at mobile width — 1 column, tablet — 2 columns, desktop — 4 columns, matching the Tailwind breakpoints in the view).
- Confirm the sidebar's "Serviços" link under CONTEÚDO goes to the new real page, and "Gerenciar Serviços" appears under ADMINISTRAÇÃO only when logged in as Super Administrador/Administrador (not as Gestor/Editor, if you have time to check with a second seeded user).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Run Pint and verify the Serviços module end-to-end"
```
