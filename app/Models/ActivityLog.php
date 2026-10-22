<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

/**
 * Registro de uma ação (criação, atualização, ativação/desativação ou
 * exclusão) feita por um usuário sobre outro model, usado para montar a
 * tela de Log de Atividades com rótulos, ícones e o diff de campos alterados.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'action',
        'description',
        'subject_type',
        'subject_id',
        'user_id',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    protected function actionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->action) {
                'created' => 'Criação',
                'updated' => 'Atualização',
                'activated' => 'Ativação',
                'deactivated' => 'Desativação',
                'deleted' => 'Exclusão',
                default => ucfirst($this->action),
            },
        );
    }

    protected function actionVariant(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->action) {
                'created', 'activated' => 'success',
                'updated' => 'info',
                'deactivated' => 'warning',
                'deleted' => 'danger',
                default => 'default',
            },
        );
    }

    protected function actionIcon(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->action) {
                'created' => 'plus',
                'updated' => 'pen',
                'activated' => 'toggle-on',
                'deactivated' => 'toggle-off',
                'deleted' => 'trash',
                default => 'circle-info',
            },
        );
    }

    /**
     * Cor hexadecimal por ação — mesma linguagem visual do `action_variant`
     * (badge), usada onde é preciso um hex de verdade (faixa decorativa da
     * linha, aba de filtro colorida), não só a classe do badge.
     */
    protected function actionColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->action) {
                'created', 'activated' => '#059669',
                'updated' => '#2563EB',
                'deactivated' => '#D97706',
                'deleted' => '#DC2626',
                default => '#64748B',
            },
        );
    }

    protected function subjectLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->subject_type) {
                Article::class => 'Mural',
                Category::class => 'Categoria',
                Document::class => 'Documento',
                DocumentCategory::class => 'Pasta de Documentos',
                ServiceLink::class => 'Serviço',
                PhotoAlbum::class => 'Álbum de Fotos',
                Video::class => 'Vídeo',
                Event::class => 'Evento',
                Training::class => 'Capacitação',
                User::class => 'Usuário',
                ForumTopic::class => 'Fórum',
                Role::class => 'Papel',
                default => $this->subject_type ? class_basename($this->subject_type) : 'Sistema',
            },
        );
    }

    /**
     * @return array<string, array{old?: mixed, new?: mixed, value?: mixed}>
     */
    protected function formattedChanges(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->changes) {
                    return [];
                }

                $labels = [
                    'title' => 'Título',
                    'name' => 'Nome',
                    'email' => 'E-mail',
                    'username' => 'Usuário',
                    'role' => 'Papel',
                    'department' => 'Departamento',
                    'status' => 'Status',
                    'is_published' => 'Publicado',
                    'is_active' => 'Ativo',
                    'url' => 'URL',
                    'description' => 'Descrição',
                    'body' => 'Conteúdo',
                ];

                $result = [];
                foreach ($this->changes as $field => $value) {
                    $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

                    if (is_array($value) && array_key_exists('old', $value) && array_key_exists('new', $value)) {
                        $result[$label] = ['old' => $value['old'], 'new' => $value['new']];
                    } else {
                        $result[$label] = ['value' => $value];
                    }
                }

                return $result;
            },
        );
    }
}
