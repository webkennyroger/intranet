<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Documento institucional disponibilizado para consulta/download pelos
 * usuários quando publicado — pode ter um arquivo enviado (file_path), um
 * link externo próprio (link, abre uma página — não tem nada a ver com a
 * descrição) e/ou uma descrição em texto livre. Arquivo e link podem
 * existir juntos; quando os dois existem, cada um tem sua própria ação
 * (abrir o arquivo ou abrir o link).
 */
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'file_path', 'link', 'is_published', 'document_category_id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'document_category_id');
    }

    public function isExternalLink(): bool
    {
        return ! empty($this->link);
    }

    /**
     * Link "principal" do documento, pra quando só dá pra mostrar uma
     * ação (ex.: tabela do admin): o link, se houver, senão o arquivo
     * enviado.
     */
    public function url(): ?string
    {
        if ($this->isExternalLink()) {
            return $this->link;
        }

        return $this->fileUrl();
    }

    /**
     * Tamanho do arquivo enviado, lido direto do storage (não é
     * persistido no banco) — vale mesmo quando o documento também tem um
     * link, já que os dois podem existir juntos.
     */
    public function size(): int
    {
        if (! $this->file_path) {
            return 0;
        }

        return Storage::disk('public')->exists($this->file_path)
            ? Storage::disk('public')->size($this->file_path)
            : 0;
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    /**
     * Ícone certo pra extensão do arquivo enviado (PDF, Word etc.), igual
     * à Biblioteca de Mídia — independente de o documento também ter link.
     */
    public function fileIcon(): string
    {
        $extension = strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'file-pdf',
            'doc', 'docx' => 'file-word',
            'ppt', 'pptx' => 'file-powerpoint',
            'xls', 'xlsx', 'csv' => 'file-excel',
            default => 'file-lines',
        };
    }

    /**
     * Ícone "principal" do documento, pra quando só dá pra mostrar um
     * (ex.: tabela do admin): link tem prioridade sobre o arquivo.
     */
    public function icon(): string
    {
        return $this->isExternalLink() ? 'link' : $this->fileIcon();
    }

    /**
     * Cores do ícone/fundo por tipo de arquivo — mesmo padrão de cores já
     * usado na Biblioteca de Mídia (PDF vermelho, Word azul, Excel verde
     * etc.), pra ficar consistente em todo o site.
     *
     * @return array{bg: string, text: string}
     */
    public function fileIconClasses(): array
    {
        $extension = strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => ['bg' => 'bg-rose-50 hover:bg-rose-100', 'text' => 'text-rose-700'],
            'doc', 'docx' => ['bg' => 'bg-blue-50 hover:bg-blue-100', 'text' => 'text-blue-700'],
            'ppt', 'pptx' => ['bg' => 'bg-amber-50 hover:bg-amber-100', 'text' => 'text-amber-700'],
            'xls', 'xlsx', 'csv' => ['bg' => 'bg-emerald-50 hover:bg-emerald-100', 'text' => 'text-emerald-700'],
            default => ['bg' => 'bg-slate-100 hover:bg-slate-200', 'text' => 'text-slate-600'],
        };
    }

    /**
     * Cor fixa do ícone de link, sempre verde — independente do tipo do
     * arquivo que o documento também possa ter.
     *
     * @return array{bg: string, text: string}
     */
    public function linkIconClasses(): array
    {
        return ['bg' => 'bg-emerald-50 hover:bg-emerald-100', 'text' => 'text-emerald-700'];
    }

    /**
     * Cores do ícone "principal" (ver icon()): link tem prioridade sobre
     * o arquivo, igual à regra do icon().
     *
     * @return array{bg: string, text: string}
     */
    public function iconClasses(): array
    {
        return $this->isExternalLink() ? $this->linkIconClasses() : $this->fileIconClasses();
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }
}
