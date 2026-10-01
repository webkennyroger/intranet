# Intranet DPEMT — Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up a new, independent Laravel 13 + Livewire 4 + Pest 4 application for the DPEMT intranet, with the DPEMT UI kit/editor ported in, 4-role RBAC (Super Administrador/Administrador/Gestor/Editor), a header+sidebar shell matching the `home.html` mockup, and a user management screen — with every content section (Mural, Serviços, etc.) left as a placeholder route for future sub-projects.

**Architecture:** Fresh Laravel skeleton scaffolded into this directory (preserving the existing mockups/docs), with Livewire 4 for all interactivity, `spatie/laravel-permission` for roles, a static permission-filtered config array driving the sidebar nav (no DB-driven menu builder), and DPEMT's generic Blade UI kit + Tiptap editor component copied in verbatim as a component library.

**Tech Stack:** PHP 8.5, Laravel `^13.8`, `livewire/livewire` `^4.4`, `spatie/laravel-permission` `^8.3`, `pestphp/pest` `^4.7` + `pestphp/pest-plugin-laravel` `^4.1`, Tailwind `^4.0` (`@tailwindcss/vite`), SQLite (dev), `laravel/boost` `^2.7` (dev tooling).

**Spec:** `docs/superpowers/specs/2026-10-01-intranet-foundation-design.md`

## Global Constraints

