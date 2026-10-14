<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Evento institucional com data/hora de início e fim, exibido na
 * agenda/calendário quando publicado.
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'location', 'cover_image', 'starts_at', 'ends_at', 'is_published'];

    public function coverImageUrl(): ?string
    {
        return $this->cover_image ? Storage::url($this->cover_image) : null;
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
