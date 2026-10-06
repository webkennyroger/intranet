<?php

namespace App\Livewire\Admin\Articles;

use App\Models\Article;
use App\Models\Category;
use App\Services\ActivityLogger;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Formulário admin de criação/edição de artigos do Mural (notícias/avisos
 * publicados aos usuários), incluindo categoria e capa (cor/ícone/rótulo).
 */
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
        $this->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string|max:500',
            'body' => 'required|string',
            'categoryId' => $this->categoryId !== '' ? 'required|exists:categories,id' : 'nullable',
            'coverColor' => 'required|string|max:20',
            'coverIcon' => 'required|regex:/^[a-z0-9-]+$/',
        ]);

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
            $changes = ActivityLogger::diff($this->article, $data);
            $this->article->update($data);
            if ($changes !== []) {
                ActivityLogger::updated($this->article, $changes);
            }
        } else {
            $article = Article::create($data + [
                'author_id' => auth()->id(),
                'is_published' => false,
            ]);
            ActivityLogger::created($article);
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
