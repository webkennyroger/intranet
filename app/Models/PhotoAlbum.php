<?php

namespace App\Models;

use Database\Factories\PhotoAlbumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Álbum de fotos de um evento institucional. Agrupa as `Photo`s do álbum
 * já ordenadas pelo campo `order` para exibição em sequência.
 */
class PhotoAlbum extends Model
{
    /** @use HasFactory<PhotoAlbumFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'cover_image_path', 'event_date', 'is_published'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('order');
    }
}
