<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\BirthdayBanner;
use App\Models\Comment;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Models\Training;
use App\Models\TrainingCategory;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Popula módulos que a Home/ACL não garantem (HomeContentSeeder cobre só
 * notícias/fotos/evento/aniversariantes mínimos pra Home) com ~10 registros
 * cada, pra dar pra ver a interface de cada tela do admin com dados de
 * verdade — comentários, respostas de fórum e curtidas inclusos. Só
 * completa o que falta até o alvo; não duplica em telas que já têm
 * conteúdo suficiente.
 */
class DemoContentSeeder extends Seeder
{
    private const TARGET = 10;

    public function run(): void
    {
        $this->ensureDocuments();
        $this->ensureTrainings();
        $this->ensureEvents();
        $this->ensureVideos();
        $this->ensurePhotoAlbums();
        $this->ensureForum();
        $this->ensureComments();
        $this->ensureBirthdayBanners();
        $this->ensureLikes();
    }

    private function ensureDocuments(): void
    {
        $missing = self::TARGET - Document::count();

        if ($missing <= 0) {
            return;
        }

        $categoryIds = DocumentCategory::pluck('id');

        Document::factory()->count($missing)->create([
            'document_category_id' => fn () => $categoryIds->isNotEmpty() ? $categoryIds->random() : null,
        ]);
    }

    private function ensureTrainings(): void
    {
        $missing = self::TARGET - Training::count();

        if ($missing <= 0) {
            return;
        }

        $categoryIds = TrainingCategory::pluck('id');

        Training::factory()->count($missing)->create([
            'training_category_id' => fn () => $categoryIds->isNotEmpty() ? $categoryIds->random() : null,
        ]);
    }

    private function ensureEvents(): void
    {
        $missing = self::TARGET - Event::count();

        if ($missing <= 0) {
            return;
        }

        $categoryIds = EventCategory::pluck('id');
        $maxOrder = Event::max('order') ?? 0;

        for ($i = 1; $i <= $missing; $i++) {
            Event::factory()->create([
                'order' => $maxOrder + $i,
                'event_category_id' => $categoryIds->isNotEmpty() ? $categoryIds->random() : null,
            ]);
        }
    }

    private function ensureVideos(): void
    {
        $missing = self::TARGET - Video::count();

        if ($missing > 0) {
            Video::factory()->count($missing)->create();
        }
    }

    private function ensurePhotoAlbums(): void
    {
        $missing = self::TARGET - PhotoAlbum::count();

        if ($missing <= 0) {
            return;
        }

        for ($i = 0; $i < $missing; $i++) {
            $album = PhotoAlbum::factory()->create();

            Photo::factory()->count(fake()->numberBetween(8, 15))->create(['photo_album_id' => $album->id]);
        }
    }

    private function ensureForum(): void
    {
        $missing = self::TARGET - ForumTopic::count();

        if ($missing <= 0) {
            return;
        }

        $userIds = User::pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $missing; $i++) {
            $topic = ForumTopic::factory()->create(['author_id' => $userIds->random()]);

            ForumReply::factory()->count(fake()->numberBetween(0, 4))->create([
                'forum_topic_id' => $topic->id,
                'author_id' => fn () => $userIds->random(),
            ]);
        }
    }

    private function ensureComments(): void
    {
        $missing = self::TARGET - Comment::count();

        if ($missing <= 0) {
            return;
        }

        $articleIds = Article::pluck('id');
        $userIds = User::pluck('id');

        if ($articleIds->isEmpty() || $userIds->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $missing; $i++) {
            $comment = Comment::factory()->create([
                'article_id' => $articleIds->random(),
                'author_id' => $userIds->random(),
            ]);

            if (fake()->boolean(30)) {
                Comment::factory()->create([
                    'article_id' => $comment->article_id,
                    'author_id' => $userIds->random(),
                    'parent_id' => $comment->id,
                ]);
            }
        }
    }

    private function ensureBirthdayBanners(): void
    {
        $existingMonths = BirthdayBanner::pluck('month');
        $missingMonths = collect(range(1, 12))->diff($existingMonths)->take(self::TARGET - $existingMonths->count());

        foreach ($missingMonths as $month) {
            BirthdayBanner::factory()->create(['month' => $month]);
        }
    }

    private function ensureLikes(): void
    {
        $users = User::inRandomOrder()->limit(self::TARGET)->get();

        if ($users->isEmpty()) {
            return;
        }

        $this->attachRandomLikes(Article::published()->get(), $users);
        $this->attachRandomLikes(PhotoAlbum::where('is_published', true)->get(), $users);
        $this->attachRandomLikes(Video::where('is_published', true)->get(), $users);
    }

    /**
     * @param  Collection<int, Article|PhotoAlbum|Video>  $likeables
     * @param  Collection<int, User>  $users
     */
    private function attachRandomLikes(Collection $likeables, Collection $users): void
    {
        foreach ($likeables as $likeable) {
            if ($likeable->likedByUsers()->exists()) {
                continue;
            }

            $likeable->likedByUsers()->attach(
                $users->random(min($users->count(), fake()->numberBetween(1, 5)))->pluck('id')
            );
        }
    }
}