- Dependency versions are pinned to match DPEMT exactly: `laravel/framework:^13.8`, `livewire/livewire:^4.4`, `spatie/laravel-permission:^8.3`, `pestphp/pest:^4.7`, `pestphp/pest-plugin-laravel:^4.1`, `tailwindcss:^4.0`, `@tailwindcss/vite:^4.0`.
- This is a brand-new, independent application: its own SQLite/MySQL database, no shared tables, no SSO, no runtime dependency on DPEMT. DPEMT is only a source of code to copy.
- Exactly 4 roles exist: **Super Administrador**, **Administrador**, **Gestor**, **Editor**. No other roles are seeded.
- No module/plugin/template/extension installation system or permissions (`modules.manage`, `plugins.manage`, `templates.manage`, `components.manage` do not exist in this app's permission inventory).
- The sidebar nav is driven by a static `config/sidebar.php` array filtered by `can()`, not a database-backed menu builder.
- No self-registration or registration-approval workflow — users are created only through the admin user management screen built in this plan.
- Content models (Article, Category, Workflow, etc.) are explicitly out of scope — every content-section link routes to a shared placeholder view.

## Review Focus

- A request to delete, block, or demote the sole remaining Super Administrador must be rejected — losing the last Super Administrador would lock everyone out of user management.
- Creating or editing a user with role Gestor but no department/section selected should be rejected — an unscoped Gestor has no defined content boundary once content sub-projects enforce scoping.
- A login attempt by a user whose `status` is `blocked` must be rejected with a clear message even though the password is correct — a correct password must never be sufficient on its own.
- Hitting an admin-only URL directly (e.g. `/admin/usuarios`) as a Gestor or Editor must return 403 — hiding the sidebar link is not access control.
- An Administrador trying to block or delete their own account must be rejected — otherwise an admin can accidentally lock themselves out with no one else to undo it.

---

## File Structure

```
app/
  Enums/UserStatus.php
  Http/Middleware/AdminMiddleware.php
  Http/Middleware/PermissionMiddleware.php
  Livewire/
    Auth/Login.php
    Admin/Users/UserIndex.php
    Admin/Users/UserForm.php
  Models/User.php
  Services/PermissionManager.php
bootstrap/app.php                              (modified: middleware aliases)
config/sidebar.php
database/
  migrations/0001_01_01_000000_create_users_table.php   (modified: profile columns)
  seeders/AclSeeder.php
  seeders/DatabaseSeeder.php                   (modified: call AclSeeder)
resources/
  css/app.css
  views/
    components/
      ui/*.blade.php                            (copied from DPEMT)
      editor/tiptap.blade.php                   (copied from DPEMT)
      layout/header.blade.php
      layout/sidebar.blade.php
    layouts/app.blade.php
    layouts/guest.blade.php
    livewire/
      auth/login.blade.php
      admin/users/user-index.blade.php
      admin/users/user-form.blade.php
    dashboard.blade.php
    coming-soon.blade.php
routes/web.php                                   (modified: auth, dashboard, admin, placeholders)
vite.config.js                                    (modified: tailwindcss plugin)
tests/
  Feature/ExampleTest.php
  Feature/UserModelTest.php
  Feature/AclSeederTest.php
  Feature/ComponentsSmokeTest.php
  Feature/EditorComponentTest.php
  Feature/Auth/LoginTest.php
  Feature/DashboardTest.php
  Feature/SidebarTest.php
  Feature/PlaceholderRoutesTest.php
  Feature/Admin/UserManagementTest.php
  Feature/Admin/UserFormTest.php
```

---

### Task 1: Scaffold the Laravel project in the existing directory

**Files:**
- Create: entire Laravel skeleton (via `composer create-project`), merged into the existing `C:\Users\Defensoria\Herd\intranet` directory
- Preserve: `home.html`, `mural.html`, `servico.html`, `docs/`, `.agents/`, `.claude/`, `.codex/`, `.superpowers/`, `.gitignore` (already committed)

**Interfaces:**
- Produces: a bootable Laravel 13 app at the repo root, with `artisan`, `composer.json`, `.env`, SQLite database configured.

- [ ] **Step 1: Verify local toolchain**

Run:
```bash
php -v
composer -V
node -v
npm -v
```
Expected: PHP 8.5.x, Composer 2.x, Node 18+, npm 9+. If any command is not found, stop and ask the user — Herd should have these on `PATH` already (confirmed present in the sibling DPEMT project).

- [ ] **Step 2: Confirm the working tree has nothing uncommitted before scaffolding**

Run: `git status`
Expected: clean (the spec commit from the previous session is the only commit, `home.html`/`mural.html`/`servico.html`/doc folders untracked or already committed — check and commit anything untracked that isn't scaffolding noise before proceeding).

- [ ] **Step 3: Scaffold Laravel into a temporary subdirectory**

Run:
```bash
composer create-project laravel/laravel _laravel_scaffold "13.8.*" --prefer-dist --no-interaction
```
Expected: `_laravel_scaffold/` is created with a full Laravel skeleton (`app/`, `routes/`, `resources/`, `artisan`, `composer.json`, etc.) and exits 0.

- [ ] **Step 4: Merge the scaffold into the project root**

Run:
```bash
cp -a _laravel_scaffold/. ./
rm -rf _laravel_scaffold
```
Expected: `ls` at the root now shows `app/`, `artisan`, `bootstrap/`, `composer.json`, `config/`, `database/`, `public/` (now populated with `index.php`), `resources/`, `routes/`, `storage/`, `tests/`, alongside the pre-existing `home.html`, `mural.html`, `servico.html`, `docs/`. Run `git status` and confirm none of the preserved files were overwritten (they have no name collisions with the skeleton).

- [ ] **Step 5: Configure the environment and database**

Run:
```bash
cp .env.example .env
php artisan key:generate --ansi --no-interaction
touch database/database.sqlite
```
Edit `.env`: set `DB_CONNECTION=sqlite` and remove/comment the `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` lines (SQLite needs none of them — Laravel resolves the path from `database/database.sqlite` by convention).

- [ ] **Step 6: Boot the application and run its default migration**

Run:
```bash
php artisan migrate --no-interaction
php artisan about
```
Expected: `migrate` creates the default `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs` tables with no errors; `about` prints the Laravel/PHP versions and environment without exceptions.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Scaffold Laravel 13 application"
```

---

### Task 2: Install Livewire 4 and the Tailwind 4 build pipeline

**Files:**
- Modify: `composer.json`, `package.json`, `vite.config.js`
- Create: `resources/css/app.css`

**Interfaces:**
- Produces: `@vite(['resources/css/app.css'])`, `@livewireStyles`, `@livewireScripts` directives usable from any Blade layout written in later tasks.

- [ ] **Step 1: Install Livewire**

Run: `composer require livewire/livewire:^4.4`
Expected: exits 0, `livewire/livewire` appears in `composer.json` `require`.

- [ ] **Step 2: Install the Tailwind 4 Vite plugin**

Run: `npm install -D tailwindcss@^4.0 @tailwindcss/vite@^4.0 @tailwindcss/typography@^0.5`
Expected: exits 0, packages appear in `package.json` `devDependencies`.

- [ ] **Step 3: Wire Tailwind into Vite**

Replace the contents of `vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

- [ ] **Step 4: Write the theme stylesheet**

Create `resources/css/app.css` (ported from DPEMT's theme, trimmed of page-builder/popup-builder rules that don't apply here):

```css
@import 'tailwindcss';
@plugin "@tailwindcss/typography";

@custom-variant dark (&:not(.light, .light *));

@source '../views/**/*.blade.php';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
        'Segoe UI Symbol', 'Noto Color Emoji';

    --color-primary-50: #ecfdf5;
    --color-primary-100: #d1fae5;
    --color-primary-200: #a7f3d0;
    --color-primary-300: #6ee7b7;
    --color-primary-400: #34d399;
    --color-primary-500: #10b981;
    --color-primary-600: #059669;
    --color-primary-700: #047857;
    --color-primary-800: #065f46;
    --color-primary-900: #064e3b;
    --color-primary-950: #022c22;
}

:root {
    --bg-base: hsl(160 49% 8%);
    --bg-surface: hsl(160 47% 11%);
    --bg-elevated: hsl(160 33% 17%);
    --bg-hover: hsl(160 33% 17% / 0.6);
    --bg-card: hsl(160 47% 11% / 0.6);
    --bg-input: hsl(160 33% 17% / 0.5);
    --text-primary: hsl(160 40% 96%);
    --text-secondary: hsl(160 20% 65%);
    --text-muted: hsl(160 16% 47%);
    --border-color: hsl(160 25% 27% / 0.8);
    --border-subtle: hsl(160 25% 27% / 0.5);
    --scrollbar-thumb: hsl(160 25% 27%);
    --scrollbar-hover: hsl(160 19% 35%);
}

.light {
    --bg-base: #f8fafc;
    --bg-surface: #ffffff;
    --bg-elevated: #f1f5f9;
    --bg-hover: rgba(241, 245, 249, 0.8);
    --bg-card: rgba(255, 255, 255, 0.8);
    --bg-input: rgba(241, 245, 249, 0.8);
    --text-primary: #0f172a;
    --text-secondary: #475569;
    --text-muted: #94a3b8;
    --border-color: rgba(203, 213, 225, 0.8);
    --border-subtle: rgba(203, 213, 225, 0.5);
    --scrollbar-thumb: #cbd5e1;
    --scrollbar-hover: #94a3b8;
}

html, body {
    background-color: var(--bg-base);
    color: var(--text-primary);
}

[x-cloak] {
    display: none !important;
}

::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: var(--scrollbar-hover); }

:focus-visible {
    outline: 3px solid #059669;
    outline-offset: 2px;
}
```

- [ ] **Step 5: Build the frontend assets**

Run: `npm run build`
Expected: exits 0, produces `public/build/manifest.json`.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add Livewire 4 and Tailwind 4 build pipeline"
```

---

### Task 3: Install Pest 4 and switch the default test suite over

**Files:**
- Modify: `composer.json`, `phpunit.xml`, `tests/Pest.php`
- Delete: `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` (PHPUnit-style, superseded by Pest)
- Create: `tests/Feature/ExampleTest.php` (Pest-style)

**Interfaces:**
- Produces: `pest()`/`it()`/`test()` global functions usable by every later task's tests; `RefreshDatabase` applied automatically to every test under `tests/Feature`.

- [ ] **Step 1: Install Pest**

Run:
```bash
composer require pestphp/pest:^4.7 pestphp/pest-plugin-laravel:^4.1 --dev
php artisan pest:install --no-interaction
```
Expected: exits 0, creates `tests/Pest.php`, updates `phpunit.xml` to use Pest's bootstrap.

- [ ] **Step 2: Apply RefreshDatabase to all Feature tests**

Edit `tests/Pest.php`, ensure it contains:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->in('Feature');
```

- [ ] **Step 3: Replace the default example tests**

Run:
```bash
rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
```

Create `tests/Feature/ExampleTest.php`:

```php
<?php

test('the health endpoint responds successfully', function () {
    $response = $this->get('/up');

    $response->assertStatus(200);
});
```

- [ ] **Step 4: Run the suite**

Run: `php artisan test`
Expected: `1 passed`.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Install Pest 4 and switch the default test suite"
```

---

### Task 4: Install spatie/laravel-permission and extend the User model

**Files:**
- Modify: `composer.json`, `database/migrations/0001_01_01_000000_create_users_table.php`, `app/Models/User.php`
- Create: `app/Enums/UserStatus.php`, `database/migrations/xxxx_create_permission_tables.php` (published by the package)
- Test: `tests/Feature/UserModelTest.php`

**Interfaces:**
- Produces: `App\Enums\UserStatus` (cases `Active`, `Blocked`), `User::isActive(): bool`, `User::isSuperAdmin(): bool`, `User::superAdministratorCount(): int`, `User` has `HasRoles` (so `->assignRole()`, `->hasRole()`, `->can()` work).

- [ ] **Step 1: Install the package and publish its migration**

Run:
```bash
composer require spatie/laravel-permission:^8.3
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction
```
Expected: creates `database/migrations/xxxx_xx_xx_xxxxxx_create_permission_tables.php` and `config/permission.php`.

- [ ] **Step 2: Add profile columns to the users migration**

Edit `database/migrations/0001_01_01_000000_create_users_table.php` — this hasn't run against any shared data yet (`Task 1` only migrated a plain default `users` table in this fresh project), so columns are added directly instead of via a separate migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('status')->default('active');
            $table->string('department')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
```

- [ ] **Step 3: Write the failing test**

Create `tests/Feature/UserModelTest.php`:

```php
<?php

use App\Enums\UserStatus;
use App\Models\User;

test('a user with status active isActive returns true', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    expect($user->isActive())->toBeTrue();
});

test('a user with status blocked isActive returns false', function () {
    $user = User::factory()->create(['status' => UserStatus::Blocked]);

    expect($user->isActive())->toBeFalse();
});

test('isSuperAdmin is true only for users holding the Super Administrador role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $editor = User::factory()->create();

    expect($admin->isSuperAdmin())->toBeTrue()
        ->and($editor->isSuperAdmin())->toBeFalse();
});

test('superAdministratorCount reflects how many users hold the role', function () {
    expect(User::superAdministratorCount())->toBe(0);

    User::factory()->create()->assignRole('Super Administrador');

    expect(User::superAdministratorCount())->toBe(1);
});
```

- [ ] **Step 4: Run the tests to verify they fail**

Run: `php artisan test --filter=UserModelTest`
Expected: FAIL — `UserStatus` class and `User::isActive()`/`isSuperAdmin()`/`superAdministratorCount()` don't exist yet, and the `Spatie\Permission\Traits\HasRoles` trait/role tables aren't wired up.

- [ ] **Step 5: Create the status enum**

Create `app/Enums/UserStatus.php`:

```php
<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Blocked => 'Bloqueado',
        };
    }
}
```

- [ ] **Step 6: Update the User model**

Replace `app/Models/User.php`:

```php
<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'status', 'department',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Administrador');
    }

    public static function superAdministratorCount(): int
    {
        return static::role('Super Administrador')->count();
    }

    public function getAvatarInitials(): string
    {
        return strtoupper(mb_substr($this->name, 0, 2));
    }
}
```

- [ ] **Step 7: Run the migrations and tests**

Run:
```bash
php artisan migrate:fresh --no-interaction
php artisan test --filter=UserModelTest
```
Expected: all 4 tests PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Install spatie/laravel-permission and extend the User model"
```

---

### Task 5: Seed roles and permissions (AclSeeder)

**Files:**
- Create: `app/Services/PermissionManager.php`, `database/seeders/AclSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/AclSeederTest.php`

**Interfaces:**
- Consumes: `User::fillable` (`name`, `username`, `email`, `password`, `status`, `department`) from Task 4.
- Produces: `App\Services\PermissionManager::inventory(): array<string>` and `::ensureAll(): void`, consumed by the `AclSeeder`. Seeded roles: `Super Administrador`, `Administrador`, `Gestor`, `Editor`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AclSeederTest.php`:

```php
<?php

use App\Models\User;
use Database\Seeders\AclSeeder;
use Spatie\Permission\Models\Role;

test('AclSeeder creates exactly the four intranet roles', function () {
    $this->seed(AclSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['Administrador', 'Editor', 'Gestor', 'Super Administrador']);
});

test('Super Administrador role has every permission in the inventory', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Super Administrador')->first();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(App\Services\PermissionManager::inventory())->sort()->values()->all());
});

test('Editor role cannot publish content', function () {
    $this->seed(AclSeeder::class);

    $role = Role::where('name', 'Editor')->first();

    expect($role->hasPermissionTo('content.create'))->toBeTrue()
        ->and($role->hasPermissionTo('content.publish'))->toBeFalse();
});

test('AclSeeder creates a default Super Administrador user', function () {
    $this->seed(AclSeeder::class);

    $admin = User::where('email', 'admin@defensoria.am.gov.br')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isSuperAdmin())->toBeTrue();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=AclSeederTest`
Expected: FAIL — `AclSeeder` and `PermissionManager` don't exist yet.

- [ ] **Step 3: Create the permission inventory**

Create `app/Services/PermissionManager.php`:

```php
<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

class PermissionManager
{
    public static function inventory(): array
    {
        return [
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
            'roles.view',
            'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
            'media.upload',
            'settings.manage',
        ];
    }

    public function ensureAll(): void
    {
        foreach (self::inventory() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
```

- [ ] **Step 4: Create the ACL seeder**

Create `database/seeders/AclSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\PermissionManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AclSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        (new PermissionManager)->ensureAll();

        $roleDefinitions = [
            'Super Administrador' => PermissionManager::inventory(),

            'Administrador' => [
                'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
                'roles.view',
                'content.view', 'content.create', 'content.update', 'content.delete', 'content.publish',
                'media.upload',
                'settings.manage',
            ],

            'Gestor' => [
                'content.view', 'content.create', 'content.update', 'content.publish',
                'media.upload',
            ],

            'Editor' => [
                'content.view', 'content.create', 'content.update',
                'media.upload',
            ],
        ];

        foreach ($roleDefinitions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }

        $admin = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@defensoria.am.gov.br'],
            [
                'name' => 'Super Administrador',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $admin->syncRoles(['Super Administrador']);
    }
}
```

- [ ] **Step 5: Call the seeder from DatabaseSeeder**

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AclSeeder::class);
    }
}
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --filter=AclSeederTest`
Expected: all 4 tests PASS.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Seed the 4 intranet roles and their permissions"
```

