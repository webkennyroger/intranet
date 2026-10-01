# Intranet DPEMT — Foundation Design

**Date:** 2026-10-01
**Status:** Approved for planning
**Scope:** First sub-project of the Intranet DPEMT build. Covers framework install, ported UI kit, auth, roles/permissions, the header+sidebar shell, and user/role management. Content sections (Mural, Serviços, Documentos, Notícias, Fotos, Vídeos, Eventos, Capacitações, Fórum, SOS DPEMT, Aniversariantes, Conselho Superior) are explicitly **out of scope** — each becomes its own future sub-project built on top of this foundation.

## Purpose & context

The Defensoria Pública do Estado de Mato Grosso needs an internal intranet portal (separate from the public-facing DPEMT site) for staff: news/mural, services, documents, events, forum, etc. Static HTML mockups already exist in this repo (`home.html`, `mural.html`, `servico.html`) showing the intended header/sidebar/content layout and the full navigation structure.

A sibling project, `C:\Users\Defensoria\Herd\DPEMT`, is a mature Laravel 13 + Livewire 4 + Pest 4 Joomla-style CMS (Articles, Categories, Menus, Workflow, AccessLevels, Media) with `spatie/laravel-permission`, Tailwind 4, Alpine.js, and a Tiptap-based editor. It has a reusable native UI component kit (`resources/views/components/ui/*`) and layout components (`components/layout/sidebar.blade.php`, `topbar.blade.php`) that are good structural references, plus a working Livewire login flow (`app/Livewire/Admin/Auth/Login.php`).

**Decision:** the intranet is a **brand-new, independent Laravel application** — its own database, its own deploy, no shared auth/SSO with DPEMT. We reuse DPEMT's *code* (UI components, editor, login pattern, permission-seeding approach) as a starting point, not its data or runtime.

## Goals

