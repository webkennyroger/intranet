<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Evento institucional com data/hora de início e fim, exibido na
 * agenda/calendário quando publicado — pode ter um link externo (ex.:
 * transmissão ao vivo, formulário de inscrição) e um arquivo anexo de
 * qualquer tipo pra download (ex.: programação em PDF, planilha, vídeo).
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'location', 'cover_image', 'link', 'file_path', 'starts_at', 'ends_at', 'is_published'];

    public function coverImageUrl(): ?string
    {
        return $this->cover_image ? Storage::url($this->cover_image) : null;
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::url($this->file_path) : null;
    }

    /**
     * Ícone certo pra extensão do arquivo anexo (PDF, Word, Excel, ZIP,
     * vídeo, imagem etc.), igual ao usado na Biblioteca de Mídia.
     */
    public function fileIcon(): string
    {
        $extension = strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'file-pdf',
            'doc', 'docx' => 'file-word',
            'ppt', 'pptx' => 'file-powerpoint',
            'xls', 'xlsx', 'csv' => 'file-excel',
            'zip', 'rar', '7z' => 'file-zipper',
            'mp4', 'mov', 'avi', 'webm' => 'file-video',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'file-image',
            default => 'file-lines',
        };
    }

    /**
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
            'zip', 'rar', '7z' => ['bg' => 'bg-orange-50 hover:bg-orange-100', 'text' => 'text-orange-700'],
            'mp4', 'mov', 'avi', 'webm' => ['bg' => 'bg-purple-50 hover:bg-purple-100', 'text' => 'text-purple-700'],
            'jpg', 'jpeg', 'png', 'gif', 'webp' => ['bg' => 'bg-sky-50 hover:bg-sky-100', 'text' => 'text-sky-700'],
            default => ['bg' => 'bg-slate-100 hover:bg-slate-200', 'text' => 'text-slate-600'],
        };
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }
}
