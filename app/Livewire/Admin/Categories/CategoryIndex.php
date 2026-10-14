<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Tela admin "Categorias" do Mural: grade de cards (uma pasta colorida por
 * categoria, com a contagem real de artigos) e um painel lateral fixo que
 * cria uma nova categoria ou edita a selecionada — sem modal nem página
 * separada, o painel já fica sempre visível ao lado da grade.
 */
class CategoryIndex extends Component
{
    public ?Category $editing = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|alpha_dash')]
    public string $slug = '';

    #[Validate('nullable|string|max:255')]
    public string $description = '';

    #[Validate('required|regex:/^#[0-9a-fA-F]{6}$/')]
    public string $color = '#7C3AED';

    public bool $slugManuallyEdited = false;

    public function updatedName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
    }

    public function edit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->editing = $category;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = (string) $category->description;
        $this->color = $category->color;
        $this->slugManuallyEdited = true;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editing', 'name', 'slug', 'description', 'color', 'slugManuallyEdited']);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|alpha_dash|unique:categories,slug,'.($this->editing?->id ?? 'NULL'),
            'description' => 'nullable|string|max:255',
            'color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description !== '' ? $this->description : null,
            'color' => $this->color,
        ];

        if ($this->editing) {
            $changes = ActivityLogger::diff($this->editing, $data);
            $this->editing->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->editing, $changes);
            }
            $this->dispatch('toast', title: 'Categoria', message: 'Categoria atualizada com sucesso.', variant: 'success');
        } else {
            $category = Category::create($data);
            ActivityLogger::created($category);
            $this->dispatch('toast', title: 'Categoria', message: 'Categoria criada com sucesso.', variant: 'success');
        }

        $this->cancelEdit();
    }

    public function delete(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->delete();
        ActivityLogger::deleted($category);
        $this->dispatch('toast', title: 'Categoria', message: 'Categoria excluída com sucesso.', variant: 'danger');

        if ($this->editing?->id === $categoryId) {
            $this->cancelEdit();
        }
    }

    public function render()
    {
        return view('livewire.admin.categories.category-index', [
            'categories' => Category::withCount('articles')->orderBy('name')->get(),
        ])->layout('layouts.admin', ['title' => 'Categorias']);
    }
}