- Stand up Laravel 13 + Livewire 4 + Pest 4 in this directory, matching DPEMT's dependency versions.
- Port the generic, DPEMT-agnostic UI component library and the Tiptap editor component.
- Implement authentication (login/logout, account status checks) by porting DPEMT's Livewire login pattern.
- Implement 4 roles — Super Administrador, Administrador, Gestor, Editor — with `spatie/laravel-permission`, seeded with a permission set scoped to this app's needs (no module/plugin/template-management permissions — this is not an extensible CMS like Joomla, just built-in content features).
- Build the application shell: header + collapsible sidebar, matching the `home.html` mockup's structure and branding, with nav items filtered by permission via a simple config-driven menu (not a DB-driven menu builder).
- Build a user management screen (list/create/edit/block/delete users, assign roles, assign a Gestor's department scope) restricted to Super Administrador/Administrador.
- Each content section in the sidebar links to a placeholder route until its own sub-project is built.

## Non-goals (explicitly deferred)

- Any content model (Article/Category/Workflow) or section-specific functionality (Mural, Documentos, Eventos, Fórum, etc.) — future sub-projects.
- DB-driven/admin-editable menu builder (DPEMT's `Menu`/`MenuItem` model) — the foundation uses a static permission-filtered config array. Revisit only if a future need requires admin-editable navigation.
- Module/plugin/template installation system — not needed; this is a fixed-feature intranet, not an extensible CMS.
- Self-registration / registration-approval workflow — users are created by Admins via the user management screen.
- SSO or shared database with DPEMT.

## Architecture

### Stack (versions pinned to match DPEMT)

- PHP 8.5, Laravel `^13.8`
- `livewire/livewire` `^4.4`
- `spatie/laravel-permission` `^8.3`
- Dev: `pestphp/pest` `^4.7`, `pestphp/pest-plugin-laravel` `^4.1`, `laravel/pint`, `laravel/boost`
- Frontend: `laravel-vite-plugin`, `tailwindcss` `^4.0` (`@tailwindcss/vite`), `alpinejs` `^3.16`, `@tiptap/core` + starter-kit + the same extension set DPEMT uses (color, highlight, image, link, placeholder, table*, text-align, text-style, underline)
- Database: SQLite for local dev (matches DPEMT's `post-create-project-cmd` convention of touching `database/database.sqlite`), configurable via `.env` for production.

### Directory structure

Follows standard Laravel structure (per CLAUDE.md: don't invent new base folders). Key additions:

```
app/
  Livewire/
    Auth/Login.php
    Admin/Users/UserIndex.php
    Admin/Users/UserForm.php
  Models/
    User.php
  Services/
    PermissionManager.php        (ported/trimmed from DPEMT)
config/
  sidebar.php                     (nav structure + required permission per item)
database/
  seeders/
    AclSeeder.php                 (roles + permissions + initial Super Admin user)
resources/
  views/
    components/
      ui/*                        (ported verbatim from DPEMT)
      editor/tiptap.blade.php     (ported from DPEMT)
      layout/
        header.blade.php          (new, built from home.html mockup)
        sidebar.blade.php         (new, built from home.html mockup)
      layouts/
        app.blade.php             (renders header + sidebar + slot)
    livewire/
      auth/login.blade.php
      admin/users/index.blade.php
      admin/users/form.blade.php
```

### Roles & permissions

Seeded by `AclSeeder` (pattern ported from DPEMT's, trimmed):

| Role | Description | Key permissions |
|---|---|---|
| **Super Administrador** | Full access, system-protected, cannot be deleted. | all permissions |
| **Administrador** | Manages all content/sections and users, except cannot demote/delete the last Super Administrador. | `users.*`, `roles.view`, content `*` across all departments, `settings.manage` |
| **Gestor** | Scoped to one or more departments/sections; creates, edits, and **publishes** content within scope. | `content.view/create/update/publish` (scoped), `media.upload` |
| **Editor** | Creates/edits content but cannot publish; needs Gestor/Admin approval. | `content.view/create/update` (no publish) |

Department scoping for Gestor: a `department` (or `section`) column/table associated with users, used later by content sub-projects to filter what a Gestor can touch. For the foundation, it's just a field captured on the user form — enforcement logic arrives with the first content sub-project that needs it.

Safeguards (ported from DPEMT's `User::superAdministratorCount()` pattern): block operations that would leave zero Super Administradores (role removal, delete, block).

### Authentication

Port DPEMT's `app/Livewire/Admin/Auth/Login.php` + blade view: username-or-email + password, account status check (active/blocked), login throttling. Drop DPEMT's registration/approval flow — not needed here. Keep a `status` enum on `User` (active/blocked) for the block/unblock feature in user management.

### UI kit & editor

Copy these files from DPEMT verbatim, as a starting point (no intranet-specific logic lives in these — adapt only if a component references DPEMT-specific routes/models):

- `resources/views/components/ui/*.blade.php` (full list: accordion, alert, autocomplete, avatar, badge, breadcrumbs, button, calendar, card, carousel, chart, checkbox, color-picker, command, composer, context(-item), data-table, date-picker, dropdown, empty-state, field, file-upload, flag, form, heading, icon, input, kanban, loading-state, modal, navbar, otp-input, pagination, pillbox, popover, profile, progress, radio(-group), select, separator, skeleton, slider, switch, tabs, text, textarea, theme-toggle, time-picker, timeline, toast, toggle-group, tooltip)
- `resources/views/components/editor/tiptap.blade.php` and the corresponding `@tiptap/*` npm packages

Any build/JS wiring these components depend on (Vite entry points, Alpine plugins like `@alpinejs/sort` if used by a ported component) gets copied too, trimmed to only what the ported components actually use.

### Header + sidebar shell

Built from `home.html`'s markup/structure (not copied from DPEMT's admin topbar/sidebar, which has different branding and sections), converted into Blade components:

- **`components/layout/header.blade.php`**: brand/logo + sidebar-toggle button, search input, notifications bell, user dropdown (avatar initials, name, chevron, logout).
- **`components/layout/sidebar.blade.php`**: user greeting card (name + role badge) at top; grouped nav (Início; GERAL; CONTEÚDO: Mural/Serviços/Documentos; GESTÃO: Conselho Superior/Notícias/Fotos/Vídeos; OUTROS: Eventos/Capacitações/Fórum/SOS DPEMT/Aniversariantes); collapse button at bottom. A new "Administração" group holds the Usuários link.
- Nav structure lives in `config/sidebar.php` as an array of groups → items, each item declaring `label`, `icon`, `route`, and `permission`. The sidebar component iterates this config and calls `auth()->user()->can($item['permission'])` to filter.
- Content-section routes point to a shared placeholder Livewire/Blade view ("Em construção") until each section's sub-project replaces it.
- `layouts/app.blade.php` wraps header + sidebar + a content slot; every future page extends this.

### User management

- Route group `/admin/usuarios`, middleware `auth` + permission `users.view`/`users.manage`.
- `UserIndex` Livewire component: data-table (ported UI component) listing name, email, status, role, department, last login; search/filter by role or status; actions to edit, block/unblock, delete.
- `UserForm` Livewire component: create/edit — name, username, email, password (optional on edit), status, role select (single role among the 4), department field (shown only when role = Gestor).
- Enforce: cannot delete/block/demote the last Super Administrador; cannot delete your own account.
- Sidebar entry "Usuários" under an "Administração" group, gated by `users.view` permission (so Gestor/Editor never see it).

## Data model (foundation only)

- `users`: id, name, username, email, password, status (enum: active/blocked), department (nullable string, used when role = Gestor), timestamps, soft deletes.
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — standard `spatie/laravel-permission` tables.
- No content tables yet.

## Testing (Pest, feature-focused)

- Login: valid credentials succeed; invalid credentials fail; blocked account is rejected.
- Authorization: a protected dashboard route is reachable by all 4 roles (just logged-in), but `/admin/usuarios` is reachable only by Super Administrador/Administrador — Gestor/Editor get 403.
- Sidebar: a given role only sees nav items it has permission for (assert absence of "Usuários" link for Gestor/Editor; assert presence for Admin).
- User management: creating a user assigns the chosen role; Gestor role requires/stores a department; cannot delete the last Super Administrador; cannot block/delete yourself.
- Safeguard regression: attempting to demote the sole Super Administrador to another role is blocked.

## Open questions / risks

- **Department/section taxonomy**: for the foundation this is a free-text field on `users`. If a future sub-project needs a structured list of departments (e.g. a dropdown fed by a `departments` table), that's a small follow-up migration — not blocking now.
- **Placeholder routes**: all sidebar content links will 404 unless given a trivial placeholder view; the plan should include generating one shared "coming soon" view wired to every content route up front.
- **Tiptap/Alpine asset trimming**: DPEMT's `package.json` may include JS the intranet doesn't need yet (e.g. `@alpinejs/sort` if only used by a content feature we're deferring); the implementation plan should verify each ported component's JS dependency before adding it, not copy `package.json` wholesale.