---

### Task 6: Port the DPEMT UI component kit

**Files:**
- Create: `resources/views/components/ui/*.blade.php` (copied from DPEMT)
- Test: `tests/Feature/ComponentsSmokeTest.php`

**Interfaces:**
- Produces: `<x-ui.button>`, `<x-ui.input>`, `<x-ui.select>`, `<x-ui.modal>`, `<x-ui.data-table>`, `<x-ui.checkbox>`, `<x-ui.badge>`, `<x-ui.icon>`, and the rest of the kit, usable by every later task.

- [ ] **Step 1: Copy the component directory**

Run:
```bash
mkdir -p resources/views/components/ui
cp "/c/Users/Defensoria/Herd/DPEMT/resources/views/components/ui/"*.blade.php resources/views/components/ui/
```
Expected: `resources/views/components/ui/` now contains the same ~50 files as DPEMT's (accordion, alert, avatar, badge, button, card, checkbox, data-table, dropdown, field, icon, input, modal, pagination, select, tabs, textarea, toast, tooltip, etc.).

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/ComponentsSmokeTest.php`:

```php
<?php

test('the button component renders its slot', function () {
    $html = Blade::render('<x-ui.button>Salvar</x-ui.button>');

    expect($html)->toContain('Salvar');
});

test('the input component renders a label', function () {
    $html = Blade::render('<x-ui.input label="Nome" name="name" />');

    expect($html)->toContain('Nome');
});

test('the modal component renders its named slot content', function () {
    $html = Blade::render('<x-ui.modal name="demo"><x-slot:header>Título</x-slot:header>Corpo</x-ui.modal>');

    expect($html)->toContain('Título')->toContain('Corpo');
});

