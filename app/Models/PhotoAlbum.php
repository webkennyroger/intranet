<?php

namespace App\Models;

use Database\Factories\PhotoAlbumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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

    public function likedByUsers(): MorphToMany
    {
        return $this->morphToMany(User::class, 'likeable', 'likes')->withTimestamps();
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