test('the data-table component renders its header and body slots', function () {
    $html = Blade::render('<x-ui.data-table><x-slot:header><th>Nome</th></x-slot:header><tr><td>Ana</td></tr></x-ui.data-table>');

    expect($html)->toContain('Nome')->toContain('Ana');
});
```

Add the import at the top of the file: `use Illuminate\Support\Facades\Blade;`.

- [ ] **Step 3: Run the tests to verify they fail**

Run: `php artisan test --filter=ComponentsSmokeTest`
Expected: FAIL — `View [ui.button] not found` (or similar) until Step 1's copy is actually in place; if Step 1 already ran, these should already pass — if any component references something DPEMT-specific (e.g. a `settings()` helper or `config('cms.*')`) and errors, note the offending component and strip that reference before re-running.

- [ ] **Step 4: Run the tests again and fix any DPEMT-specific references**

Run: `php artisan test --filter=ComponentsSmokeTest`
Expected: all 4 tests PASS. If a component fails because it calls a helper that doesn't exist in this app (grep the `ui/` directory for `settings(`, `config('cms` to check), remove or stub that specific call in that file only — do not change the others preemptively.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Port the DPEMT UI component kit"
```

---

### Task 7: Port the Tiptap editor component

**Files:**
- Create: `resources/views/components/editor/tiptap.blade.php` (copied from DPEMT)
- Test: `tests/Feature/EditorComponentTest.php`

**Interfaces:**
- Produces: `<x-editor.tiptap name="content" value="..." placeholder="..." height="400px" />`, available to future content sub-projects.

- [ ] **Step 1: Install the Tiptap packages**

Run:
```bash
npm install @tiptap/core@^2.11 @tiptap/starter-kit@^2.11 @tiptap/extension-color@^2.11 @tiptap/extension-highlight@^2.11 @tiptap/extension-image@^2.11 @tiptap/extension-link@^2.11 @tiptap/extension-placeholder@^2.11 @tiptap/extension-table@^2.11 @tiptap/extension-table-cell@^2.11 @tiptap/extension-table-header@^2.11 @tiptap/extension-table-row@^2.11 @tiptap/extension-text-align@^2.11 @tiptap/extension-text-style@^2.11 @tiptap/extension-underline@^2.11
```
Expected: exits 0, packages added to `package.json` `dependencies`.

- [ ] **Step 2: Copy the component**

Run:
```bash
mkdir -p resources/views/components/editor
cp "/c/Users/Defensoria/Herd/DPEMT/resources/views/components/editor/tiptap.blade.php" resources/views/components/editor/tiptap.blade.php
```

- [ ] **Step 3: Write the failing test**

Create `tests/Feature/EditorComponentTest.php`:

```php
<?php

use Illuminate\Support\Facades\Blade;

test('the tiptap editor component renders its mount point and hidden input', function () {
    $html = Blade::render('<x-editor.tiptap name="body" value="<p>Olá</p>" />');

    expect($html)->toContain('x-ref="editor"')
        ->and($html)->toContain('name="body"');
});
```

- [ ] **Step 4: Run the test to verify it fails or passes**

Run: `php artisan test --filter=EditorComponentTest`
Expected: PASS once the file is copied (this component has no DPEMT-specific server-side logic — it is pure Blade + an inline `@script` block). If it fails, check the error for any undefined Blade directive and confirm Livewire 4 is installed (Task 2) since `@script`/`@endscript` are Livewire directives.

- [ ] **Step 5: Build assets to confirm the Tiptap imports resolve**

Run: `npm run build`
Expected: exits 0 — confirms Vite can resolve every `@tiptap/*` import inside the component's `@script` block.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Port the Tiptap editor component"
```

---

### Task 8: Authentication (login/logout, account status check)

**Files:**
- Create: `app/Livewire/Auth/Login.php`, `resources/views/livewire/auth/login.blade.php`, `resources/views/layouts/guest.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Consumes: `User::isActive()` (Task 4), `<x-ui.input>`/`<x-ui.checkbox>`/`<x-ui.button>` (Task 6).
- Produces: named routes `login` (GET), `logout` (POST), `dashboard` (GET, protected — created in Task 9, referenced here as the post-login redirect target).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Auth/LoginTest.php`:

```php
<?php

use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Auth\Login;

test('a user with correct credentials and active status can log in', function () {
    $user = User::factory()->create([
        'email' => 'ana@defensoria.am.gov.br',
        'password' => bcrypt('senha-correta'),
        'status' => UserStatus::Active,
    ]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'senha-correta')
        ->call('submit')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('a user with the wrong password cannot log in', function () {
    User::factory()->create([
        'email' => 'ana@defensoria.am.gov.br',
        'password' => bcrypt('senha-correta'),
        'status' => UserStatus::Active,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'ana@defensoria.am.gov.br')
        ->set('password', 'senha-errada')
        ->call('submit')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('a blocked user cannot log in even with the correct password', function () {
    User::factory()->create([
        'email' => 'bloqueado@defensoria.am.gov.br',
        'password' => bcrypt('senha-correta'),
        'status' => UserStatus::Blocked,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'bloqueado@defensoria.am.gov.br')
        ->set('password', 'senha-correta')
        ->call('submit')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('logout ends the session and redirects to login', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=LoginTest`
Expected: FAIL — `App\Livewire\Auth\Login` and the `login`/`logout`/`dashboard` routes don't exist yet.

- [ ] **Step 3: Create the Login Livewire component**

Create `app/Livewire/Auth/Login.php`:

```php
<?php

namespace App\Livewire\Auth;

use App\Enums\UserStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:6')]
    public string $password = '';

    public bool $remember = false;

    public function submit(): void
    {
        $this->validate();

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());
            $this->addError('email', "Muitas tentativas de login. Tente novamente em {$seconds} segundos.");

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            $this->addError('email', 'E-mail ou senha inválidos.');

            return;
        }

        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            $this->addError('email', $this->statusDeniedMessage($user->status));

            return;
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    private function statusDeniedMessage(UserStatus $status): string
    {
        return match ($status) {
            UserStatus::Blocked => 'Sua conta foi bloqueada. Contate um administrador.',
            default => 'Não é possível acessar o sistema com esta conta.',
        };
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.guest');
    }
}
```

`last_login_at` is intentionally not tracked here — it isn't in this app's `users` table (Task 4's migration doesn't include it, since it wasn't part of the spec's data model).

- [ ] **Step 4: Create the login view**

Create `resources/views/livewire/auth/login.blade.php`:

```blade
<div class="min-h-screen flex items-center justify-center px-4 py-16" style="background-color: var(--bg-base);">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-2xl font-bold" style="color: var(--text-primary);">Intranet DPEMT</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary);">Defensoria Pública do Estado de Mato Grosso</p>
        </div>

        <div class="rounded-3xl p-8 shadow-2xl" style="background-color: var(--bg-card); border: 1px solid var(--border-color);">
            <form wire:submit="submit" class="space-y-5">
                @if ($errors->has('email'))
                    <div class="px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <x-ui.input wire:model="email" id="email" type="email" autocomplete="email" label="E-mail" />
                <x-ui.input wire:model="password" id="password" type="password" autocomplete="current-password" label="Senha" />
                <x-ui.checkbox label="Manter conectado" wire:model="remember" id="remember" />

                <x-ui.button type="submit" loading="{{ false }}" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Entrar</span>
                    <span wire:loading>Autenticando...</span>
                </x-ui.button>
            </form>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Create the guest layout**

Create `resources/views/layouts/guest.blade.php`:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Login' }} — Intranet DPEMT</title>
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
```

- [ ] **Step 6: Add the auth routes**

Edit `routes/web.php`:

```php
<?php

use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
```

A placeholder `dashboard` route is added in Task 9 — these tests will still fail on `route('dashboard')` until then; that's expected and resolved by the next task.

- [ ] **Step 7: Run the tests**

Run: `php artisan test --filter=LoginTest`
Expected: still FAIL at this point only on the `dashboard` route not existing (`assertRedirect(route('dashboard'))` and the `submit()` redirect target) — confirm the failure is specifically about the missing `dashboard` route, not about login/logout logic itself. Proceed to Task 9, then re-run.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add authentication (login/logout, account status check)"
```

---

### Task 9: Application shell — layout, dashboard route, header/sidebar skeleton

**Files:**
- Create: `resources/views/layouts/app.blade.php`, `resources/views/components/layout/header.blade.php`, `resources/views/components/layout/sidebar.blade.php`, `resources/views/dashboard.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: `<x-ui.*>` (Task 6), authenticated `auth()->user()` (Task 4/8).
- Produces: named route `dashboard` (GET, `auth` middleware), `layouts.app` Blade layout with a `$slot`, `<x-layout.header />` and `<x-layout.sidebar />` components (structural skeleton only — nav items/permission filtering arrive in Task 10).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DashboardTest.php`:

```php
<?php

use App\Models\User;

test('a guest is redirected to login when visiting the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('an authenticated user sees the header and sidebar on the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200)
        ->assertSee('Intranet DPEMT')
        ->assertSee($user->name);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=DashboardTest`
Expected: FAIL — `dashboard` route doesn't exist.

- [ ] **Step 3: Create the header component**

Create `resources/views/components/layout/header.blade.php` (structure from `home.html`'s header, lines 76-133):

```blade
<header class="bg-dpemt-dark text-white sticky top-0 z-50 shadow-md h-16 flex items-center justify-between px-4" style="background-color: #0C4A34;">
    <div class="flex items-center gap-3">
        <button id="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-emerald-800/60 transition text-white/90">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>

        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full bg-white/10 border border-white/20 flex items-center justify-center p-1">
                <svg class="w-full h-full text-white fill-current" viewBox="0 0 24 24">
                    <path d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-5.45 9-12V7l-9-5zm0 2.18l7 3.89v5.03c0 4.54-3.12 8.78-7 9.87-3.88-1.09-7-5.33-7-9.87V8.07l7-3.89zM11 7v2h2V7h-2zm0 4v6h2v-6h-2z"/>
                </svg>
            </div>
            <div>
                <h1 class="font-extrabold text-sm uppercase tracking-wider leading-tight text-white">Intranet DPEMT</h1>
                <p class="text-[10px] text-emerald-200 uppercase tracking-widest font-medium">Defensoria Pública do Estado de Mato Grosso</p>
            </div>
        </div>
    </div>

    <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
        <div class="relative w-full">
            <input type="text" placeholder="Buscar no sistema..."
                class="w-full py-1.5 pl-4 pr-10 rounded-full bg-emerald-950/60 border border-emerald-700/50 text-white placeholder-emerald-300/70 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400" />
            <button class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-300 hover:text-white">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </button>
        </div>
    </div>

    <div class="flex items-center gap-4" x-data="{ userMenuOpen: false }">
        <button class="relative p-2 rounded-full hover:bg-emerald-800/60 text-emerald-100 transition">
            <i class="fa-regular fa-bell text-lg"></i>
        </button>

        <div class="h-6 w-px bg-emerald-800 hidden sm:block"></div>

        <div class="relative">
            <button @click="userMenuOpen = !userMenuOpen" class="flex items-center gap-2.5 cursor-pointer p-1 rounded-lg hover:bg-emerald-800/40 transition">
                <div class="w-8 h-8 rounded-full bg-emerald-700 overflow-hidden border border-emerald-400 flex items-center justify-center text-white font-bold text-xs">
                    {{ auth()->user()->getAvatarInitials() }}
                </div>
                <div class="hidden sm:block text-left">
                    <span class="text-xs font-semibold text-white block leading-tight">{{ auth()->user()->name }}</span>
                </div>
                <i class="fa-solid fa-chevron-down text-[10px] text-emerald-300"></i>
            </button>

            <div x-show="userMenuOpen" @click.outside="userMenuOpen = false" x-cloak
                class="absolute right-0 mt-2 w-48 rounded-xl shadow-lg bg-white text-slate-700 py-1 z-50">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-100">Sair</button>
                </form>
            </div>
        </div>
    </div>
</header>
```

- [ ] **Step 4: Create the sidebar component (skeleton — static items, no permission filtering yet)**

Create `resources/views/components/layout/sidebar.blade.php`:

```blade
<aside x-show="sidebarOpen" id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 overflow-y-auto">
    <div class="p-4 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
        <div class="w-12 h-12 rounded-full bg-slate-200 flex-shrink-0 flex items-center justify-center overflow-hidden border border-slate-300">
            <i class="fa-solid fa-user-tie text-2xl text-slate-500"></i>
        </div>
        <div class="overflow-hidden">
            <p class="text-xs text-slate-500 font-medium">Bem-vindo,</p>
            <p class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</p>
            <span class="inline-block mt-0.5 px-2 py-0.5 bg-slate-200/80 text-slate-600 text-[10px] font-semibold rounded-full">
                {{ auth()->user()->getRoleNames()->first() ?? 'Sem papel' }}
            </span>
        </div>
    </div>

    <nav class="p-3 space-y-6 flex-1 text-sm">
        <div>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-emerald-50 text-emerald-800 font-semibold transition">
                <i class="fa-solid fa-house text-emerald-700"></i>
                <span>Início</span>
            </a>
        </div>
    </nav>
</aside>
```

- [ ] **Step 5: Create the authenticated layout**

Create `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Painel' }} — Intranet DPEMT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-slate-100 flex flex-col text-slate-800" x-data="{ sidebarOpen: true }">
    <x-layout.header />

    <div class="flex flex-1 overflow-hidden">
        <x-layout.sidebar />

        <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 bg-slate-100">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
```

- [ ] **Step 6: Create the dashboard view and route**

Create `resources/views/dashboard.blade.php`:

```blade
<x-layouts.app title="Início">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-xl font-bold text-slate-800">Início</h2>
        <p class="text-slate-500 mt-1">Bem-vindo à intranet, {{ auth()->user()->name }}.</p>
    </div>
</x-layouts.app>
```

Add to `routes/web.php`:

```php
Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware('auth')
    ->name('dashboard');
```

- [ ] **Step 7: Run both this task's and Task 8's tests**

Run:
```bash
php artisan test --filter=DashboardTest
php artisan test --filter=LoginTest
```
Expected: all PASS now that `dashboard` exists.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add application shell: layout, dashboard route, header/sidebar skeleton"
```

---

### Task 10: Permission-filtered sidebar navigation config

**Files:**
- Create: `config/sidebar.php`
- Modify: `resources/views/components/layout/sidebar.blade.php`
- Test: `tests/Feature/SidebarTest.php`

**Interfaces:**
- Produces: `config('sidebar')` returns `array<int, array{group?: string, label?: string, icon?: string, uri?: string, route?: string, permission?: string|null, items?: array}>` — a flat top-level entry has `label`/`icon`/`uri`/`route`/`permission` directly; a grouped entry has `group` + `items` (each item shaped like a flat entry).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SidebarTest.php`:

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

test('a nav item with no permission requirement is visible to any authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Mural');
});

test('a nav item requiring a permission the user lacks is not rendered', function () {
    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertDontSee('Usuários');
});

test('a nav item requiring a permission the user has is rendered', function () {
    Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    $role->givePermissionTo('users.view');

    $user = User::factory()->create();
    $user->assignRole('Administrador');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('Usuários');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=SidebarTest`
Expected: FAIL — "Mural" and "Usuários" aren't rendered yet; `config('sidebar')` doesn't exist.

- [ ] **Step 3: Create the sidebar config**

Create `config/sidebar.php`:

```php
<?php

return [
    [
        'label' => 'Início',
        'icon' => 'house',
        'uri' => '/dashboard',
        'route' => 'dashboard',
        'permission' => null,
    ],
    [
        'group' => 'GERAL',
        'items' => [
            ['label' => 'Assistente Geral', 'icon' => 'robot', 'uri' => '/assistente-geral', 'route' => 'placeholder.assistente-geral', 'permission' => null],
        ],
    ],
    [
        'group' => 'CONTEÚDO',
        'items' => [
            ['label' => 'Mural', 'icon' => 'newspaper', 'uri' => '/mural', 'route' => 'placeholder.mural', 'permission' => null],
            ['label' => 'Serviços', 'icon' => 'sliders', 'uri' => '/servicos', 'route' => 'placeholder.servicos', 'permission' => null],
            ['label' => 'Documentos', 'icon' => 'folder-open', 'uri' => '/documentos', 'route' => 'placeholder.documentos', 'permission' => null],
        ],
    ],
    [
        'group' => 'GESTÃO',
        'items' => [
            ['label' => 'Conselho Superior', 'icon' => 'scale-balanced', 'uri' => '/conselho-superior', 'route' => 'placeholder.conselho-superior', 'permission' => null],
            ['label' => 'Notícias', 'icon' => 'comment-dots', 'uri' => '/noticias', 'route' => 'placeholder.noticias', 'permission' => null],
            ['label' => 'Fotos', 'icon' => 'camera', 'uri' => '/fotos', 'route' => 'placeholder.fotos', 'permission' => null],
            ['label' => 'Vídeos', 'icon' => 'video', 'uri' => '/videos', 'route' => 'placeholder.videos', 'permission' => null],
        ],
    ],
    [
        'group' => 'OUTROS',
        'items' => [
            ['label' => 'Eventos', 'icon' => 'calendar-days', 'uri' => '/eventos', 'route' => 'placeholder.eventos', 'permission' => null],
            ['label' => 'Capacitações', 'icon' => 'graduation-cap', 'uri' => '/capacitacoes', 'route' => 'placeholder.capacitacoes', 'permission' => null],
            ['label' => 'Fórum', 'icon' => 'bullhorn', 'uri' => '/forum', 'route' => 'placeholder.forum', 'permission' => null],
            ['label' => 'SOS DPEMT', 'icon' => 'life-ring', 'uri' => '/sos', 'route' => 'placeholder.sos', 'permission' => null],
            ['label' => 'Aniversariantes', 'icon' => 'cake-candles', 'uri' => '/aniversariantes', 'route' => 'placeholder.aniversariantes', 'permission' => null],
        ],
    ],
    [
        'group' => 'ADMINISTRAÇÃO',
        'items' => [
            ['label' => 'Usuários', 'icon' => 'users', 'uri' => '/admin/usuarios', 'route' => 'admin.users.index', 'permission' => 'users.view'],
        ],
    ],
];
```

- [ ] **Step 4: Render the config in the sidebar component, filtered by permission**

Replace the `<nav>` block in `resources/views/components/layout/sidebar.blade.php`:

```blade
    <nav class="p-3 space-y-6 flex-1 text-sm">
        @foreach (config('sidebar') as $section)
            @php
                $items = collect($section['items'] ?? [$section])
                    ->filter(fn ($item) => $item['permission'] === null || auth()->user()->can($item['permission']));
            @endphp

            @if ($items->isNotEmpty())
                <div>
                    @if (isset($section['group']))
                        <h3 class="px-3 text-[11px] font-bold text-slate-400 tracking-wider uppercase mb-1">{{ $section['group'] }}</h3>
                    @endif
                    <ul class="space-y-0.5">
                        @foreach ($items as $item)
                            <li>
                                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-100 rounded-lg font-medium transition">
                                    <i class="fa-solid fa-{{ $item['icon'] }} text-slate-400 w-4 text-center"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </nav>
```

This will fail to render until the `placeholder.*` and `admin.users.index` route names exist — Task 11 and Task 12 add them. For now, run the next step only after Task 11.

- [ ] **Step 5: Run the tests (after Task 11 adds the placeholder routes)**

Run: `php artisan test --filter=SidebarTest`
Expected: all 3 PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Drive the sidebar from a permission-filtered config"
```

---

### Task 11: Placeholder routes for every content section

**Files:**
- Create: `resources/views/coming-soon.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PlaceholderRoutesTest.php`

**Interfaces:**
- Consumes: `config('sidebar')` (Task 10).
- Produces: a GET route (named `placeholder.<slug>`, `auth` middleware) for every sidebar item whose `route` starts with `placeholder.`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PlaceholderRoutesTest.php`:

```php
<?php

use App\Models\User;

test('every placeholder sidebar route resolves to a 200 response for an authenticated user', function () {
    $user = User::factory()->create();

    foreach (config('sidebar') as $section) {
        foreach ($section['items'] ?? [$section] as $item) {
            if (! str_starts_with($item['route'], 'placeholder.')) {
                continue;
            }

            $this->actingAs($user)
                ->get($item['uri'])
                ->assertStatus(200)
                ->assertSee($item['label']);
        }
    }
});

test('a guest is redirected to login from a placeholder route', function () {
    $this->get('/mural')->assertRedirect(route('login'));
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PlaceholderRoutesTest`
Expected: FAIL — none of the `/mural`, `/servicos`, etc. routes exist.

- [ ] **Step 3: Create the placeholder view**

Create `resources/views/coming-soon.blade.php`:

```blade
<x-layouts.app :title="$title">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center">
            <i class="fa-solid fa-screwdriver-wrench text-4xl text-emerald-600 mb-4"></i>
            <h2 class="text-lg font-bold text-slate-800">{{ $title }}</h2>
            <p class="text-slate-500 mt-2">Esta seção está em construção.</p>
        </div>
    </div>
</x-layouts.app>
```

- [ ] **Step 4: Register the placeholder routes from config**

Add to `routes/web.php`:

```php
Route::middleware('auth')->group(function () {
    foreach (config('sidebar') as $section) {
        foreach ($section['items'] ?? [$section] as $item) {
            if (! str_starts_with($item['route'], 'placeholder.')) {
                continue;
            }

            Route::get($item['uri'], fn () => view('coming-soon', ['title' => $item['label']]))
                ->name($item['route']);
        }
    }
});
```

- [ ] **Step 5: Run this task's and Task 10's tests**

Run:
```bash
php artisan test --filter=PlaceholderRoutesTest
php artisan test --filter=SidebarTest
```
Expected: all PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add placeholder routes for every content section"
```

---

### Task 12: Admin middleware + user management list (UserIndex)

**Files:**
- Create: `app/Http/Middleware/AdminMiddleware.php`, `app/Http/Middleware/PermissionMiddleware.php`, `app/Livewire/Admin/Users/UserIndex.php`, `resources/views/livewire/admin/users/user-index.blade.php`
- Modify: `bootstrap/app.php`, `routes/web.php`
- Test: `tests/Feature/Admin/UserManagementTest.php`

**Interfaces:**
- Consumes: `User` model (Task 4), `<x-ui.data-table>`/`<x-ui.badge>`/`<x-ui.button>` (Task 6).
- Produces: route `admin.users.index` (GET, `auth`+`admin`+`permission:users.view` middleware), Livewire events `user-saved` (dispatched by `UserForm` in Task 13, listened to here to refresh the list).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/UserManagementTest.php`:

```php
<?php

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Admin\Users\UserIndex;

test('a Super Administrador can view the user management page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertStatus(200)
        ->assertSeeLivewire(UserIndex::class);
});

test('a Gestor is forbidden from the user management page', function () {
    $gestor = User::factory()->create();
    $gestor->assignRole('Gestor');

    $this->actingAs($gestor)
        ->get(route('admin.users.index'))
        ->assertStatus(403);
});

test('an Editor is forbidden from the user management page', function () {
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $this->actingAs($editor)
        ->get(route('admin.users.index'))
        ->assertStatus(403);
});

test('the list shows every user with their role badge', function () {
    $admin = User::factory()->create(['name' => 'Carla Souza']);
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->assertSee('Carla Souza');
});

test('deleting the sole Super Administrador is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('delete', $admin->id)
        ->assertHasErrors();

    expect(User::find($admin->id))->not->toBeNull();
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $second = User::factory()->create();
    $second->assignRole('Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('delete', $admin->id)
        ->assertHasErrors();

    expect(User::find($admin->id))->not->toBeNull();
});

test('an admin can delete a different, non-last-super-admin user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    $editor = User::factory()->create();
    $editor->assignRole('Editor');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)->call('delete', $editor->id);

    expect(User::find($editor->id))->toBeNull();
});

test('blocking the sole Super Administrador is blocked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');

    $this->actingAs($admin);

    Livewire::test(UserIndex::class)
        ->call('toggleBlock', $admin->id)
        ->assertHasErrors();

    expect($admin->refresh()->isActive())->toBeTrue();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=UserManagementTest`
Expected: FAIL — route, middleware, and Livewire component don't exist yet.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/AdminMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $request->user()->hasAnyRole(['Super Administrador', 'Administrador', 'Gestor', 'Editor'])) {
            abort(403, 'Acesso ao painel administrativo negado.');
        }

        return $next($request);
    }
}
```

Create `app/Http/Middleware/PermissionMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        foreach ($permissions as $permission) {
            if (! $request->user()->hasPermissionTo($permission)) {
                abort(403, 'Permissão negada.');
            }
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the middleware aliases**

Edit `bootstrap/app.php`, adding the `use` statements and the `withMiddleware` block:

```php
<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

(Keep any other lines the Laravel 13 skeleton already generated in this file — only add the `use` imports and the `alias([...])` call inside `withMiddleware`.)

- [ ] **Step 5: Create the UserIndex Livewire component**

Create `app/Livewire/Admin/Users/UserIndex.php`:

```php
<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UserIndex extends Component
{
    use WithPagination;

    #[On('user-saved')]
    public function refreshList(): void
    {
        // Triggers a re-render; no state to update beyond pagination's own refresh.
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->addError('delete', 'Você não pode excluir a própria conta.');

            return;
        }

        if ($user->isSuperAdmin() && User::superAdministratorCount() <= 1) {
            $this->addError('delete', 'Não é possível excluir o único Super Administrador.');

            return;
        }

        $user->delete();
    }

    public function toggleBlock(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->addError('toggleBlock', 'Você não pode bloquear a própria conta.');

            return;
        }

        if ($user->isActive() && $user->isSuperAdmin() && User::superAdministratorCount() <= 1) {
            $this->addError('toggleBlock', 'Não é possível bloquear o único Super Administrador.');

            return;
        }

        $user->update([
            'status' => $user->isActive() ? UserStatus::Blocked : UserStatus::Active,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.users.user-index', [
            'users' => User::with('roles')->orderBy('name')->paginate(15),
        ]);
    }
}
```

- [ ] **Step 6: Create the UserIndex view**

Create `resources/views/livewire/admin/users/user-index.blade.php`:

```blade
<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800">Usuários</h2>
    </div>

    @error('delete') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    @error('toggleBlock') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror

    <x-ui.data-table>
        <x-slot:header>
            <th class="px-6 py-3">Nome</th>
            <th class="px-6 py-3">E-mail</th>
            <th class="px-6 py-3">Papel</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Ações</th>
        </x-slot:header>

        @foreach ($users as $user)
            <tr>
                <td class="px-6 py-3">{{ $user->name }}</td>
                <td class="px-6 py-3">{{ $user->email }}</td>
                <td class="px-6 py-3">{{ $user->getRoleNames()->first() ?? '—' }}</td>
                <td class="px-6 py-3">{{ $user->status->label() }}</td>
                <td class="px-6 py-3 space-x-2">
                    <button wire:click="toggleBlock({{ $user->id }})" class="text-sm text-amber-700 hover:underline">
                        {{ $user->isActive() ? 'Bloquear' : 'Desbloquear' }}
                    </button>
                    <button wire:click="delete({{ $user->id }})" wire:confirm="Confirma a exclusão deste usuário?" class="text-sm text-red-700 hover:underline">
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach

        <x-slot:footer>
            {{ $users->links() }}
        </x-slot:footer>
    </x-ui.data-table>
</div>
```

- [ ] **Step 7: Add the admin route group**

Add to `routes/web.php`:

```php
use App\Livewire\Admin\Users\UserIndex;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/usuarios', UserIndex::class)->name('users.index');
    });
});
```

Also ensure the `AclSeeder`'s `Administrador`/`Super Administrador` roles already carry `users.view` (they do — Task 5 gave `Administrador` the full `users.*` set and `Super Administrador` the whole inventory). `Gestor`/`Editor` have no `users.*` permission, so the `permission:users.view` middleware already blocks them with a 403 — satisfying the "direct URL access" review-focus item without extra code.

- [ ] **Step 8: Run the tests**

Run: `php artisan test --filter=UserManagementTest`
Expected: all 7 PASS. If `assertSeeLivewire` isn't available, use `assertSee('Usuários')` instead — both prove the component rendered.

- [ ] **Step 9: Run the full suite to confirm nothing regressed**

Run: `php artisan test`
Expected: all tests PASS (this also confirms Task 10's "Usuários" visibility test now passes, since `admin.users.index` exists).

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "Add admin middleware and user management list"
```

---

### Task 13: User create/edit form (UserForm)

**Files:**
- Create: `app/Livewire/Admin/Users/UserForm.php`, `resources/views/livewire/admin/users/user-form.blade.php`
- Modify: `resources/views/livewire/admin/users/user-index.blade.php`
- Test: `tests/Feature/Admin/UserFormTest.php`

**Interfaces:**
- Consumes: `User` model (Task 4), `<x-ui.modal>`/`<x-ui.input>`/`<x-ui.select>` (Task 6).
- Produces: dispatches a `user-saved` event (consumed by `UserIndex::refreshList()`, Task 12).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Admin/UserFormTest.php`:

```php
<?php

use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Admin\Users\UserForm;

function actingAsAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('Super Administrador');
    test()->actingAs($admin);

    return $admin;
}

test('creating a user with role Editor does not require a department', function () {
    actingAsAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'João Lima')
        ->set('username', 'joao.lima')
        ->set('email', 'joao@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Editor')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'joao@defensoria.am.gov.br')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('Editor'))->toBeTrue()
        ->and($user->department)->toBeNull();
});

test('creating a user with role Gestor requires a department', function () {
    actingAsAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'Marina Alves')
        ->set('username', 'marina.alves')
        ->set('email', 'marina@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Gestor')
        ->set('department', '')
        ->call('save')
        ->assertHasErrors(['department']);

    expect(User::where('email', 'marina@defensoria.am.gov.br')->first())->toBeNull();
});

test('creating a user with role Gestor and a department succeeds', function () {
    actingAsAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'Marina Alves')
        ->set('username', 'marina.alves')
        ->set('email', 'marina@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Gestor')
        ->set('department', 'Recursos Humanos')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'marina@defensoria.am.gov.br')->first();
    expect($user->department)->toBe('Recursos Humanos')
        ->and($user->hasRole('Gestor'))->toBeTrue();
});

test('editing an existing user updates their role without requiring a new password', function () {
    actingAsAdmin();
    $editor = User::factory()->create(['email' => 'troca@defensoria.am.gov.br']);
    $editor->assignRole('Editor');

    Livewire::test(UserForm::class, ['user' => $editor])
        ->set('role', 'Gestor')
        ->set('department', 'Comunicação')
        ->call('save')
        ->assertHasNoErrors();

    expect($editor->refresh()->hasRole('Gestor'))->toBeTrue()
        ->and($editor->department)->toBe('Comunicação');
});

test('email must be unique when creating a user', function () {
    actingAsAdmin();
    User::factory()->create(['email' => 'duplicado@defensoria.am.gov.br']);

    Livewire::test(UserForm::class)
        ->set('name', 'Outro Nome')
        ->set('username', 'outro.nome')
        ->set('email', 'duplicado@defensoria.am.gov.br')
        ->set('password', 'senha-segura-123')
        ->set('role', 'Editor')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('demoting the sole Super Administrador away from that role is blocked', function () {
    $admin = actingAsAdmin();

    Livewire::test(UserForm::class, ['user' => $admin])
        ->set('role', 'Administrador')
        ->call('save')
        ->assertHasErrors(['role']);

    expect($admin->refresh()->hasRole('Super Administrador'))->toBeTrue();
});

test('demoting one of two Super Administradores is allowed', function () {
    $admin = actingAsAdmin();
    $second = User::factory()->create();
    $second->assignRole('Super Administrador');

    Livewire::test(UserForm::class, ['user' => $second])
        ->set('role', 'Administrador')
        ->call('save')
        ->assertHasNoErrors();

    expect($second->refresh()->hasRole('Administrador'))->toBeTrue()
        ->and($admin->refresh()->hasRole('Super Administrador'))->toBeTrue();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=UserFormTest`
Expected: FAIL — `App\Livewire\Admin\Users\UserForm` doesn't exist yet.

- [ ] **Step 3: Create the UserForm component**

Create `app/Livewire/Admin/Users/UserForm.php`:

```php
<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

class UserForm extends Component
{
    public ?User $user = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $username = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public string $password = '';

    #[Validate('required|in:Super Administrador,Administrador,Gestor,Editor')]
    public string $role = 'Editor';

    public string $department = '';

    public function mount(?User $user = null): void
    {
        if ($user) {
            $this->user = $user;
            $this->name = $user->name;
            $this->username = $user->username;
            $this->email = $user->email;
            $this->role = $user->getRoleNames()->first() ?? 'Editor';
            $this->department = $user->department ?? '';
        }
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'role' => 'required|in:Super Administrador,Administrador,Gestor,Editor',
            'department' => $this->role === 'Gestor' ? 'required|string|max:255' : 'nullable|string|max:255',
        ];

        if (! $this->user) {
            $rules['password'] = 'required|string|min:8';
        } elseif ($this->password !== '') {
            $rules['password'] = 'string|min:8';
        }

        $this->validate($rules);

        if (
            $this->user
            && $this->user->isSuperAdmin()
            && $this->role !== 'Super Administrador'
            && User::superAdministratorCount() <= 1
        ) {
            $this->addError('role', 'Não é possível remover o papel do único Super Administrador.');

            return;
        }

        $data = [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'department' => $this->role === 'Gestor' ? $this->department : null,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->user) {
            $this->user->update($data);
            $this->user->syncRoles([$this->role]);
        } else {
            $user = User::create($data + ['password' => Hash::make($this->password)]);
            $user->assignRole($this->role);
        }

        $this->dispatch('user-saved');
        $this->reset(['name', 'username', 'email', 'password', 'department']);
        $this->role = 'Editor';
    }

    public function render()
    {
        return view('livewire.admin.users.user-form');
    }
}
```

- [ ] **Step 4: Create the UserForm view**

Create `resources/views/livewire/admin/users/user-form.blade.php`:

```blade
<form wire:submit="save" class="space-y-4">
    <x-ui.input wire:model="name" label="Nome" />
    <x-ui.input wire:model="username" label="Usuário" />
    <x-ui.input wire:model="email" type="email" label="E-mail" />
    <x-ui.input wire:model="password" type="password" label="Senha" :hint="$user ? 'Deixe em branco para manter a senha atual.' : null" />

    <x-ui.select wire:model.live="role" label="Papel">
        <option value="Super Administrador">Super Administrador</option>
        <option value="Administrador">Administrador</option>
        <option value="Gestor">Gestor</option>
        <option value="Editor">Editor</option>
    </x-ui.select>

    @if ($role === 'Gestor')
        <x-ui.input wire:model="department" label="Departamento/Setor" />
    @endif

    <x-ui.button type="submit">Salvar</x-ui.button>
</form>
```

- [ ] **Step 5: Embed the form in a modal on the index page**

Edit `resources/views/livewire/admin/users/user-index.blade.php`, adding a "Novo usuário" trigger and the modal (add this right after the `<h2>` heading's closing `</div>`):

```blade
    <div>
        <x-ui.button x-on:click="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'user-form' }))">
            Novo usuário
        </x-ui.button>
    </div>

    <x-ui.modal name="user-form" maxWidth="lg">
        <x-slot:header>Usuário</x-slot:header>
        <livewire:admin.users.user-form />
    </x-ui.modal>
```

(The index's `<div class="flex items-center justify-between">` wrapper already exists from Task 12 — nest the new button inside it, next to the `<h2>`.)

- [ ] **Step 6: Run the tests**

Run: `php artisan test --filter=UserFormTest`
Expected: all 7 PASS.

- [ ] **Step 7: Run the full suite**

Run: `php artisan test`
Expected: all tests across every task PASS.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Add user create/edit form"
```

---

### Task 14: Final polish

**Files:**
- No new files — formatting and full-suite verification only.

- [ ] **Step 1: Run Pint on all touched PHP files**

Run: `vendor/bin/pint --dirty --format agent`
Expected: reports fixed/clean files, exits 0.

- [ ] **Step 2: Run the entire test suite one more time**

Run: `php artisan test`
Expected: all tests PASS, zero failures.

- [ ] **Step 3: Rebuild frontend assets**

Run: `npm run build`
Expected: exits 0.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "Run Pint and verify the full test suite"
```

---

### Task 15: Install Laravel Boost

**Files:**
- Modify: `composer.json`
- Create: `boost.json` (and whatever `php artisan boost:install` generates: MCP config for the detected agent, guideline files, skill files)

**Interfaces:**
- None consumed from or produced for other tasks — Boost is a dev/AI-tooling package, independent of application behavior. This task can run any time after Task 1 (needs `composer.json` to exist) and does not block or get blocked by any other task.

- [ ] **Step 1: Install the package**

Run: `composer require laravel/boost:^2.7 --dev`
Expected: exits 0, `laravel/boost` appears in `composer.json` `require-dev` (matches the version already used by the sibling DPEMT project).

- [ ] **Step 2: Run the installer non-interactively**

Run: `php artisan boost:install --guidelines --skills --mcp --no-interaction`
Expected: exits 0, creates `boost.json` at the project root and wires up MCP/guidelines/skills for whatever coding agent it detects in this environment (Claude Code). `--no-interaction` makes Laravel Prompts fall back to sensible defaults instead of blocking on a prompt.

- [ ] **Step 3: If the installer errors or appears to need a TTY, fall back to a manual `boost.json`**

If Step 2 fails specifically because it requires interactive input (rather than a real error), create `boost.json` by hand instead, mirroring the sibling DPEMT project's working configuration:

```json
{
    "cloud": false,
    "guidelines": true,
    "mcp": true,
    "nightwatch": false,
    "sail": false,
    "skills": [
        "laravel-best-practices",
        "testing-best-practices",
        "livewire-development",
        "tailwindcss-development"
    ]
}
```

Then tell the user that `boost.json` was created manually and that they should run `php artisan boost:install` themselves in an interactive terminal to finish wiring up the MCP server entry for their editor/agent, since that step genuinely needs a human choosing options in a terminal.

- [ ] **Step 4: Verify**

Run: `php artisan about`
Expected: exits 0 with no new errors (confirms the package registered its service provider cleanly). Run `php artisan test` once more to confirm Boost didn't break anything.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Install Laravel Boost"
```
